<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentoFinanciero;
use App\Models\Empresa;
use App\Models\TipoDocumento;
use App\Models\CesionFactory;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\MovimientoDocumento;
use App\Exports\DocumentosExport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Cobranza;
use App\Services\Ventas\DocumentoFinancieroImportService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Exports\DocumentosAlCorteExport;

class DocumentoFinancieroController extends Controller
{
    //

    public function index(Request $request)
    {
        $usuariosFinanzas = [1, 405, 374, 375];

        if (!in_array(Auth::id(), $usuariosFinanzas)) {
            abort(403, 'Acceso denegado. No tienes permiso para ingresar a este módulo.');
        }

        $baseQuery = DocumentoFinanciero::query();

        $fechaCorte = $this->resolverFechaCorte($request);
        $this->aplicarFiltroFechaCorte($baseQuery, $fechaCorte);
        $this->aplicarFiltrosGeneralesListado($baseQuery, $request);
        $this->aplicarFiltrosColumnasListado($baseQuery, $request);

        [$sortBy, $sortOrder] = $this->resolverOrdenListado($request);

        $totales = (clone $baseQuery)
            ->selectRaw('status_original, COUNT(*) as total')
            ->groupBy('status_original')
            ->pluck('total', 'status_original');

        $totalAlDia = $totales['Al día'] ?? 0;
        $totalVencido = $totales['Vencido'] ?? 0;

        if ($request->filled('status')) {
            $baseQuery->where('status_original', $request->status);
        }

        $query = (clone $baseQuery)->with([
            'cobranza:id,razon_social,rut_cliente',
            'empresa:id,Nombre',
            'tipoDocumento:id,nombre',
            'abonos:id,documento_financiero_id,fecha_abono,monto',
            'cruces:id,documento_financiero_id,documento_compra_id,cobranza_compra_id,monto,fecha_cruce',
            'cruces.documentoCompra:id,folio,razon_social,rut_proveedor',
            'pagos:id,documento_financiero_id,fecha_pago',
            'prontoPagos:id,documento_financiero_id,fecha_pronto_pago',

            /*
            |--------------------------------------------------------------------------
            | Factoring múltiple
            |--------------------------------------------------------------------------
            | Se carga la colección completa utilizada actualmente por el modal.
            | No se carga factoryRegistro porque latestOfMany() genera un JOIN
            | interno y la carga limitada por columnas produce ambigüedad SQL.
            |--------------------------------------------------------------------------
            */
            'factories:id,documento_financiero_id,cesion_factoring_id,banco_id,rut_factory,cesion,fecha_factory,monto,saldo_liquido,monto_no_anticipado,diferencia_precio,comision_total,monto_a_recibir,estado_operacion,user_id,created_at',
            'factories.banco:id,nombre',

            'referencia:id,folio,tipo_documento_id',
            'referenciados:id,referencia_id,folio',
        ]);

        $totalSaldoPendiente = (clone $baseQuery)
            ->whereNotIn('tipo_documento_id', [61, 56])
            ->where('saldo_pendiente', '>', 0)
            ->sum('saldo_pendiente');

        $documentosOriginal = $query
            ->when(
                $sortBy,
                fn ($q) => $q->orderBy($sortBy, $sortOrder),
                fn ($q) => $q
                    ->orderByRaw('fecha_vencimiento IS NULL, fecha_vencimiento DESC')
                    ->orderBy('folio', 'DESC')
            )
            ->paginate(10);

        DB::table('documentos_financieros')
            ->whereDate('fecha_vencimiento', '<', now())
            ->where('saldo_pendiente', '>', 0)
            ->where('status_original', '!=', 'Vencido')
            ->update(['status_original' => 'Vencido']);

        DB::table('documentos_financieros')
            ->whereDate('fecha_vencimiento', '>=', now())
            ->where('saldo_pendiente', '>', 0)
            ->where('status_original', '!=', 'Al día')
            ->update(['status_original' => 'Al día']);

        $totalPagados = (clone $baseQuery)
            ->where('saldo_pendiente', '<=', 0)
            ->count();

        $totalPendientes = (clone $baseQuery)
            ->where('saldo_pendiente', '>', 0)
            ->count();

        $empresas = Empresa::orderBy('Nombre')->get(['id', 'Nombre']);
        $tiposDocumento = TipoDocumento::orderBy('nombre')->get(['id', 'nombre']);
        $proveedores = \App\Models\Proveedor::orderBy('razon_social')->get(['id', 'razon_social', 'rut']);
        $cobranzas = Cobranza::orderBy('razon_social')->get(['id', 'razon_social', 'rut_cliente']);
        $bancos = \App\Models\Banco::orderBy('nombre')->get(['id', 'nombre']);

        $cesionesFactoringExistentes = CesionFactory::query()
            ->where('estado_operacion', 'Vigente')
            ->orderByDesc('created_at')
            ->get([
                'id',
                'cesion',
                'banco_id',
                'fecha_operacion',
                'comision_total',
                'monto_a_recibir',
                'estado_operacion',
            ])
            ->map(function ($cesionFactory) {
                return [
                    'id' => $cesionFactory->id,
                    'cesion' => $cesionFactory->cesion,
                    'banco_id' => $cesionFactory->banco_id,
                    'fecha_operacion' => optional($cesionFactory->fecha_operacion)->format('Y-m-d') 
                        ?? $cesionFactory->fecha_operacion,
                    'comision_total' => (int) $cesionFactory->comision_total,
                    'monto_a_recibir' => (int) ($cesionFactory->monto_a_recibir ?? 0),
                    'estado_operacion' => $cesionFactory->estado_operacion,
                ];
            })
            ->values();

        return view('cobranzas.documentos', compact(
            'documentosOriginal',
            'totalAlDia',
            'totalVencido',
            'totalSaldoPendiente',
            'totalPagados',
            'totalPendientes',
            'empresas',
            'tiposDocumento',
            'proveedores',
            'cobranzas',
            'bancos',
            'cesionesFactoringExistentes',
            'sortBy',
            'sortOrder'
        ));
    }

