<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentoCompra;
use App\Models\DocumentoFinanciero;
use App\Imports\ComprasImport;
use App\Models\CobranzaCompra;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use App\Exports\DocumentoCompraExport;
use App\Models\MovimientoCompra;
use App\Models\MovimientoDocumento;
use Illuminate\Support\Facades\DB;
use App\Models\Banco;
use App\Models\TipoCuenta;
use App\Models\DocumentoCompraPagoProgramado;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;


class DocumentoCompraController extends Controller
{
    /**
     * Muestra todos los registros de compras
     */
    public function index(Request $request)
    {
        // === BASE QUERY con relaciones ===
        $baseQuery = DocumentoCompra::select([
                'id',
                'empresa_id',
                'cobranza_compra_id',
                'tipo_documento_id',
                'nro',
                'tipo_doc',
                'tipo_compra',
                'rut_proveedor',
                'razon_social',
                'folio',
                'fecha_docto',
                'fecha_vencimiento',
                'monto_neto',
                'monto_iva_recuperable',
                'monto_total',
                'saldo_pendiente',
                'estado',
                'status_original',
                'fecha_estado_manual',
                'referencia_id',
            ])
            ->with([
                'empresa:id,Nombre',
                'tipoDocumento:id,nombre',
                'referencia:id,folio',
                'referenciados:id,folio,referencia_id,tipo_documento_id,monto_total',
                'abonos:id,documento_compra_id,fecha_abono',
                'cruces:id,documento_compra_id,fecha_cruce',
                'pagos:id,documento_compra_id,fecha_pago',
                'prontoPagos:id,documento_compra_id,fecha_pronto_pago',
                'pagoProgramado:id,documento_compra_id,fecha_programada',
                'cobranzaCompra:id,razon_social,rut_cliente,forma_pago',
            ]);

        // === FILTROS GENERALES Y FILTROS POR COLUMNAS ===
        $this->aplicarFiltrosGeneralesListado($baseQuery, $request);
        $this->aplicarFiltrosColumnasListado($baseQuery, $request);

        // === ORDENAMIENTO ===
        [$sortBy, $sortOrder] = $this->resolverOrdenListado($request);

        // === ACTUALIZAR ESTADO AUTOMÁTICO ===
        DB::table('documentos_compras')
            ->whereDate('fecha_vencimiento', '<', now())
            ->where('saldo_pendiente', '>', 0)
            ->where('status_original', '!=', 'Vencido')
            ->update(['status_original' => 'Vencido']);

        DB::table('documentos_compras')
            ->whereDate('fecha_vencimiento', '>=', now())
            ->where('saldo_pendiente', '>', 0)
            ->where('status_original', '!=', 'Al día')
            ->update(['status_original' => 'Al día']);

        // === RE-CONTAR ESTADOS DESPUÉS DEL UPDATE ===
        $totalAlDia = (clone $baseQuery)
            ->where('status_original', 'Al día')
            ->count();

        $totalVencido = (clone $baseQuery)
            ->where('status_original', 'Vencido')
            ->count();

        // === CONTAR PAGADOS / PENDIENTES ===
        $totalPagados = (clone $baseQuery)
            ->where('saldo_pendiente', '<=', 0)
            ->count();

        $totalPendientes = (clone $baseQuery)
            ->where('saldo_pendiente', '>', 0)
            ->count();

        // === SALDO PENDIENTE GLOBAL ===
        $totalSaldoPendiente = (clone $baseQuery)
            ->whereNotIn('tipo_documento_id', [61, 56])
            ->where('saldo_pendiente', '>', 0)
            ->sum('saldo_pendiente');

        // === PAGINACIÓN ===
        $documentosCompras = $baseQuery
            ->when(
                $sortBy,
                fn ($query) => $query->orderBy($sortBy, $sortOrder),
                fn ($query) => $query->orderBy('fecha_vencimiento', 'desc')
            )
            ->paginate(10);

        // === LISTAS AUXILIARES ===
        $proveedores = \App\Models\Proveedor::select('id', 'razon_social', 'rut')
            ->orderBy('razon_social')
            ->get();

        $cobranzasCompras = CobranzaCompra::select('id', 'razon_social', 'rut_cliente')
            ->orderBy('razon_social')
            ->get();

        $tiposDocumento = \App\Models\TipoDocumento::orderBy('nombre')->get();
        $empresas = \App\Models\Empresa::orderBy('Nombre')->get();

        $bancos = Banco::orderBy('nombre')->get();
        $tipoCuentas = TipoCuenta::orderBy('nombre')->get();

        // === OPCIONES DINÁMICAS PARA FORMULARIO DE PROVEEDORES ===
        $opcionesCobranzaCompra = $this->obtenerOpcionesCobranzaCompra();

        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        if (session()->has('sugerencias_notas_compras')) {
            foreach (session('sugerencias_notas_compras') as $i => $item) {
                
            }
        }

        if (session()->has('sugerencias_notas_compras')) {
            foreach (session('sugerencias_notas_compras') as $item) {
                $id = $item['nota']->id ?? null;

                $existe = $id
                    ? \App\Models\DocumentoCompra::where('id', $id)->exists()
                    : false;
            }
        }
        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        return view('cobranzas.finanzas_compras.index', compact(
            'documentosCompras',
            'proveedores',
            'cobranzasCompras',
            'tiposDocumento',
            'empresas',
            'totalAlDia',
            'totalVencido',
            'totalPagados',
            'totalPendientes',
            'totalSaldoPendiente',
            'bancos',
            'tipoCuentas',
            'opcionesCobranzaCompra',
            'sortBy',
            'sortOrder'
        ));
    }


    public function filtrar(Request $request)
    {
        $parametros = $request->query();

        if (($parametros['sort_by'] ?? null) === 'tipo_doc_id') {
            $parametros['sort_by'] = 'tipo_documento_id';
        }

        $mapaColumnas = [
            'empresa_id' => 'cf_empresa_id',
            'status_original' => 'cf_status_original',
            'tipo_documento_id' => 'cf_tipo_documento_id',
            'rut_proveedor' => 'cf_rut_proveedor',
            'razon_social' => 'cf_razon_social',
            'folio' => 'cf_folio',
            'fecha_docto' => 'cf_fecha_docto',
            'fecha_vencimiento' => 'cf_fecha_vencimiento',
            'monto_total' => 'cf_monto_total',
        ];

        $columna = $request->input('columna');
        $valor = $request->input('valor');

        unset($parametros['columna'], $parametros['valor'], $parametros['page']);

        if ($columna && array_key_exists($columna, $mapaColumnas)) {
            unset($parametros[$mapaColumnas[$columna]]);

            if ($valor !== null && $valor !== '') {
                $parametros[$mapaColumnas[$columna]] = $valor;
            }
        }

        return redirect()->route('finanzas_compras.index', $parametros);
    }