    public function filtrarColumnas(Request $request)
    {
        $usuariosFinanzas = [1, 405, 374, 375];

        if (!in_array(Auth::id(), $usuariosFinanzas)) {
            abort(403, 'Acceso denegado.');
        }

        $parametros = $request->query();

        if (($parametros['sort_by'] ?? null) === 'tipo_doc_id') {
            $parametros['sort_by'] = 'tipo_documento_id';
        }

        $mapaColumnas = [
            'empresa_id' => 'cf_empresa_id',
            'tipo_documento_id' => 'cf_tipo_documento_id',
            'tipo_doc_id' => 'cf_tipo_documento_id',
            'rut_cliente' => 'cf_rut_cliente',
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

        return redirect()->route('cobranzas.documentos', $parametros);
    }



    public function general(Request $request)
    {
        $usuariosFinanzas = [1, 405, 374, 375];

        if (!in_array(Auth::id(), $usuariosFinanzas)) {
            abort(403, 'Acceso denegado. No tienes permiso para ingresar a este módulo.');
        }

        $hoy = \Carbon\Carbon::today();





        $comprasProgramadasHoy = \App\Models\DocumentoCompraPagoProgramado::with([
            'documentoCompra:id,empresa_id,cobranza_compra_id,tipo_documento_id,folio,razon_social,rut_proveedor,monto_total,saldo_pendiente,estado,referencia_id',
            'documentoCompra.empresa:id,Nombre',
            'documentoCompra.cobranzaCompra:id,razon_social,rut_cliente,forma_pago',
        ])
        ->whereDate('fecha_programada', $hoy)
        ->whereHas('documentoCompra', function ($q) {
            $q->where('saldo_pendiente', '>', 0)
            ->where('tipo_documento_id', '!=', 61)
            ->doesntHave('pagosReales')
            ->doesntHave('pagosPorReferencia')
            ->doesntHave('prontoPagos');
        })
        ->orderBy('fecha_programada')
        ->get();

        $comprasProgramadasAtrasadas = \App\Models\DocumentoCompraPagoProgramado::with([
            'documentoCompra:id,empresa_id,cobranza_compra_id,tipo_documento_id,folio,razon_social,rut_proveedor,monto_total,saldo_pendiente,estado,referencia_id',
            'documentoCompra.empresa:id,Nombre',
            'documentoCompra.cobranzaCompra:id,razon_social,rut_cliente,forma_pago',
        ])
        ->whereDate('fecha_programada', '<', $hoy)
        ->whereHas('documentoCompra', function ($q) {
            $q->where('saldo_pendiente', '>', 0)
            ->where('tipo_documento_id', '!=', 61)
            ->doesntHave('pagosReales')
            ->doesntHave('pagosPorReferencia')
            ->doesntHave('prontoPagos');
        })
        ->orderBy('fecha_programada')
        ->get();





        return view('cobranzas.general', compact(
            'comprasProgramadasHoy',
            'comprasProgramadasAtrasadas'
        ));
    }


    public function import(Request $request, DocumentoFinancieroImportService $importService)
    {
        $request->validate([
            'file' => 'required|mimetypes:text/plain,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);

        $filename = $request->file('file')->getClientOriginalName();

        try {
            $import = $importService->execute($request->file('file'));
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('cobranzas.documentos')
                ->with('error', 'No se pudo importar el archivo.')
                ->with('detalles_errores', [
                    $e->getMessage()
                ]);
        }

        $mensajes = [];

        if (count($import->errores) > 0) {
            foreach ($import->errores as $error) {
                $mensajes[] = "⚠️ " . $error;
            }

            return redirect()->route('cobranzas.documentos')
                ->with('error', 'El archivo no cumple con la estructura esperada.')
                ->with('detalles_errores', $mensajes);
        }

        if (count($import->importados) > 0) {
            $mensajes[] = count($import->importados) . " documentos importados correctamente: "
                . implode(', ', $import->importados) . ".";
        }

        if (count($import->duplicados) > 0) {
            $mensajes[] = "Los siguientes folios ya existían y no se importaron: "
                . implode(', ', $import->duplicados);
        }

        if (count($import->sinCobranza) > 0) {
            session([
                'sin_cobranza_guiada' => $import->sinCobranza,
                'sin_cobranza_pendientes' => $import->sinCobranza,
            ]);

            session()->forget(['sin_cobranza', 'detalles_errores']);

            return redirect()->route('cobranzas.documentos')
                ->with('info', 'Se detectaron nuevos clientes sin cobranza. El sistema abrirá el formulario para crearlos.');
        } else {
            session()->forget(['sin_cobranza', 'sin_cobranza_guiada', 'sin_cobranza_pendientes']);
        }

        if (count($import->notasCredito) > 0) {
            foreach ($import->notasCredito as $nota) {
                $mensajes[] = $nota;
            }
        }

        if (count($mensajes) > 0) {
            return redirect()->route('cobranzas.documentos')
                ->with('warning', 'La importación finalizó con observaciones.')
                ->with('detalles_errores', $mensajes);
        }

        if (count($import->importados) > 0) {
            \App\Models\MovimientoDocumento::create([
                'documento_financiero_id' => null,
                'user_id' => Auth::id(),
                'tipo_movimiento' => 'Importación masiva',
                'descripcion' => "Se importaron " . count($import->importados) .
                    " documentos desde el archivo '{$filename}' el " . now()->format('d/m/Y H:i:s'),
            ]);
        }

        return redirect()->route('cobranzas.documentos')
            ->with('success', 'Archivo importado correctamente.');
    }


    public function updateStatus(Request $request, DocumentoFinanciero $documento)
    {
        $request->validate([
            'status' => [
                'nullable',
                'string',
                'max:50',
                'in:Abono,Cruce,Pago,Pronto pago,Cobranza judicial',
            ],
            'fecha_estado_manual' => 'nullable|date',
        ]);

        $nuevoStatus = $request->status;

        /*
        |--------------------------------------------------------------------------
        | Protección importante:
        | Factory NO se debe guardar desde este método.
        | Factory requiere registro en tabla factories mediante FactoryController.
        |--------------------------------------------------------------------------
        */
        if ($nuevoStatus === 'Factory') {
            return redirect()
                ->back()
                ->withErrors([
                    'status' => 'El estado Factory debe registrarse desde el formulario específico de Factory.',
                ])
                ->withInput();
        }

        $original = $documento->getOriginal();

        // Guardar estado manual simple
        $documento->status = $nuevoStatus;

        // Estados manuales que llevan fecha
        if (in_array($nuevoStatus, [
            'Abono',
            'Cruce',
            'Pago',
            'Pronto pago',
            'Cobranza judicial',
        ], true)) {
            $documento->fecha_estado_manual = $request->fecha_estado_manual ?? now();
        } else {
            $documento->fecha_estado_manual = null;
        }

        /*
        |--------------------------------------------------------------------------
        | Compatibilidad con flujo antiguo:
        | Aunque actualmente Pago y Pronto pago se registran desde formularios
        | específicos, dejamos esta protección por si alguien usa updateStatus directo.
        |--------------------------------------------------------------------------
        */

        if ($nuevoStatus === 'Pago') {
            if (!$documento->pagos()->exists()) {
                $documento->pagos()->create([
                    'fecha_pago' => $documento->fecha_estado_manual,
                    'user_id' => Auth::id(),
                ]);
            }

            $documento->saldo_pendiente = 0;
        }

        if ($nuevoStatus === 'Pronto pago') {
            if (!$documento->prontoPagos()->exists()) {
                $documento->prontoPagos()->create([
                    'fecha_pronto_pago' => $documento->fecha_estado_manual,
                    'user_id' => Auth::id(),
                ]);
            }

            $documento->saldo_pendiente = 0;
        }

        // Guardar solo si hay cambios
        if ($documento->isDirty(['status', 'fecha_estado_manual', 'saldo_pendiente'])) {
            $documento->save();

            MovimientoDocumento::create([
                'documento_financiero_id' => $documento->id,
                'user_id' => Auth::id(),
                'tipo_movimiento' => 'Actualización de estado manual',
                'descripcion' => "Estado manual cambiado de '{$original['status']}' a '{$documento->status}'",
                'datos_anteriores' => $original,
                'datos_nuevos' => $documento->getChanges(),
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'Estado manual actualizado correctamente.');
    }


    /////////////////////////////////////////////////////
    ////// EXPORTACIÓN //////////////////////////////////
    ///////////////////////////////////////////////////// 
    public function export(Request $request)
    {
        $perPage = 10; // igual que en index()
        $page = $request->get('page', 1);

        // === Construcción de la query base ===
        $query = DocumentoFinanciero::with([
            'empresa',
            'tipoDocumento',
            'referencia.tipoDocumento',
            'referenciados.tipoDocumento',
            'abonos:id,documento_financiero_id,monto,fecha_abono',
            'cruces:id,documento_financiero_id,monto,fecha_cruce',
            'pagos:id,documento_financiero_id,fecha_pago',
            'prontoPagos:id,documento_financiero_id,fecha_pronto_pago',
            'cobranza',
        ]);

        // === Aplicar los mismos filtros que en index() ===
        if ($request->filled('razon_social')) {
            $query->where('razon_social', 'like', "%{$request->razon_social}%");
        }

        if ($request->filled('rut_cliente')) {
            $query->where('rut_cliente', 'like', "%{$request->rut_cliente}%");
        }

        if ($request->filled('folio')) {
            $query->where('folio', 'like', "%{$request->folio}%");
        }

        if ($request->filled('status')) {
            $query->where('status_original', $request->status);
        }

        // === Filtros adicionales (de filtrarColumnas) ===
        if ($request->filled('columna') && $request->filled('valor')) {
            switch ($request->columna) {
                case 'razon_social':
                case 'rut_cliente':
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

                case 'tipo_documento_id':
                    $query->where('tipo_documento_id', $request->valor);
                    break;

            }
        }

        // === Filtros de fechas ===
        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha_docto', [$request->fecha_inicio, $request->fecha_fin]);
        } elseif ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_docto', '>=', $request->fecha_inicio);
        } elseif ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_docto', '<=', $request->fecha_fin);
        }

        if ($request->filled('vencimiento_inicio') && $request->filled('vencimiento_fin')) {
            $query->whereBetween('fecha_vencimiento', [$request->vencimiento_inicio, $request->vencimiento_fin]);
        } elseif ($request->filled('vencimiento_inicio')) {
            $query->whereDate('fecha_vencimiento', '>=', $request->vencimiento_inicio);
        } elseif ($request->filled('vencimiento_fin')) {
            $query->whereDate('fecha_vencimiento', '<=', $request->vencimiento_fin);
        }

        // ===Ordenamiento ===
        if ($request->filled('sort_by')) {
            $sortBy = $request->get('sort_by', 'razon_social');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderByRaw('ISNULL(fecha_vencimiento), fecha_vencimiento DESC');
        }

        // ===Paginar antes de cargar ===
        $documentosPaginados = $query->paginate($perPage, ['*'], 'page', $page);

        // ===Filtrar Estado de Pago en la colección ya paginada ===
        $documentosFiltrados = $documentosPaginados->getCollection()->filter(function ($doc) use ($request) {
            if ($request->filled('estado_pago')) {
                if ($request->estado_pago === 'Pagado') {
                    return $doc->saldo_pendiente <= 0;
                }
                if ($request->estado_pago === 'Pendiente') {
                    return $doc->saldo_pendiente > 0;
                }
            }
            return true;
        });

        // ===Reemplazar la colección en el paginador ===
        $documentosPaginados->setCollection($documentosFiltrados);

        // ===Exportación final solo con los registros filtrados ===
        $fecha = now()->format('Y-m-d_H-i-s');
        return Excel::download(
            new DocumentosExport($documentosFiltrados),
            "Cuentas_Por_Cobrar_Pagina_{$page}_{$fecha}.xlsx"
        );
    }



    public function exportAll(Request $request)
    {
        // ===Construcción de la query base ===
        $query = DocumentoFinanciero::with([
            'empresa',
            'tipoDocumento',
            'referencia.tipoDocumento',
            'referenciados.tipoDocumento',
            'abonos:id,documento_financiero_id,monto,fecha_abono',
            'cruces:id,documento_financiero_id,monto,fecha_cruce',
            'pagos:id,documento_financiero_id,fecha_pago',
            'prontoPagos:id,documento_financiero_id,fecha_pronto_pago',
            'cobranza',
        ]);

        // ===Filtros base (idénticos al index) ===
        if ($request->filled('razon_social')) {
            $query->where('razon_social', 'like', "%{$request->razon_social}%");
        }

        if ($request->filled('rut_cliente')) {
            $query->where('rut_cliente', 'like', "%{$request->rut_cliente}%");
        }

        if ($request->filled('folio')) {
            $query->where('folio', 'like', "%{$request->folio}%");
        }

        if ($request->filled('status')) {
            $query->where('status_original', $request->status);
        }

        // ===Filtros adicionales (desde filtrarColumnas) ===
        if ($request->filled('columna') && $request->filled('valor')) {
            switch ($request->columna) {
                case 'razon_social':
                case 'rut_cliente':
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

                case 'tipo_documento_id':
                    $query->where('tipo_documento_id', $request->valor);
                    break;

            }
        }

        // ===Filtros de fechas ===
        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha_docto', [$request->fecha_inicio, $request->fecha_fin]);
        } elseif ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_docto', '>=', $request->fecha_inicio);
        } elseif ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_docto', '<=', $request->fecha_fin);
        }

        if ($request->filled('vencimiento_inicio') && $request->filled('vencimiento_fin')) {
            $query->whereBetween('fecha_vencimiento', [$request->vencimiento_inicio, $request->vencimiento_fin]);
        } elseif ($request->filled('vencimiento_inicio')) {
            $query->whereDate('fecha_vencimiento', '>=', $request->vencimiento_inicio);
        } elseif ($request->filled('vencimiento_fin')) {
            $query->whereDate('fecha_vencimiento', '<=', $request->vencimiento_fin);
        }

        // ===Excluir notas de crédito y anulados ===
        // $query->whereNotIn('tipo_documento_id', [61, 56]);

        // === Ordenamiento ===
        if ($request->filled('sort_by')) {
            $sortBy = $request->get('sort_by', 'razon_social');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderByRaw('ISNULL(fecha_vencimiento), fecha_vencimiento DESC');
        }

        // === Ejecutar query una sola vez ===
        $documentos = $query->get();


        // === Precálculos para optimizar el export ===
        // Evita recalcular sumas y fechas dentro de DocumentosExport::map()
        $documentos->each(function ($doc) {
            $doc->total_abonado = $doc->abonos->sum('monto');
            $doc->ultima_fecha_abono = $doc->abonos->max('fecha_abono');
            $doc->total_cruzado = $doc->cruces->sum('monto');
            $doc->ultima_fecha_cruce = $doc->cruces->max('fecha_cruce');
        });