    /**
     * Importa el archivo Excel RCV_COMPRAS
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimetypes:text/plain,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);

        $file = $request->file('file');
        $filename = $file->getClientOriginalName();

        //  Extraer RUT desde el nombre del archivo
        $rut = null;
        if (preg_match('/(\d{7,8}-[0-9Kk])/', $filename, $matches)) {
            $rut = $matches[1];
        }

        $rutLimpio = $rut ? str_replace(['.', '-', ' '], '', $rut) : null;

        $empresa = null;
        if ($rutLimpio) {
            $empresa = \App\Models\Empresa::whereRaw("
                REPLACE(REPLACE(REPLACE(rut, '.', ''), '-', ''), ' ', '') = ?
            ", [$rutLimpio])->first();
        }

        if (!$empresa) {
            return redirect()->back()
                ->with('error', "No se encontró ninguna empresa asociada al RUT {$rut} (archivo: {$filename}).");
        }

        //Importación
        $import = new ComprasImport($empresa->id);
        Excel::import($import, $request->file('file'));

        /*
        |--------------------------------------------------------------------------
        | BLOQUE AGREGADO: reprocesar sugerencias DESPUÉS del import completo
        |--------------------------------------------------------------------------
        */
        $service = new \App\Services\ReferenciaNotasCompraService();

        $notas = \App\Models\DocumentoCompra::where('empresa_id', $empresa->id)
            ->where('tipo_documento_id', 61)
            ->whereNull('referencia_id')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->get();

        $sugerenciasPostImport = [];

        foreach ($notas as $nota) {

            //NC sin cobranza → no se sugiere nada (correcto)
            if (!$nota->cobranza_compra_id) {
                continue;
            }

            $resultado = $service->generarSugerencias($nota);

            if ($resultado['sugerida'] || $resultado['alternativas']->count() > 0) {
                $sugerenciasPostImport[] = [
                    'nota' => $nota,
                    'sugerida' => $resultado['sugerida'],
                    'alternativas' => $resultado['alternativas'],
                ];
            }
        }

        if (!empty($sugerenciasPostImport)) {
            session(['sugerencias_notas_compras' => $sugerenciasPostImport]);

        }



        $totalImportados = $import->nuevos;
        $totalDuplicados = count($import->duplicados);

        if (count($import->sugerenciasNotas) > 0) {
            session()->put('sugerencias_notas_compras', $import->sugerenciasNotas);
        }

        if (count($import->sinCobranza) > 0) {
            session(['sin_compra_pendientes' => $import->sinCobranza]);
            session()->forget(['sin_cobranza', 'sin_cobranza_pendientes']);
        } else {
            session()->forget('sin_compra_pendientes');
        }

        if ($totalImportados > 0) {
            \App\Models\MovimientoCompra::create([
                'documento_compra_id' => null,
                'usuario_id' => Auth::id(),
                'tipo_movimiento' => 'Importación masiva',
                'descripcion' => "Se importaron {$totalImportados} documentos desde '{$filename}'",
                'datos_nuevos' => [
                    'archivo' => $filename,
                    'total_importados' => $totalImportados,
                    'total_duplicados' => $totalDuplicados,
                    'empresa_id' => $empresa->id,
                ],
                'fecha_cambio' => now(),
            ]);
        }

        if ($totalImportados > 0 && $totalDuplicados === 0) {
            return redirect()->route('finanzas_compras.index')->with('success', 'Archivo importado correctamente.');
        }

        if ($totalImportados === 0 && $totalDuplicados > 0) {
            return redirect()->route('finanzas_compras.index')->with([
                'warning' => 'Todos los registros ya existían.',
                'detalles_errores' => $import->duplicados
            ]);
        }

        if ($totalImportados > 0 && $totalDuplicados > 0) {
            return redirect()->route('finanzas_compras.index')->with([
                'success' => "Se importaron {$totalImportados} registros.",
                'warning' => "Se omitieron {$totalDuplicados} duplicados.",
                'detalles_errores' => $import->duplicados
            ]);
        }