        // === Filtro de Estado de Pago (en memoria, con accessor saldo_pendiente) ===
        if ($request->filled('estado_pago')) {
            $documentos = $documentos->filter(function ($doc) use ($request) {
                if ($request->estado_pago === 'Pagado') {
                    return $doc->saldo_pendiente <= 0;
                }
                if ($request->estado_pago === 'Pendiente') {
                    return $doc->saldo_pendiente > 0;
                }
                return true;
            });
        }

        // ===Actualizar estado automático antes de exportar ===
        $hoy = \Carbon\Carbon::today();
        foreach ($documentos as $doc) {
            if ($doc->fecha_vencimiento && $doc->saldo_pendiente > 0) {
                $fechaVenc = \Carbon\Carbon::parse($doc->fecha_vencimiento);
                $nuevoEstado = $fechaVenc->lt($hoy) ? 'Vencido' : 'Al día';


                if ($doc->status_original !== $nuevoEstado) {
                    DocumentoFinanciero::whereKey($doc->id)->update([
                        'status_original' => $nuevoEstado,
                    ]);

                    $doc->status_original = $nuevoEstado;
                }



            }
        }

        // ===Exportación final (sin paginación) ===
        $fecha = now()->format('Y-m-d_H-i-s');
        return Excel::download(
            new DocumentosExport($documentos),
            "Cuentas_Por_Cobrar_Todos_{$fecha}.xlsx"
        );
    }




    ////////////////////////////////////////////////////
    ////////////////////////////////////////////////////
    ////////////////////////////////////////////////////

    public function show(DocumentoFinanciero $documento)
    {
        /*
        |--------------------------------------------------------------------------
        | Cargar relaciones relevantes
        |--------------------------------------------------------------------------
        | factories contiene todos los registros Factoring asociados al documento.
        | Ya no se requiere cargar factoryRegistro porque las vistas fueron
        | migradas para consumir la colección completa.
        |--------------------------------------------------------------------------
        */
        $documento->load([
            'empresa',
            'tipoDocumento',

            'abonos',
            'cruces.proveedor',
            'cruces.documentoCompra',
            'pagos',
            'prontoPagos',

            'factories.banco',
            'factories.usuario',
            'factories.cesionFactory',

            'referencia',
            'referenciados',
            'cobranza',
            'cobranzaCompraAsociada',

            // Documentos de compra asociados al mismo RUT del cliente
            'documentosCompraAsociados.empresa',
            'documentosCompraAsociados.tipoDocumento',
        ]);

        // Guardar la URL anterior solo si viene del listado y no de otra acción
        if (url()->previous() && !str_contains(url()->previous(), '/documentos/')) {
            session(['return_to_listado' => url()->previous()]);
        }

        // Si está referenciado por una nota de crédito o hace referencia a una
        $referencias = [
            'referencia' => $documento->referencia,
            'referenciadoPor' => $documento->referenciados,
        ];

        // Cargar proveedores disponibles
        $proveedores = \App\Models\Proveedor::orderBy('razon_social')
            ->get(['id', 'razon_social', 'rut']);

        $cobranzas = \App\Models\Cobranza::orderBy('razon_social')
            ->get(['id', 'razon_social', 'rut_cliente']);

        // Bancos disponibles para nuevas operaciones Factoring
        $bancos = \App\Models\Banco::orderBy('nombre')
            ->get(['id', 'nombre']);

        /*
        |--------------------------------------------------------------------------
        | Cesiones Factoring vigentes
        |--------------------------------------------------------------------------
        | Se envían también al detalle porque desde esta vista se abre el mismo
        | modal de cambio de estado / Factoring individual.
        |--------------------------------------------------------------------------
        */
        $cesionesFactoringExistentes = CesionFactory::query()
            ->where('estado_operacion', 'Vigente')
            ->orderByDesc('created_at')
            ->get([
                'id',
                'cesion',
                'banco_id',
                'fecha_operacion',
                'comision_total',
                'monto_a_recibir',
                'estado_operacion',
            ])
            ->map(function ($cesionFactory) {
                return [
                    'id' => $cesionFactory->id,
                    'cesion' => $cesionFactory->cesion,
                    'banco_id' => $cesionFactory->banco_id,
                    'fecha_operacion' => $cesionFactory->fecha_operacion
                        ? \Carbon\Carbon::parse($cesionFactory->fecha_operacion)->format('Y-m-d')
                        : null,
                    'comision_total' => (int) $cesionFactory->comision_total,
                    'monto_a_recibir' => (int) ($cesionFactory->monto_a_recibir ?? 0),
                    'estado_operacion' => $cesionFactory->estado_operacion,
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Línea de tiempo de movimientos de gestión
        |--------------------------------------------------------------------------
        | Se incorporan todos los Factorings vigentes del documento.
        |--------------------------------------------------------------------------
        */
        $movimientosGestion = collect();

        foreach ($documento->abonos as $abono) {
            $movimientosGestion->push([
                'tipo' => 'Abono',
                'fecha' => $abono->fecha_abono,
                'created_at' => $abono->created_at,
                'monto' => (int) $abono->monto,
                'registro' => $abono,
            ]);
        }

        foreach ($documento->cruces as $cruce) {
            $movimientosGestion->push([
                'tipo' => 'Cruce',
                'fecha' => $cruce->fecha_cruce,
                'created_at' => $cruce->created_at,
                'monto' => (int) $cruce->monto,
                'registro' => $cruce,
            ]);
        }

        foreach ($documento->factories as $factory) {
            $movimientosGestion->push([
                'tipo' => 'Factoring',
                'fecha' => $factory->fecha_factory,
                'created_at' => $factory->created_at,
                'monto' => null,
                'monto_cedido' => (int) ($factory->monto ?? 0),
                'saldo_liquido' => (int) ($factory->saldo_liquido ?? 0),
                'monto_no_anticipado' => (int) ($factory->monto_no_anticipado ?? 0),
                'diferencia_precio' => (int) ($factory->diferencia_precio ?? 0),
                'cesion' => $factory->cesion,
                'registro' => $factory,
            ]);
        }

        foreach ($documento->prontoPagos as $prontoPago) {
            $movimientosGestion->push([
                'tipo' => 'Pronto pago',
                'fecha' => $prontoPago->fecha_pronto_pago,
                'created_at' => $prontoPago->created_at,
                'monto' => null,
                'registro' => $prontoPago,
            ]);
        }

        foreach ($documento->pagos as $pago) {
            $movimientosGestion->push([
                'tipo' => 'Pago',
                'fecha' => $pago->fecha_pago,
                'created_at' => $pago->created_at,
                'monto' => null,
                'registro' => $pago,
            ]);
        }

        $movimientosGestion = $movimientosGestion
            ->filter(fn ($item) => !empty($item['fecha']))
            ->sortBy(function ($item) {
                $fechaCreacion = !empty($item['created_at'])
                    ? \Carbon\Carbon::parse($item['created_at'])->format('Y-m-d H:i:s')
                    : null;

                $fechaMovimiento = \Carbon\Carbon::parse($item['fecha'])
                    ->format('Y-m-d') . ' 00:00:00';

                return $fechaCreacion ?? $fechaMovimiento;
            })
            ->values();

        return view('cobranzas.detalles', compact(
            'documento',
            'referencias',
            'proveedores',
            'cobranzas',
            'bancos',
            'cesionesFactoringExistentes',
            'movimientosGestion'
        ));
    }



    // Almacenamiento de estados relacionados (abonos y cruces)
    public function storeAbono(Request $request, DocumentoFinanciero $documento)
    {
        $request->validate([
            'monto' => 'required|integer|min:1',
            'fecha_abono' => 'required|date|before_or_equal:today',
        ], [
            'fecha_abono.before_or_equal' => 'La fecha del abono no debe sobrepasar la fecha actual.',
            'fecha_abono.required' => 'La fecha del abono es obligatoria.',
        ]);

        // Validar que el abono no supere el saldo pendiente
        $saldoPendiente = $documento->saldo_pendiente; // usa el accessor del modelo

        if ($request->monto > $saldoPendiente) {
            return back()
                ->withErrors(['monto' => 'El abono no puede ser mayor al saldo pendiente actual.'])
                ->withInput();
        }


        // Guardar el abono
        $documento->abonos()->create([
            'monto' => $request->monto,
            'fecha_abono' => $request->fecha_abono,
        ]);



        // Recalcular saldo pendiente en BD
        $documento->recalcularSaldoPendiente();


        // Actualizar estado del documento
        $documento->update([
            'status' => 'Abono',
            'fecha_estado_manual' => now(),
        ]);

        // Registrar movimiento
        MovimientoDocumento::create([
            'documento_financiero_id' => $documento->id,
            'user_id' => Auth::id(),
            'tipo_movimiento' => 'Abono registrado',
            'descripcion' => "Se registró un abono de {$request->monto} el {$request->fecha_abono}",
            'datos_nuevos' => ['monto' => $request->monto, 'fecha_abono' => $request->fecha_abono],
        ]);

        return back()->with('success', 'Abono registrado correctamente.');
    }

    public function storeCruce(Request $request, DocumentoFinanciero $documento)
    {
        $request->validate([
            'monto' => 'required|integer|min:1',
            'fecha_cruce' => 'required|date|before_or_equal:today',
        ], [
            'fecha_cruce.before_or_equal' => 'La fecha del cruce no debe sobrepasar la fecha actual.',
            'fecha_cruce.required' => 'La fecha del cruce es obligatoria.',
        ]);

        $documento->loadMissing('cobranza');

        if (!$documento->cobranza_id) {
            return back()
                ->withErrors(['cobranza_id' => 'El documento no tiene una cobranza asociada.'])
                ->withInput();
        }

        $saldoPendiente = $documento->saldo_pendiente;

        if ($request->monto > $saldoPendiente) {
            return back()
                ->withErrors(['monto' => 'El cruce no puede ser mayor al saldo pendiente actual.'])
                ->withInput();
        }

        $cruce = $documento->cruces()->create([
            'monto' => $request->monto,
            'fecha_cruce' => $request->fecha_cruce,
            'cobranza_id' => $documento->cobranza_id,
        ]);

        $documento->recalcularSaldoPendiente();

        $documento->update([
            'status' => 'Cruce',
            'fecha_estado_manual' => now(),
        ]);

        \App\Models\MovimientoDocumento::create([
            'documento_financiero_id' => $documento->id,
            'user_id' => Auth::id(),
            'tipo_movimiento' => 'Cruce registrado',
            'descripcion' => "Se registró un cruce de {$request->monto} el {$request->fecha_cruce} con la cobranza asociada {$documento->cobranza?->razon_social}.",
            'datos_nuevos' => [
                'monto' => $request->monto,
                'fecha_cruce' => $request->fecha_cruce,
                'cobranza_id' => $documento->cobranza_id,
                'cobranza_razon_social' => $documento->cobranza?->razon_social,
            ],
        ]);

        return back()->with('success', 'Cruce registrado correctamente.');
    }



    private function resolverFechaCorte(Request $request): ?Carbon
    {
        if (!$request->filled('fecha_corte')) {
            return null;
        }

        return Carbon::parse($request->input('fecha_corte'))->endOfDay();
    }



    private function aplicarFiltroFechaCorte(Builder $query, ?Carbon $fechaCorte): void
    {
        if (!$fechaCorte) {
            return;
        }

        $query->whereDate('fecha_docto', '<=', $fechaCorte->toDateString());
    }


    public function exportAlCorte(Request $request)
    {
        $usuariosFinanzas = [1, 405, 374, 375];

        if (!in_array(Auth::id(), $usuariosFinanzas)) {
            abort(403, 'Acceso denegado. No tienes permiso para exportar este módulo.');
        }

        $fechaCorte = $this->resolverFechaCorte($request);

        if (!$fechaCorte) {
            return back()->with('error', 'Debe seleccionar una fecha de corte para generar este archivo.');
        }

        $nombreArchivo = 'Cuentas_Por_Cobrar_Al_Corte_' . $fechaCorte->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new DocumentosAlCorteExport($request, $fechaCorte),
            $nombreArchivo
        );
    }





    // Métodos privados
    private function aplicarFiltrosGeneralesListado(Builder $query, Request $request): void
    {
        if ($request->filled('razon_social')) {
            $this->aplicarFiltroRazonSocial($query, $request->input('razon_social'));
        }

        if ($request->filled('rut_cliente')) {
            $query->where('rut_cliente', 'like', "%{$request->rut_cliente}%");
        }

        if ($request->filled('folio')) {
            $query->where('folio', 'like', "%{$request->folio}%");
        }

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha_docto', [$request->fecha_inicio, $request->fecha_fin]);
        } elseif ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_docto', '>=', $request->fecha_inicio);
        } elseif ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_docto', '<=', $request->fecha_fin);
        }

        if ($request->filled('vencimiento_inicio') && $request->filled('vencimiento_fin')) {
            $query->whereBetween('fecha_vencimiento', [$request->vencimiento_inicio, $request->vencimiento_fin]);
        } elseif ($request->filled('vencimiento_inicio')) {
            $query->whereDate('fecha_vencimiento', '>=', $request->vencimiento_inicio);
        } elseif ($request->filled('vencimiento_fin')) {
            $query->whereDate('fecha_vencimiento', '<=', $request->vencimiento_fin);
        }

        if ($request->filled('saldo_valor')) {
            $valor = (float) str_replace(['.', ','], '', $request->saldo_valor);
            $tipoSaldo = $request->input('saldo_tipo', 'saldo_pendiente');

            if (!in_array($tipoSaldo, ['saldo_pendiente', 'monto_total'], true)) {
                $tipoSaldo = 'saldo_pendiente';
            }

            $query->whereBetween($tipoSaldo, [$valor - 1, $valor + 1]);
        }

        if ($request->filled('estado_pago')) {
            if ($request->estado_pago === 'Pagado') {
                $query->where('saldo_pendiente', '<=', 0);
            }

            if ($request->estado_pago === 'Pendiente') {
                $query->where('saldo_pendiente', '>', 0);
            }
        }
    }

    private function aplicarFiltrosColumnasListado(Builder $query, Request $request): void
    {
        if ($request->filled('cf_empresa_id')) {
            $query->where('empresa_id', $request->input('cf_empresa_id'));
        }

        if ($request->filled('cf_tipo_documento_id')) {
            $query->where('tipo_documento_id', $request->input('cf_tipo_documento_id'));
        }

        if ($request->filled('cf_rut_cliente')) {
            $query->where('rut_cliente', 'like', '%' . $request->input('cf_rut_cliente') . '%');
        }

        if ($request->filled('cf_razon_social')) {
            $this->aplicarFiltroRazonSocial($query, $request->input('cf_razon_social'));
        }

        if ($request->filled('cf_folio')) {
            $query->where('folio', 'like', '%' . $request->input('cf_folio') . '%');
        }

        if ($request->filled('cf_fecha_docto')) {
            $query->whereDate('fecha_docto', $request->input('cf_fecha_docto'));
        }

        if ($request->filled('cf_fecha_vencimiento')) {
            $query->whereDate('fecha_vencimiento', $request->input('cf_fecha_vencimiento'));
        }

        if ($request->filled('cf_monto_total')) {
            $valor = (float) str_replace(['.', ','], '', $request->input('cf_monto_total'));
            $query->where('monto_total', $valor);
        }
    }

    private function aplicarFiltroRazonSocial(Builder $query, string $busqueda): void
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
            'razon_social',
            'rut_cliente',
            'folio',
            'fecha_docto',
            'fecha_vencimiento',
            'monto_total',
            'empresa_id',
            'tipo_documento_id',
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