        return redirect()->route('finanzas_compras.index')
            ->with('error', 'No se encontraron registros válidos para importar.');
    }

    public function asignarReferencia(Request $request)
    {
        $request->validate([
            'nota_id' => 'required|exists:documentos_compras,id',
            'factura_id' => 'required|exists:documentos_compras,id',
        ]);

        $nota = DocumentoCompra::findOrFail($request->nota_id);
        $factura = DocumentoCompra::findOrFail($request->factura_id);

        $syncMovimientoReferencia = app(\App\Services\SincronizarMovimientoReferenciaCompraService::class);

        $referenciaAnterior = $nota->referencia_id
            ? DocumentoCompra::find($nota->referencia_id)
            : null;

        // Guardar referencia
        $nota->referencia_id = $factura->id;
        $nota->save();

        // Recalcular nota (queda en 0 si tiene referencia)
        $nota->refresh();
        $nota->recalcularSaldoPendiente();

        // Recalcular nueva factura referenciada
        $factura->refresh();
        $factura->recalcularSaldoPendiente();
        $factura->refresh();
        $syncMovimientoReferencia->sincronizar($factura);
        $factura->refresh();
        $factura->recalcularSaldoPendiente();

        // Si antes tenía otra referencia, recalcular también esa factura anterior
        if ($referenciaAnterior && $referenciaAnterior->id !== $factura->id) {
            $referenciaAnterior->refresh();
            $referenciaAnterior->recalcularSaldoPendiente();
            $referenciaAnterior->refresh();
            $syncMovimientoReferencia->sincronizar($referenciaAnterior);
            $referenciaAnterior->refresh();
            $referenciaAnterior->recalcularSaldoPendiente();
        }

        // limpiar las sugerencias
        session()->forget('sugerencias_notas_compras');

        return redirect()->route('finanzas_compras.index')
            ->with('success', 'Referencia asignada correctamente.');
    }

    public function asignarReferencias(Request $request)
    {
        $referencias = $request->input('referencia');

        // No se seleccionó ninguna referencia
        if (empty($referencias) || !is_array($referencias)) {
            session()->forget('sugerencias_notas_compras');

            return response()->json([
                'success' => true,
                'message' => 'No había referencias para asignar'
            ]);
        }

        $syncMovimientoReferencia = app(\App\Services\SincronizarMovimientoReferenciaCompraService::class);

        foreach ($referencias as $notaId => $facturaId) {
            $nota = DocumentoCompra::find($notaId);
            $factura = DocumentoCompra::find($facturaId);

            if (!$nota || !$factura) {
                continue;
            }

            $referenciaAnterior = $nota->referencia_id
                ? DocumentoCompra::find($nota->referencia_id)
                : null;

            $nota->referencia_id = $factura->id;
            $nota->save();

            // Recalcular nota
            $nota->refresh();
            $nota->recalcularSaldoPendiente();

            // Recalcular factura nueva
            $factura->refresh();
            $factura->recalcularSaldoPendiente();
            $factura->refresh();
            $syncMovimientoReferencia->sincronizar($factura);
            $factura->refresh();
            $factura->recalcularSaldoPendiente();

            // Recalcular factura anterior si cambió
            if ($referenciaAnterior && $referenciaAnterior->id !== $factura->id) {
                $referenciaAnterior->refresh();
                $referenciaAnterior->recalcularSaldoPendiente();
                $referenciaAnterior->refresh();
                $syncMovimientoReferencia->sincronizar($referenciaAnterior);
                $referenciaAnterior->refresh();
                $referenciaAnterior->recalcularSaldoPendiente();
            }
        }

        // Limpiar sesión SIEMPRE
        session()->forget('sugerencias_notas_compras');

        return response()->json(['success' => true]);
    }


    public function quitarReferencia($id)
    {
        $documento = DocumentoCompra::with(['referencia', 'referenciados'])->findOrFail($id);

        $syncMovimientoReferencia = app(\App\Services\SincronizarMovimientoReferenciaCompraService::class);

        if (!$documento->referencia_id) {
            return redirect()
                ->route('finanzas_compras.show', $documento->id)
                ->with('warning', 'El documento no tiene una referencia asociada.');
        }

        $documentoReferenciado = $documento->referencia;

        $documento->referencia_id = null;
        $documento->save();

        // Recalcular saldo del documento actual
        $documento->refresh();
        $documento->recalcularSaldoPendiente();

        // Recalcular saldo del documento que antes estaba siendo referenciado
        if ($documentoReferenciado) {
            $documentoReferenciado->refresh();
            $documentoReferenciado->recalcularSaldoPendiente();
            $documentoReferenciado->refresh();
            $syncMovimientoReferencia->sincronizar($documentoReferenciado);
            $documentoReferenciado->refresh();
            $documentoReferenciado->recalcularSaldoPendiente();
        }

        return redirect()
            ->route('finanzas_compras.show', $documento->id)
            ->with('success', 'Referencia eliminada correctamente.');
    }


    public function asignarNuevaReferencia(Request $request, $id)
    {
        $request->validate([
            'factura_id' => 'required|exists:documentos_compras,id',
        ]);

        $documento = DocumentoCompra::findOrFail($id);
        $nuevaFactura = DocumentoCompra::findOrFail($request->factura_id);

        $syncMovimientoReferencia = app(\App\Services\SincronizarMovimientoReferenciaCompraService::class);

        // Validaciones de negocio
        if ($documento->id === $nuevaFactura->id) {
            return redirect()
                ->route('finanzas_compras.show', $documento->id)
                ->with('error', 'Un documento no puede referenciarse a sí mismo.');
        }

        if ($documento->empresa_id !== $nuevaFactura->empresa_id) {
            return redirect()
                ->route('finanzas_compras.show', $documento->id)
                ->with('error', 'La referencia debe pertenecer a la misma empresa.');
        }

        if ($documento->rut_proveedor !== $nuevaFactura->rut_proveedor) {
            return redirect()
                ->route('finanzas_compras.show', $documento->id)
                ->with('error', 'La referencia debe corresponder al mismo proveedor.');
        }




        if ($documento->rut_proveedor !== $nuevaFactura->rut_proveedor) {
            return redirect()
                ->route('finanzas_compras.show', $documento->id)
                ->with('error', 'La referencia debe corresponder al mismo proveedor.');
        }

        // Para este caso: una NC solo debe referenciar facturas
        if ((int) $documento->tipo_documento_id === 61 && (int) $nuevaFactura->tipo_documento_id !== 33) {
            return redirect()
                ->route('finanzas_compras.show', $documento->id)
                ->with('error', 'La Nota de Crédito solo puede referenciar una Factura Electrónica.');
        }







        // Para este caso: una NC solo debe referenciar facturas
        if ((int) $documento->tipo_documento_id === 61 && (int) $nuevaFactura->tipo_documento_id !== 33) {
            return redirect()
                ->route('finanzas_compras.show', $documento->id)
                ->with('error', 'La Nota de Crédito solo puede referenciar una Factura Electrónica.');
        }

        $referenciaAnterior = $documento->referencia_id
            ? DocumentoCompra::find($documento->referencia_id)
            : null;

        $documento->referencia_id = $nuevaFactura->id;
        $documento->save();

        // Recalcular documento actual (ej. NC -> saldo 0 si quedó referenciada)
        $documento->refresh();
        $documento->recalcularSaldoPendiente();

        // Recalcular nueva factura
        $nuevaFactura->refresh();
        $nuevaFactura->recalcularSaldoPendiente();
        $nuevaFactura->refresh();
        $syncMovimientoReferencia->sincronizar($nuevaFactura);
        $nuevaFactura->refresh();
        $nuevaFactura->recalcularSaldoPendiente();

        // Si antes apuntaba a otra factura distinta, recalcular también esa anterior
        if ($referenciaAnterior && $referenciaAnterior->id !== $nuevaFactura->id) {
            $referenciaAnterior->refresh();
            $referenciaAnterior->recalcularSaldoPendiente();
            $referenciaAnterior->refresh();
            $syncMovimientoReferencia->sincronizar($referenciaAnterior);
            $referenciaAnterior->refresh();
            $referenciaAnterior->recalcularSaldoPendiente();
        }

        return redirect()
            ->route('finanzas_compras.show', $documento->id)
            ->with('success', 'Nueva referencia asignada correctamente.');
    }



    public function export(Request $request)
    {
        $perPage = 10;
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $perPage;

        // === Query base ===
        $query = DocumentoCompra::with([
            'empresa:id,Nombre',
            'tipoDocumento:id,nombre',
            'referencia:id,tipo_documento_id,folio,fecha_docto',
            'referencia.tipoDocumento:id,nombre',
            'referenciados:id,referencia_id,tipo_documento_id,folio,monto_total',
            'referenciados.tipoDocumento:id,nombre',
        ]);

        // ===Filtros principales ===
        if ($request->filled('rut_proveedor')) {
            $query->where('rut_proveedor', 'like', "%{$request->rut_proveedor}%");
        }

        if ($request->filled('razon_social')) {
            $query->where('razon_social', 'like', "%{$request->razon_social}%");
        }

        if ($request->filled('folio')) {
            $query->where('folio', 'like', "%{$request->folio}%");
        }

        if ($request->filled('estado')) {
            $query->where(function ($q) use ($request) {
                $q->where('status_original', $request->estado)
                ->orWhere('estado', $request->estado);
            });
        }

        // ===Filtros de fechas ===
        if ($request->filled('fecha_docto_inicio') && $request->filled('fecha_docto_fin')) {
            $query->whereBetween('fecha_docto', [$request->fecha_docto_inicio, $request->fecha_docto_fin]);
        } elseif ($request->filled('fecha_docto_inicio')) {
            $query->whereDate('fecha_docto', '>=', $request->fecha_docto_inicio);
        } elseif ($request->filled('fecha_docto_fin')) {
            $query->whereDate('fecha_docto', '<=', $request->fecha_docto_fin);
        }

        if ($request->filled('fecha_venc_inicio') && $request->filled('fecha_venc_fin')) {
            $query->whereBetween('fecha_vencimiento', [$request->fecha_venc_inicio, $request->fecha_venc_fin]);
        } elseif ($request->filled('fecha_venc_inicio')) {
            $query->whereDate('fecha_vencimiento', '>=', $request->fecha_venc_inicio);
        } elseif ($request->filled('fecha_venc_fin')) {
            $query->whereDate('fecha_vencimiento', '<=', $request->fecha_venc_fin);
        }

        // ===Filtros personalizados (filtrarColumnas) ===
        if ($request->filled('columna') && $request->filled('valor')) {
            switch ($request->columna) {
                case 'razon_social':
                case 'rut_proveedor':
                case 'folio':
                    $query->where($request->columna, 'like', "%{$request->valor}%");
                    break;

                case 'fecha_docto':
                case 'fecha_vencimiento':
                    $query->whereDate($request->columna, '=', $request->valor);
                    break;

                case 'monto_total':
                    $query->where($request->columna, '=', $request->valor);
                    break;

                case 'empresa_id':
                    $query->where('empresa_id', $request->valor);
                    break;

                case 'tipo_doc_id':
                    $query->where('tipo_doc_id', $request->valor);
                    break;
            }
        }

        // ===Filtro de estado de pago (directo en SQL) ===
        if ($request->filled('estado_pago')) {
            if ($request->estado_pago === 'Pagado') {
                $query->where('saldo_pendiente', '<=', 0);
            } elseif ($request->estado_pago === 'Pendiente') {
                $query->where('saldo_pendiente', '>', 0);
            }
        }

        // ===Excluir notas de crédito / anulados ===
        $query->whereNotIn('tipo_documento_id', [61, 56]);

        // ===Orden ===
        if ($request->filled('sort_by')) {
            $sortBy = $request->get('sort_by', 'razon_social');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('fecha_vencimiento', 'desc');
        }

        // ===Ejecutar query ===
        $documentos = $query->get();

        // ===Actualizar estado automático ===
        $hoy = \Carbon\Carbon::today();
        foreach ($documentos as $doc) {
            if ($doc->fecha_vencimiento && $doc->saldo_pendiente > 0) {
                $fechaVenc = \Carbon\Carbon::parse($doc->fecha_vencimiento);
                $nuevoEstado = $fechaVenc->lt($hoy) ? 'Vencido' : 'Al día';



                if ($doc->status_original !== $nuevoEstado) {
                    DocumentoCompra::whereKey($doc->id)->update([
                        'status_original' => $nuevoEstado,
                    ]);

                    $doc->status_original = $nuevoEstado;
                }



            }
        }

        // === Paginación manual ===
        $documentos = $documentos->slice($offset, $perPage)->values();

        // ===Exportación ===
        $fecha = now()->format('Y-m-d_H-i-s');
        return Excel::download(
            new DocumentoCompraExport($documentos),
            "Cuentas_Por_Pagar_Pagina_{$page}_{$fecha}.xlsx"
        );
    }





    public function exportAll(Request $request)
    {
        // === Query base optimizada ===
        $query = DocumentoCompra::query()
            ->select([
                'id',
                'empresa_id',
                'tipo_documento_id',
                'nro',
                'tipo_doc',
                'tipo_compra',
                'rut_proveedor',
                'razon_social',
                'folio',
                'fecha_docto',
                'fecha_vencimiento',
                'status_original',
                'estado',
                'fecha_estado_manual',
                'monto_exento',
                'monto_neto',
                'monto_iva_recuperable',
                'monto_iva_no_recuperable',
                'monto_total',
                'saldo_pendiente',
                'referencia_id',
                'fecha_recepcion',
                'fecha_acuse',
                'created_at',
                'updated_at',
            ])
            ->with([
                'empresa:id,Nombre',
                'tipoDocumento:id,nombre',
                'referencia:id,tipo_documento_id,folio,fecha_docto',
                'referencia.tipoDocumento:id,nombre',
                'referenciados:id,referencia_id,tipo_documento_id,folio,monto_total',
                'referenciados.tipoDocumento:id,nombre',
            ]);

        // === Filtros principales ===
        if ($request->filled('rut_proveedor')) {
            $query->where('rut_proveedor', 'like', "%{$request->rut_proveedor}%");
        }

        if ($request->filled('razon_social')) {
            $query->where('razon_social', 'like', "%{$request->razon_social}%");
        }

        if ($request->filled('folio')) {
            $query->where('folio', 'like', "%{$request->folio}%");
        }

        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        if ($request->filled('estado')) {
            $query->where(function ($q) use ($request) {
                $q->where('status_original', $request->estado)
                    ->orWhere('estado', $request->estado);
            });
        }

        // === Filtros de fechas ===
        if ($request->filled('fecha_docto_inicio') && $request->filled('fecha_docto_fin')) {
            $query->whereBetween('fecha_docto', [
                $request->fecha_docto_inicio,
                $request->fecha_docto_fin,
            ]);
        } elseif ($request->filled('fecha_docto_inicio')) {
            $query->whereDate('fecha_docto', '>=', $request->fecha_docto_inicio);
        } elseif ($request->filled('fecha_docto_fin')) {
            $query->whereDate('fecha_docto', '<=', $request->fecha_docto_fin);
        }

        if ($request->filled('fecha_venc_inicio') && $request->filled('fecha_venc_fin')) {
            $query->whereBetween('fecha_vencimiento', [
                $request->fecha_venc_inicio,
                $request->fecha_venc_fin,
            ]);
        } elseif ($request->filled('fecha_venc_inicio')) {
            $query->whereDate('fecha_vencimiento', '>=', $request->fecha_venc_inicio);
        } elseif ($request->filled('fecha_venc_fin')) {
            $query->whereDate('fecha_vencimiento', '<=', $request->fecha_venc_fin);
        }

        // === Filtro por saldo ===
        if ($request->filled('saldo_valor')) {
            $valor = (float) str_replace(['.', ','], '', $request->saldo_valor);

            $tipoSaldo = $request->input('saldo_tipo', 'saldo_pendiente');

            $columnasPermitidas = [
                'saldo_pendiente',
                'monto_total',
            ];

            if (!in_array($tipoSaldo, $columnasPermitidas, true)) {
                $tipoSaldo = 'saldo_pendiente';
            }

            $query->whereBetween($tipoSaldo, [
                $valor - 1,
                $valor + 1,
            ]);
        }

        // === Filtros personalizados ===
        if ($request->filled('columna') && $request->filled('valor')) {
            switch ($request->columna) {
                case 'razon_social':
                case 'rut_proveedor':
                case 'folio':
                    $query->where($request->columna, 'like', "%{$request->valor}%");
                    break;

                case 'fecha_docto':
                case 'fecha_vencimiento':
                    $query->whereDate($request->columna, '=', $request->valor);
                    break;

                case 'monto_total':
                    $query->where($request->columna, '=', $request->valor);
                    break;

                case 'empresa_id':
                    $query->where('empresa_id', $request->valor);
                    break;

                case 'tipo_doc_id':
                    $query->where('tipo_doc_id', $request->valor);
                    break;
            }
        }

        // === Filtro de estado de pago ===
        if ($request->filled('estado_pago')) {
            if ($request->estado_pago === 'Pagado') {
                $query->where('saldo_pendiente', '<=', 0);
            } elseif ($request->estado_pago === 'Pendiente') {
                $query->where('saldo_pendiente', '>', 0);
            }
        }

        // === Filtro por referencias ===
        if ($request->filled('filtro_referencia')) {
            switch ($request->filtro_referencia) {
                case 'referencia_a_otro':
                    $query->whereNotNull('referencia_id');
                    break;

                case 'referenciado_por_otros':
                    $query->whereHas('referenciados');
                    break;

                case 'ambas':
                    $query->whereNotNull('referencia_id')
                        ->whereHas('referenciados');
                    break;

                case 'con_cualquier_referencia':
                    $query->where(function ($q) {
                        $q->whereNotNull('referencia_id')
                            ->orWhereHas('referenciados');
                    });
                    break;

                case 'sin_referencias':
                    $query->whereNull('referencia_id')
                        ->whereDoesntHave('referenciados');
                    break;
            }
        }

        // === Excluir notas de crédito / anulados ===
        // $query->whereNotIn('tipo_documento_id', [61, 56]);

        // === Orden ===
        if ($request->filled('sort_by')) {
            $sortBy = $request->get('sort_by', 'razon_social');
            $sortOrder = $request->get('sort_order', 'asc');

            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('fecha_vencimiento', 'desc');
        }

        // === Ejecutar query ===
        $documentos = $query->get();

        // === Calcular estado automático solo para el Excel, sin actualizar la BD ===
        $hoy = \Carbon\Carbon::today();

        $documentos->each(function ($doc) use ($hoy) {
            if ($doc->fecha_vencimiento && $doc->saldo_pendiente > 0) {
                $fechaVencimiento = \Carbon\Carbon::parse($doc->fecha_vencimiento);

                $doc->status_original = $fechaVencimiento->lt($hoy)
                    ? 'Vencido'
                    : 'Al día';
            }
        });

        // === Exportación final ===
        $fecha = now()->format('Y-m-d_H-i-s');

        return Excel::download(
            new DocumentoCompraExport($documentos),
            "Cuentas_Por_Pagar_Todos_{$fecha}.xlsx"
        );
    }





    public function updateEstado(Request $request, $id)
    {
        //  Validación básica
        $request->validate([
            'estado' => 'nullable|string|max:50',
        ]);

        // Buscar el documento
        $documento = DocumentoCompra::findOrFail($id);

        //  Guardar datos originales antes del cambio
        $datosAnteriores = [
            'estado' => $documento->estado,
            'fecha_estado_manual' => $documento->fecha_estado_manual,
        ];

        //  Actualizar el estado manual y fecha
        $documento->update([
            'estado' => $request->estado,
            'fecha_estado_manual' => now(),
        ]);

        //  Registrar movimiento con trazabilidad extendida
        MovimientoCompra::create([
            'documento_compra_id' => $documento->id,
            'usuario_id' => Auth::id(),
            'estado_anterior' => $datosAnteriores['estado'],
            'nuevo_estado' => $request->estado,
            'fecha_cambio' => now(),
            'tipo_movimiento' => 'Cambio de estado manual',
            'descripcion' => "El estado del documento fue cambiado de '{$datosAnteriores['estado']}' a '{$request->estado}'.",
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos' => [
                'estado' => $request->estado,
                'fecha_estado_manual' => $documento->fecha_estado_manual,
            ],
        ]);

        //  Redirigir con mensaje de éxito
        return redirect()
            ->route('finanzas_compras.index')
            ->with('success', 'Estado actualizado correctamente.');
    }

    public function show(DocumentoCompra $documento)
    {
        // Cargar relaciones necesarias para mostrar los detalles del documento
        $documento->load([
            'empresa',
            'tipoDocumento',
            'referencia.tipoDocumento',
            'referenciados.tipoDocumento',
            'abonos',
            'cruces.proveedor',
            'pagos',
            'prontoPagos',
            'cobranzaCompra',
            'cobranzaClienteAsociada',

            // Documentos de CxC asociados al mismo RUT del proveedor
            'documentosFinancierosAsociados.empresa',
            'documentosFinancierosAsociados.tipoDocumento',
        ]);

        // Guardar la URL anterior solo si viene desde el listado
        if (url()->previous() && !str_contains(url()->previous(), '/finanzas/compras/')) {
            session(['return_to_listado' => url()->previous()]);
        }

        $cobranzasCompras = \App\Models\CobranzaCompra::select('id', 'razon_social', 'rut_cliente')
            ->orderBy('razon_social')
            ->get();

        // Cargar proveedores para los posibles cruces
        $proveedores = \App\Models\Proveedor::orderBy('razon_social')
            ->get(['id', 'razon_social', 'rut']);

        // Candidatos para asignar nueva referencia
        $candidatosReferencia = collect();

        // Solo tiene sentido asignar referencia manual desde una Nota de Crédito
        if ((int) $documento->tipo_documento_id === 61) {
            $candidatosReferencia = \App\Models\DocumentoCompra::with('tipoDocumento')
                ->where('empresa_id', $documento->empresa_id)
                ->where('rut_proveedor', $documento->rut_proveedor)
                ->where('tipo_documento_id', 33)
                ->where('id', '!=', $documento->id)
                ->orderBy('fecha_docto', 'desc')
                ->orderBy('folio', 'desc')
                ->get();
        }

        return view('cobranzas.finanzas_compras.detalles', compact(
            'documento',
            'proveedores',
            'cobranzasCompras',
            'candidatosReferencia'
        ));
    }



    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////
   
    

    public function storeAbono(Request $request, DocumentoCompra $documento)
    {
        $request->validate([
            'monto' => 'required|integer|min:1',
            'fecha_abono' => 'required|date|before_or_equal:today',
        ], [
            'fecha_abono.before_or_equal' => 'La fecha del abono no debe sobrepasar la fecha actual.',
            'fecha_abono.required' => 'La fecha del abono es obligatoria.',
        ]);

        //Validar límite del saldo
        $saldoPendiente = $documento->saldo_pendiente;
        if ($request->monto > $saldoPendiente) {
            return back()
                ->withErrors(['monto' => 'El abono no puede ser mayor al saldo pendiente actual.'])
                ->withInput();
        }

        //Guardar datos anteriores
        $datosAnteriores = [
            'saldo_pendiente' => $saldoPendiente,
            'estado' => $documento->estado,
        ];

        //Registrar abono
        $documento->abonos()->create([
            'monto' => $request->monto,
            'fecha_abono' => $request->fecha_abono,
            'origen' => 'manual',
        ]);

        //Recalcular saldo pendiente
        $documento->recalcularSaldoPendiente();

        //Actualizar estado manual
        $documento->update([
            'estado' => 'Abono',
            'fecha_estado_manual' => now(),
        ]);

        //Registrar movimiento con trazabilidad extendida
        MovimientoCompra::create([
            'documento_compra_id' => $documento->id,
            'usuario_id' => Auth::id(),
            'estado_anterior' => $datosAnteriores['estado'],
            'nuevo_estado' => 'Abono',
            'fecha_cambio' => now(),
            'tipo_movimiento' => 'Registro de abono',
            'descripcion' => "Se registró un abono de {$request->monto} el {$request->fecha_abono}.",
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos' => [
                'monto' => $request->monto,
                'fecha_abono' => $request->fecha_abono,
                'nuevo_saldo' => $documento->saldo_pendiente,
            ],
        ]);

        return back()->with('success', 'Abono registrado correctamente.');
    }


    public function storeCruce(Request $request, DocumentoCompra $documento)
    {
        $request->validate([
            'monto' => 'nullable|integer|min:1',
            'fecha_cruce' => 'required|date|before_or_equal:today',
            'documentos_financieros_cruce' => 'nullable|array',
            'documentos_financieros_cruce.*' => 'integer|exists:documentos_financieros,id',
        ], [
            'fecha_cruce.before_or_equal' => 'La fecha del cruce no debe sobrepasar la fecha actual.',
            'fecha_cruce.required' => 'La fecha del cruce es obligatoria.',
            'documentos_financieros_cruce.*.exists' => 'Uno de los documentos seleccionados no es válido.',
        ]);

        $documento->loadMissing('cobranzaCompra', 'cobranzaClienteAsociada');

        if (!$documento->cobranza_compra_id) {
            return back()
                ->withErrors(['cobranza_compra_id' => 'El documento no tiene una cobranza de compra asociada.'])
                ->withInput();
        }

        $saldoPendienteCompra = $documento->saldo_pendiente;

        $documentosSeleccionadosIds = collect($request->input('documentos_financieros_cruce', []))
            ->filter()
            ->unique()
            ->values();

        $datosAnteriores = [
            'saldo_pendiente' => $saldoPendienteCompra,
            'estado' => $documento->estado,
        ];

        try {
            DB::transaction(function () use (
                $request,
                $documento,
                $saldoPendienteCompra,
                $documentosSeleccionadosIds,
                $datosAnteriores
            ) {
                $detalleCrucesFinancieros = collect();

                /*
                * CASO 1:
                * Vienen documentos financieros seleccionados desde CxC.
                * Se crea UNA FILA en cruces POR CADA documento seleccionado.
                */
                if ($documentosSeleccionadosIds->isNotEmpty()) {
                    $documentosFinancierosSeleccionados = DocumentoFinanciero::with([
                            'empresa',
                            'tipoDocumento',
                            'cobranza',
                        ])
                        ->whereIn('id', $documentosSeleccionadosIds)
                        ->where('rut_cliente', $documento->rut_proveedor)
                        ->whereNotIn('tipo_documento_id', [61, 56])
                        ->get();

                    if ($documentosFinancierosSeleccionados->count() !== $documentosSeleccionadosIds->count()) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'documentos_financieros_cruce' => 'Uno o más documentos seleccionados no corresponden al cliente asociado.',
                        ]);
                    }

                    foreach ($documentosFinancierosSeleccionados as $docFinanciero) {
                        if (!$docFinanciero->cobranza_id) {
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                'documentos_financieros_cruce' => "El documento financiero folio {$docFinanciero->folio} no tiene cobranza asociada.",
                            ]);
                        }

                        $montoDocumento = (int) $docFinanciero->saldo_pendiente;

                        if ($montoDocumento <= 0) {
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                'documentos_financieros_cruce' => "El documento financiero folio {$docFinanciero->folio} no tiene saldo pendiente disponible.",
                            ]);
                        }

                        $detalleCrucesFinancieros->push([
                            'id' => $docFinanciero->id,
                            'folio' => $docFinanciero->folio,
                            'empresa' => $docFinanciero->empresa?->Nombre,
                            'tipo_documento' => $docFinanciero->tipoDocumento?->nombre,
                            'rut_cliente' => $docFinanciero->rut_cliente,
                            'razon_social' => $docFinanciero->razon_social,
                            'cobranza_id' => $docFinanciero->cobranza_id,
                            'monto_cruzado' => $montoDocumento,
                        ]);
                    }

                    $montoCruceTotal = (int) $detalleCrucesFinancieros->sum('monto_cruzado');

                    if ($montoCruceTotal <= 0) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'monto' => 'El monto del cruce debe ser mayor a cero.',
                        ]);
                    }

                    if ($montoCruceTotal > $saldoPendienteCompra) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'monto' => 'El total seleccionado no puede ser mayor al saldo pendiente actual del documento de compra.',
                        ]);
                    }

                    foreach ($documentosFinancierosSeleccionados as $docFinanciero) {
                        $detalle = $detalleCrucesFinancieros
                            ->firstWhere('id', $docFinanciero->id);

                        $montoDocumento = (int) $detalle['monto_cruzado'];

                        $cruce = $documento->cruces()->create([
                            'documento_financiero_id' => $docFinanciero->id,
                            'cobranza_id' => $docFinanciero->cobranza_id,
                            'cobranza_compra_id' => $documento->cobranza_compra_id,
                            'monto' => $montoDocumento,
                            'fecha_cruce' => $request->fecha_cruce,
                        ]);

                        $docFinanciero->recalcularSaldoPendiente();

                        $docFinanciero->update([
                            'status' => 'Cruce',
                            'fecha_estado_manual' => now(),
                        ]);

                        MovimientoDocumento::create([
                            'documento_financiero_id' => $docFinanciero->id,
                            'user_id' => Auth::id(),
                            'tipo_movimiento' => 'Cruce registrado desde CxP',
                            'descripcion' => "Se registró un cruce de {$montoDocumento} el {$request->fecha_cruce} contra el documento de compra folio {$documento->folio}.",
                            'datos_nuevos' => [
                                'cruce_id' => $cruce->id,
                                'monto' => $montoDocumento,
                                'fecha_cruce' => $request->fecha_cruce,
                                'documento_compra_id' => $documento->id,
                                'documento_compra_folio' => $documento->folio,
                                'cobranza_compra_id' => $documento->cobranza_compra_id,
                                'cobranza_compra_razon_social' => $documento->cobranzaCompra?->razon_social,
                                'nuevo_saldo' => $docFinanciero->saldo_pendiente,
                            ],
                        ]);
                    }
                }

                /*
                * CASO 2:
                * No vienen documentos financieros seleccionados.
                * Mantiene el comportamiento manual anterior.
                */
                else {
                    $montoCruceTotal = (int) $request->monto;

                    if ($montoCruceTotal <= 0) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'monto' => 'El monto del cruce debe ser mayor a cero.',
                        ]);
                    }

                    if ($montoCruceTotal > $saldoPendienteCompra) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'monto' => 'El cruce no puede ser mayor al saldo pendiente actual.',
                        ]);
                    }

                    $documento->cruces()->create([
                        'monto' => $montoCruceTotal,
                        'fecha_cruce' => $request->fecha_cruce,
                        'cobranza_compra_id' => $documento->cobranza_compra_id,
                    ]);
                }

                $documento->recalcularSaldoPendiente();

                $documento->update([
                    'estado' => 'Cruce',
                    'fecha_estado_manual' => now(),
                ]);

                MovimientoCompra::create([
                    'documento_compra_id' => $documento->id,
                    'usuario_id' => Auth::id(),
                    'estado_anterior' => $datosAnteriores['estado'],
                    'nuevo_estado' => 'Cruce',
                    'fecha_cambio' => now(),
                    'tipo_movimiento' => 'Registro de cruce',
                    'descripcion' => "Se registró un cruce de {$montoCruceTotal} el {$request->fecha_cruce} con la cobranza de compra asociada {$documento->cobranzaCompra?->razon_social}.",
                    'datos_anteriores' => $datosAnteriores,
                    'datos_nuevos' => [
                        'monto' => $montoCruceTotal,
                        'fecha_cruce' => $request->fecha_cruce,
                        'cobranza_compra_id' => $documento->cobranza_compra_id,
                        'cobranza_compra_razon_social' => $documento->cobranzaCompra?->razon_social,
                        'documentos_financieros_cruce' => $detalleCrucesFinancieros->values(),
                        'saldo_anterior' => $saldoPendienteCompra,
                        'nuevo_saldo' => $documento->saldo_pendiente,
                    ],
                ]);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withErrors(['cruce' => 'Ocurrió un error al registrar el cruce.'])
                ->withInput();
        }

        return back()->with('success', 'Cruce registrado correctamente.');
    }


    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


    public function sugerencias()
    {
        $sugerencias = session('sugerencias_notas_compras', []);

        return view('cobranzas.finanzas_compras.sugerencias', compact('sugerencias'));
    }


    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



    public function storePagoProgramadoMasivo(Request $request)
    {
        $request->validate([
            'documentos'        => 'required|array|min:1',
            'documentos.*'      => 'integer|exists:documentos_compras,id',
            'fecha_programada'  => 'required|date|after_or_equal:today',
            'observacion'       => 'nullable|string|max:1000',
        ]);

        $programados = 0;
        $omitidos    = 0;

        DB::transaction(function () use ($request, &$programados, &$omitidos) {

            $ids = collect($request->documentos)
                ->unique()
                ->values();

            foreach ($ids as $documentoId) {

                $documento = DocumentoCompra::with([
                    'pagosReales',
                    'pagosPorReferencia',
                    'prontoPagos',
                    'pagoProgramado',
                ])->find($documentoId);

                if (!$documento) {
                    $omitidos++;
                    continue;
                }

                // No permitir programar documentos cerrados o sin saldo
                if (
                    (int) $documento->saldo_pendiente <= 0 ||
                    $documento->pagosReales->isNotEmpty() ||
                    $documento->pagosPorReferencia->isNotEmpty() ||
                    $documento->prontoPagos->isNotEmpty() ||
                    (int) $documento->tipo_documento_id === 61
                ) {
                    $omitidos++;
                    continue;
                }

                DocumentoCompraPagoProgramado::updateOrCreate(
                    [
                        'documento_compra_id' => $documento->id,
                    ],
                    [
                        'fecha_programada' => $request->fecha_programada,
                        'user_id'          => Auth::id(),
                        'observacion'      => $request->observacion,
                    ]
                );

                $programados++;
            }
        });

        return back()->with(
            'success',
            "Próximo pago definido correctamente. Programados: {$programados}. Omitidos: {$omitidos}."
        );
    }



    public function storePagoProgramadoMasivoExport(Request $request)
    {
        $request->validate([
            'documentos'        => 'required|array|min:1',
            'documentos.*'      => 'integer|exists:documentos_compras,id',
            'fecha_programada'  => 'required|date|after_or_equal:today',
            'observacion'       => 'nullable|string|max:1000',
        ]);

        $exportPorEmpresa = [];

        DB::transaction(function () use ($request, &$exportPorEmpresa) {

            $ids = collect($request->documentos)
                ->unique()
                ->values();

            foreach ($ids as $documentoId) {

                $documento = DocumentoCompra::with([
                    'empresa',
                    'cobranzaCompra',
                    'pagosReales',
                    'pagosPorReferencia',
                    'prontoPagos',
                    'pagoProgramado',
                ])->find($documentoId);

                if (!$documento) {
                    continue;
                }

                if (
                    (int) $documento->saldo_pendiente <= 0 ||
                    $documento->pagosReales->isNotEmpty() ||
                    $documento->pagosPorReferencia->isNotEmpty() ||
                    $documento->prontoPagos->isNotEmpty() ||
                    (int) $documento->tipo_documento_id === 61
                ) {
                    continue;
                }

                DocumentoCompraPagoProgramado::updateOrCreate(
                    [
                        'documento_compra_id' => $documento->id,
                    ],
                    [
                        'fecha_programada' => $request->fecha_programada,
                        'user_id'          => Auth::id(),
                        'observacion'      => $request->observacion,
                    ]
                );

                $formaPago = mb_strtolower(trim((string) optional($documento->cobranzaCompra)->forma_pago));

                if ($formaPago === 'portal proveedor') {
                    continue;
                }

                $empresaId = $documento->empresa_id;

                $exportPorEmpresa[$empresaId]['empresa'] ??=
                    optional($documento->empresa)->Nombre ?? 'Empresa';




                $exportPorEmpresa[$empresaId]['items'][] = [
                    'documento_id' => $documento->id,
                    'tipo'         => 'pago',
                    'monto'        => (int) $documento->saldo_pendiente,
                    'fecha'        => $request->fecha_programada,
                ];




            }
        });

        $downloads = [];

        foreach ($exportPorEmpresa as $empresaId => $data) {

            if (empty($data['items'])) {
                continue;
            }

            $token = (string) Str::uuid();

            Cache::put(
                "proximos_pagos_empresa:{$token}",
                $data,
                now()->addMinutes(10)
            );

            $downloads[] = [
                'empresa' => $data['empresa'],
                'url'     => route('finanzas_compras.proximo_pago.descargar', $token),
            ];
        }

        return response()->json([
            'ok'        => true,
            'downloads' => $downloads,
        ]);
    }

    public function downloadPagoProgramadoEmpresa(string $token)
    {
        $cacheKey = "proximos_pagos_empresa:{$token}";

        $data = Cache::get($cacheKey);

        if (!$data || empty($data['items'])) {
            abort(404, 'No hay próximos pagos para exportar.');
        }

        Cache::forget($cacheKey);

        $empresa = str_replace(' ', '_', $data['empresa']);
        $fecha   = now()->format('Ymd_His');
        $nombre  = "{$empresa}_Proximo_Pago_Proveedores_{$fecha}.xlsx";

        return Excel::download(
            new \App\Exports\PagosMasivosDocumentoCompraExport($data['items']),
            $nombre
        );
    }

    public function destroyPagoProgramadoMasivo(Request $request)
    {
        $usuariosFinanzas = [1, 405, 374, 375];

        if (!in_array(Auth::id(), $usuariosFinanzas)) {
            abort(403, 'Acceso denegado. No tienes permiso para realizar esta acción.');
        }

        $request->validate([
            'programados'   => 'required|array|min:1',
            'programados.*' => 'integer|exists:documento_compra_pagos_programados,id',
        ]);

        $eliminados = 0;
        $omitidos = 0;

        DB::transaction(function () use ($request, &$eliminados, &$omitidos) {
            $ids = collect($request->programados)
                ->unique()
                ->values();

            foreach ($ids as $programadoId) {
                $programado = DocumentoCompraPagoProgramado::find($programadoId);

                if (!$programado) {
                    $omitidos++;
                    continue;
                }

                $programado->delete();
                $eliminados++;
            }
        });

        return back()->with(
            'success',
            "Fechas de próximo pago eliminadas correctamente. Eliminadas: {$eliminados}. Omitidas: {$omitidos}."
        );
    }



    private function obtenerOpcionesCobranzaCompra(): array
    {
        $campos = [
            'servicio',
            'tipo',
            'facturacion',
            'forma_pago',
            'zona',
            'importancia',
            'responsable',
            'nombre_cuenta',
            'rut_cuenta',
            'numero_cuenta',
        ];

        $opciones = [];

        foreach ($campos as $campo) {
            $opciones[$campo] = CobranzaCompra::query()
                ->whereNotNull($campo)
                ->pluck($campo)
                ->map(fn ($valor) => trim((string) $valor))
                ->filter(fn ($valor) => $valor !== '' && $valor !== '1')
                ->unique(fn ($valor) => mb_strtolower($valor))
                ->sortBy(fn ($valor) => mb_strtolower($valor))
                ->values();
        }

        return $opciones;
    }



    private function aplicarFiltrosGeneralesListado(\Illuminate\Database\Eloquent\Builder $query, Request $request): void 
    {
        if ($request->filled('rut_proveedor')) {
            $query->where('rut_proveedor', 'like', '%' . $request->input('rut_proveedor') . '%');
        }

        if ($request->filled('razon_social')) {
            $query->where('razon_social', 'like', '%' . $request->input('razon_social') . '%');
        }

        if ($request->filled('folio')) {
            $query->where('folio', trim($request->input('folio')));
        }

        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->input('empresa_id'));
        }

        if ($request->filled('estado')) {
            $query->where(function ($q) use ($request) {
                $q->where('status_original', $request->input('estado'))
                    ->orWhere('estado', $request->input('estado'));
            });
        }

        if ($request->filled('fecha_docto_inicio') && $request->filled('fecha_docto_fin')) {
            $query->whereBetween('fecha_docto', [
                $request->input('fecha_docto_inicio'),
                $request->input('fecha_docto_fin'),
            ]);
        } elseif ($request->filled('fecha_docto_inicio')) {
            $query->whereDate('fecha_docto', '>=', $request->input('fecha_docto_inicio'));
        } elseif ($request->filled('fecha_docto_fin')) {
            $query->whereDate('fecha_docto', '<=', $request->input('fecha_docto_fin'));
        }

        if ($request->filled('fecha_venc_inicio') && $request->filled('fecha_venc_fin')) {
            $query->whereBetween('fecha_vencimiento', [
                $request->input('fecha_venc_inicio'),
                $request->input('fecha_venc_fin'),
            ]);
        } elseif ($request->filled('fecha_venc_inicio')) {
            $query->whereDate('fecha_vencimiento', '>=', $request->input('fecha_venc_inicio'));
        } elseif ($request->filled('fecha_venc_fin')) {
            $query->whereDate('fecha_vencimiento', '<=', $request->input('fecha_venc_fin'));
        }

        if ($request->filled('saldo_valor')) {
            $valor = (float) str_replace(['.', ','], '', $request->input('saldo_valor'));
            $tipoSaldo = $request->input('saldo_tipo', 'saldo_pendiente');

            if (!in_array($tipoSaldo, ['saldo_pendiente', 'monto_total'], true)) {
                $tipoSaldo = 'saldo_pendiente';
            }

            $query->whereBetween($tipoSaldo, [
                $valor - 1,
                $valor + 1,
            ]);
        }

        if ($request->filled('estado_pago')) {
            if ($request->input('estado_pago') === 'Pagado') {
                $query->where('saldo_pendiente', '<=', 0);
            }

            if ($request->input('estado_pago') === 'Pendiente') {
                $query->where('saldo_pendiente', '>', 0);
            }
        }

        if ($request->filled('filtro_referencia')) {
            switch ($request->input('filtro_referencia')) {
                case 'referencia_a_otro':
                    $query->whereNotNull('referencia_id');
                    break;

                case 'referenciado_por_otros':
                    $query->whereHas('referenciados');
                    break;

                case 'ambas':
                    $query->whereNotNull('referencia_id')
                        ->whereHas('referenciados');
                    break;

                case 'con_cualquier_referencia':
                    $query->where(function ($q) {
                        $q->whereNotNull('referencia_id')
                            ->orWhereHas('referenciados');
                    });
                    break;

                case 'sin_referencias':
                    $query->whereNull('referencia_id')
                        ->whereDoesntHave('referenciados');
                    break;
            }
        }
    }


    private function aplicarFiltrosColumnasListado( \Illuminate\Database\Eloquent\Builder $query, Request $request): void 
    {

        if ($request->filled('cf_empresa_id')) {
            $empresa = trim((string) $request->input('cf_empresa_id'));

            if (ctype_digit($empresa)) {
                $query->where('empresa_id', $empresa);
            } else {
                $query->whereHas('empresa', function ($q) use ($empresa) {
                    $q->where('Nombre', 'like', '%' . $empresa . '%');
                });
            }
        }

        if ($request->filled('cf_status_original')) {
            $query->where('status_original', $request->input('cf_status_original'));
        }

        if ($request->filled('cf_tipo_documento_id')) {
            $tipoDocumento = trim((string) $request->input('cf_tipo_documento_id'));

            if (ctype_digit($tipoDocumento)) {
                $query->where('tipo_documento_id', $tipoDocumento);
            } else {
                $query->whereHas('tipoDocumento', function ($q) use ($tipoDocumento) {
                    $q->where('nombre', 'like', '%' . $tipoDocumento . '%');
                });
            }
        }

        if ($request->filled('cf_rut_proveedor')) {
            $query->where(
                'rut_proveedor',
                'like',
                '%' . $request->input('cf_rut_proveedor') . '%'
            );
        }

        if ($request->filled('cf_razon_social')) {
            $this->aplicarFiltroRazonSocial(
                $query,
                $request->input('cf_razon_social')
            );
        }

        if ($request->filled('cf_folio')) {
            $query->where(
                'folio',
                'like',
                '%' . $request->input('cf_folio') . '%'
            );
        }

        if ($request->filled('cf_fecha_docto')) {
            $query->whereDate(
                'fecha_docto',
                $request->input('cf_fecha_docto')
            );
        }

        if ($request->filled('cf_fecha_vencimiento')) {
            $query->whereDate(
                'fecha_vencimiento',
                $request->input('cf_fecha_vencimiento')
            );
        }

        if ($request->filled('cf_monto_total')) {
            $valor = (float) str_replace(
                ['.', ','],
                '',
                $request->input('cf_monto_total')
            );

            $query->where('monto_total', $valor);
        }
    }

    private function aplicarFiltroRazonSocial(\Illuminate\Database\Eloquent\Builder $query, string $busqueda): void 
    {
        $busquedaNormalizada = preg_replace('/[^a-zA-Z0-9\s]/u', '', $busqueda);

        $query->whereRaw("
            REPLACE(REPLACE(REPLACE(razon_social, '.', ''), ',', ''), '  ', ' ')
            LIKE ?
        ", ["%{$busquedaNormalizada}%"]);
    }

    private function resolverOrdenListado(Request $request): array
    {
        $sortBy = $request->input('sort_by');
        $sortOrder = strtolower($request->input('sort_order', 'asc'));

        if ($sortBy === 'tipo_doc_id') {
            $sortBy = 'tipo_documento_id';
        }

        $columnasPermitidas = [
            'empresa_id',
            'status_original',
            'tipo_documento_id',
            'rut_proveedor',
            'razon_social',
            'folio',
            'fecha_docto',
            'fecha_vencimiento',
            'monto_total',
        ];

        if (!in_array($sortBy, $columnasPermitidas, true)) {
            return [null, 'asc'];
        }

        if (!in_array($sortOrder, ['asc', 'desc'], true)) {
            $sortOrder = 'asc';
        }

        return [$sortBy, $sortOrder];
    }





}
