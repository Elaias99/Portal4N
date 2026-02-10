<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentoCompra;
use App\Imports\ComprasImport;
use App\Models\Empresa;
use App\Models\CobranzaCompra;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use App\Exports\DocumentoCompraExport;
use App\Models\MovimientoCompra;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Banco;
use App\Models\TipoCuenta;
use App\Services\ReferenciaNotasCompraService;


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
            ])
            ->with([
                'empresa:id,Nombre',
                'tipoDocumento:id,nombre',
                'referencia:id,folio',
                'referenciados:id,folio,referencia_id,tipo_documento_id,monto_total',
                'abonos:id,documento_compra_id,fecha_abono',
                'cruces:id,documento_compra_id,fecha_cruce',
                'pagos:id,documento_compra_id,fecha_pago',
                'prontoPagos:id,documento_compra_id,fecha_pronto_pago'
            ]);

        // === FILTROS GENERALES ===
        if ($request->filled('rut_proveedor')) {
            $baseQuery->where('rut_proveedor', 'like', "%{$request->rut_proveedor}%");
        }

        if ($request->filled('razon_social')) {
            $baseQuery->where('razon_social', 'like', "%{$request->razon_social}%");
        }

        if ($request->filled('folio')) {
            $baseQuery->where('folio', 'like', "%{$request->folio}%");
        }

        if ($request->filled('empresa_id')) {
            $baseQuery->where('empresa_id', $request->empresa_id);
        }

        if ($request->filled('estado')) {
            $baseQuery->where(function ($q) use ($request) {
                $q->where('status_original', $request->estado)
                ->orWhere('estado', $request->estado);
            });
        }

        // === FILTROS DE FECHAS ===
        if ($request->filled('fecha_docto_inicio') && $request->filled('fecha_docto_fin')) {
            $baseQuery->whereBetween('fecha_docto', [$request->fecha_docto_inicio, $request->fecha_docto_fin]);
        } elseif ($request->filled('fecha_docto_inicio')) {
            $baseQuery->whereDate('fecha_docto', '>=', $request->fecha_docto_inicio);
        } elseif ($request->filled('fecha_docto_fin')) {
            $baseQuery->whereDate('fecha_docto', '<=', $request->fecha_docto_fin);
        }

        if ($request->filled('fecha_venc_inicio') && $request->filled('fecha_venc_fin')) {
            $baseQuery->whereBetween('fecha_vencimiento', [$request->fecha_venc_inicio, $request->fecha_venc_fin]);
        } elseif ($request->filled('fecha_venc_inicio')) {
            $baseQuery->whereDate('fecha_vencimiento', '>=', $request->fecha_venc_inicio);
        } elseif ($request->filled('fecha_venc_fin')) {
            $baseQuery->whereDate('fecha_vencimiento', '<=', $request->fecha_venc_fin);
        }


        if ($request->filled('saldo_valor')) {

            // 1️⃣ Normalizar número (quita puntos y comas)
            $valor = (float) str_replace(['.', ','], '', $request->saldo_valor);

            // 2️⃣ Determinar tipo de saldo (default: saldo_pendiente)
            $tipoSaldo = $request->input('saldo_tipo', 'saldo_pendiente');

            // 3️⃣ Whitelist de columnas permitidas
            $columnasPermitidas = [
                'saldo_pendiente',
                'monto_total',
            ];

            // 4️⃣ Fallback de seguridad
            if (!in_array($tipoSaldo, $columnasPermitidas, true)) {
                $tipoSaldo = 'saldo_pendiente';
            }

            // 5️⃣ Aplicar filtro con tolerancia ±1
            $baseQuery->whereBetween($tipoSaldo, [
                $valor - 1,
                $valor + 1
            ]);
        }

        // === FILTRO POR ESTADO DE PAGO ===
        if ($request->filled('estado_pago')) {
            if ($request->estado_pago === 'Pagado') {
                $baseQuery->where('saldo_pendiente', '<=', 0);
            }
            if ($request->estado_pago === 'Pendiente') {
                $baseQuery->where('saldo_pendiente', '>', 0);
            }
        }

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
        $totalAlDia = (clone $baseQuery)->where('status_original', 'Al día')->count();
        $totalVencido = (clone $baseQuery)->where('status_original', 'Vencido')->count();

        // === CONTAR PAGADOS / PENDIENTES ===
        $totalPagados = (clone $baseQuery)->where('saldo_pendiente', '<=', 0)->count();
        $totalPendientes = (clone $baseQuery)->where('saldo_pendiente', '>', 0)->count();

        // === SALDO PENDIENTE GLOBAL ===
        $totalSaldoPendiente = (clone $baseQuery)
            ->whereNotIn('tipo_documento_id', [61, 56])
            ->where('saldo_pendiente', '>', 0)
            ->sum('saldo_pendiente');

        // === PAGINACIÓN ===
        $documentosCompras = $baseQuery->orderBy('fecha_vencimiento', 'desc')->paginate(10);

        // === LISTAS AUXILIARES ===
        $proveedores = \App\Models\Proveedor::select('id', 'razon_social', 'rut')->orderBy('razon_social')->get();


        $cobranzasCompras = CobranzaCompra::select('id', 'razon_social', 'rut_cliente')
        ->orderBy('razon_social')
        ->get();

        $tiposDocumento = \App\Models\TipoDocumento::orderBy('nombre')->get();
        $empresas = \App\Models\Empresa::orderBy('Nombre')->get();

        $bancos = Banco::orderBy('nombre')->get();
        $tipoCuentas = TipoCuenta::orderBy('nombre')->get();


        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// 
        if (session()->has('sugerencias_notas_compras')) {
            foreach (session('sugerencias_notas_compras') as $i => $item) {
                Log::info("🧪 SUGERENCIA {$i}", [
                    'nota_id' => $item['nota']->id ?? null,
                    'nota_folio' => $item['nota']->folio ?? null,
                ]);
            }
        }

        if (session()->has('sugerencias_notas_compras')) {
            foreach (session('sugerencias_notas_compras') as $item) {
                $id = $item['nota']->id ?? null;

                $existe = $id
                    ? \App\Models\DocumentoCompra::where('id', $id)->exists()
                    : false;

                Log::info('🧪 VALIDACION NOTA', [
                    'nota_id' => $id,
                    'existe_en_bd' => $existe,
                ]);
            }
        }

        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        return view('cobranzas.finanzas_compras.index', compact(
            'documentosCompras',
            'proveedores',          // si aún se usa en otra parte
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
        ));
    }


    public function filtrar(Request $request)
    {
        // === 1️⃣ Validación de parámetros ===
        $columna = $request->get('columna');
        $valor   = $request->get('valor');

        // Validar que la columna sea permitida (evita SQL injection)
        $columnasPermitidas = [
            'empresa_id', 'tipo_documento_id', 'rut_proveedor', 'razon_social',
            'folio', 'fecha_docto', 'fecha_vencimiento', 'monto_total',
            'status_original', 'estado', 'fecha_estado_manual'
        ];

        if ($columna && !in_array($columna, $columnasPermitidas)) {
            return redirect()
                ->route('finanzas_compras.index')
                ->with('error', "El filtro por columna '{$columna}' no está permitido.");
        }
        // === 2️⃣ Base query con relaciones ===
        $query = DocumentoCompra::with([
            'empresa',
            'tipoDocumento',
            'movimientos',
            'abonos',
            'cruces',
            'pagos',
            'prontoPagos'
        ]);

        // === 3️⃣ Filtro dinámico según tipo de campo ===
        if (!empty($valor)) {
            switch ($columna) {
                case 'empresa_id':
                    $query->whereHas('empresa', function ($q) use ($valor) {
                        $q->where('Nombre', 'like', "%{$valor}%");
                    });
                    break;

                case 'tipo_documento_id':
                    $query->whereHas('tipoDocumento', function ($q) use ($valor) {
                        $q->where('nombre', 'like', "%{$valor}%");
                    });
                    break;

                case 'fecha_docto':
                case 'fecha_vencimiento':
                case 'fecha_estado_manual':
                    if (strlen($valor) === 10 && preg_match('/\d{4}-\d{2}-\d{2}/', $valor)) {
                        $query->whereDate($columna, $valor);
                    } else {
                        // Si no es formato exacto de fecha, intentar búsqueda parcial (mes o año)
                        $query->where($columna, 'like', "%{$valor}%");
                    }
                    break;

                case 'monto_total':
                    // Permitir búsqueda por monto exacto o rango simple
                    if (is_numeric($valor)) {
                        $query->where('monto_total', '>=', (int)$valor);
                    }
                    break;

                default:
                    $query->where($columna, 'like', "%{$valor}%");
                    break;
            }
        }

        // === 4️⃣ Ordenamiento ===
        if ($request->filled('sort_by')) {
            $sortBy = $request->get('sort_by');
            $sortOrder = $request->get('sort_order', 'asc');
            if (in_array($sortBy, $columnasPermitidas)) {
                $query->orderBy($sortBy, $sortOrder);
            }
        } else {
            $query->latest();
        }

        // === 5️⃣ Obtener resultados ===
        $documentosCompras = $query->get();

        $hoy = \Carbon\Carbon::today();

        // === 6️⃣ Actualizar estado automático (Al día / Vencido)
        foreach ($documentosCompras as $doc) {
            if ($doc->fecha_vencimiento && $doc->saldo_pendiente > 0) {
                $fechaVenc = \Carbon\Carbon::parse($doc->fecha_vencimiento);
                $nuevoEstado = $fechaVenc->lt($hoy) ? 'Vencido' : 'Al día';
                if ($doc->status_original !== $nuevoEstado) {
                    $doc->status_original = $nuevoEstado;
                    $doc->save();
                }
            }
        }

        // === 7️⃣ Totales por estado original y pago ===
        $totalAlDia = DocumentoCompra::where('status_original', 'Al día')->count();
        $totalVencido = DocumentoCompra::where('status_original', 'Vencido')->count();

        $totalPagados = $documentosCompras->filter(fn($d) => $d->saldo_pendiente <= 0)->count();
        $totalPendientes = $documentosCompras->filter(fn($d) => $d->saldo_pendiente > 0)->count();

        // === 8️⃣ Paginación manual (igual que en index)
        $page = $request->get('page', 1);
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        $itemsPaginated = $documentosCompras->slice($offset, $perPage)->values();

        $totalSaldoPendiente = $itemsPaginated
            ->filter(function ($doc) {
                if (in_array($doc->tipo_documento_id, [61, 56])) return false;
                if ($doc->pagos->count() > 0) return false;
                return true;
            })
            ->sum(fn($doc) => $doc->saldo_pendiente);

        $documentosCompras = new \Illuminate\Pagination\LengthAwarePaginator(
            $itemsPaginated,
            $documentosCompras->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // === 9️⃣ Listas auxiliares ===
        $proveedores = \App\Models\Proveedor::select('id', 'razon_social', 'rut')->orderBy('razon_social')->get();
        $tiposDocumento = \App\Models\TipoDocumento::orderBy('nombre')->get();
        $empresas = \App\Models\Empresa::orderBy('Nombre')->get();

        // === 🔟 Devolver vista ===
        return view('cobranzas.finanzas_compras.index', compact(
            'documentosCompras',
            'proveedores',
            'tiposDocumento',
            'empresas',
            'totalAlDia',
            'totalVencido',
            'totalPagados',
            'totalPendientes',
            'totalSaldoPendiente'
        ));
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

        // 🔍 Extraer RUT desde el nombre del archivo
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

        // ✅ Importación
        $import = new ComprasImport($empresa->id);
        Excel::import($import, $request->file('file'));

        /*
        |--------------------------------------------------------------------------
        | 🔁 BLOQUE AGREGADO: reprocesar sugerencias DESPUÉS del import completo
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

            Log::info('🧪 [POST-IMPORT] Evaluando NC para sugerencias', [
                'nota_id' => $nota->id,
                'folio' => $nota->folio,
                'rut_proveedor' => $nota->rut_proveedor,
            ]);

            // 🟠 NC sin cobranza → no se sugiere nada (correcto)
            if (!$nota->cobranza_compra_id) {
                Log::info('🟠 [POST-IMPORT] NC sin cobranza, se omite sugerencias', [
                    'nota_id' => $nota->id,
                    'folio' => $nota->folio,
                    'rut_proveedor' => $nota->rut_proveedor,
                ]);
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

            Log::info('🧪 [POST-IMPORT] Sugerencias guardadas en sesión', [
                'count' => count($sugerenciasPostImport),
                'notas_ids' => collect($sugerenciasPostImport)->pluck('nota.id')->toArray(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 🔚 FIN BLOQUE AGREGADO
        |--------------------------------------------------------------------------
        */

        // ======================
        // Lo que YA TENÍAS
        // ======================

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

        $nota = DocumentoCompra::find($request->nota_id);

        // Guardar referencia
        $nota->referencia_id = $request->factura_id;
        $nota->save();

        // limpiar las sugerencias (para que no reaparezca el modal)
        session()->forget('sugerencias_notas_compras');

        return redirect()->route('finanzas_compras.index')
            ->with('success', 'Referencia asignada correctamente.');
    }

    public function asignarReferencias(Request $request)
    {
        $referencias = $request->input('referencia');

        // 🟡 Caso: no se seleccionó ninguna referencia
        if (empty($referencias) || !is_array($referencias)) {

            // No se referencia nada, solo se cierra el flujo
            session()->forget('sugerencias_notas_compras');

            return response()->json([
                'success' => true,
                'message' => 'No había referencias para asignar'
            ]);
        }

        // 🟢 Caso: sí hay referencias seleccionadas
        foreach ($referencias as $notaId => $facturaId) {
            DocumentoCompra::where('id', $notaId)->update([
                'referencia_id' => $facturaId
            ]);
        }

        // Limpiar sesión SIEMPRE
        session()->forget('sugerencias_notas_compras');

        return response()->json(['success' => true]);
    }


    public function export(Request $request)
    {
        $perPage = 10;
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $perPage;

        // === 1️⃣ Query base ===
        $query = DocumentoCompra::with([
            'empresa',
            'tipoDocumento',
            'movimientos',
            'abonos',
            'cruces',
            'pagos',
            'prontoPagos'
        ]);

        // === 2️⃣ Filtros principales ===
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

        // === 3️⃣ Filtros de fechas ===
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

        // === 4️⃣ Filtros personalizados (filtrarColumnas) ===
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

        // === 5️⃣ Filtro de estado de pago (directo en SQL) ===
        if ($request->filled('estado_pago')) {
            if ($request->estado_pago === 'Pagado') {
                $query->where('saldo_pendiente', '<=', 0);
            } elseif ($request->estado_pago === 'Pendiente') {
                $query->where('saldo_pendiente', '>', 0);
            }
        }

        // === 6️⃣ Excluir notas de crédito / anulados ===
        $query->whereNotIn('tipo_documento_id', [61, 56]);

        // === 7️⃣ Orden ===
        if ($request->filled('sort_by')) {
            $sortBy = $request->get('sort_by', 'razon_social');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('fecha_vencimiento', 'desc');
        }

        // === 8️⃣ Ejecutar query ===
        $documentos = $query->get();

        // === 9️⃣ Actualizar estado automático ===
        $hoy = \Carbon\Carbon::today();
        foreach ($documentos as $doc) {
            if ($doc->fecha_vencimiento && $doc->saldo_pendiente > 0) {
                $fechaVenc = \Carbon\Carbon::parse($doc->fecha_vencimiento);
                $nuevoEstado = $fechaVenc->lt($hoy) ? 'Vencido' : 'Al día';
                if ($doc->status_original !== $nuevoEstado) {
                    $doc->status_original = $nuevoEstado;
                    $doc->save();
                }
            }
        }

        // === 🔟 Paginación manual ===
        $documentos = $documentos->slice($offset, $perPage)->values();

        // === 11️⃣ Exportación ===
        $fecha = now()->format('Y-m-d_H-i-s');
        return Excel::download(
            new DocumentoCompraExport($documentos),
            "documentos_compras_pagina_{$page}_{$fecha}.xlsx"
        );
    }

    public function exportAll(Request $request)
    {
        // === 1️⃣ Query base ===
        $query = DocumentoCompra::with([
            'empresa',
            'tipoDocumento',
            'movimientos',
            'abonos',
            'cruces',
            'pagos',
            'prontoPagos'
        ]);

        // === 2️⃣ Filtros principales ===
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

        // === 3️⃣ Filtros de fechas ===
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

        // === 4️⃣ Filtros personalizados (filtrarColumnas) ===
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

        // === 5️⃣ Excluir notas de crédito / anulados ===
        // $query->whereNotIn('tipo_documento_id', [61, 56]);

        // === 6️⃣ Orden ===
        if ($request->filled('sort_by')) {
            $sortBy = $request->get('sort_by', 'razon_social');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('fecha_vencimiento', 'desc');
        }

        // === 7️⃣ Ejecutar la query ===
        $documentos = $query->get();

        // === 8️⃣ Filtro de estado de pago (en memoria) ===
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

        // === 9️⃣ Actualizar estado automático (Vencido / Al día) ===
        $hoy = \Carbon\Carbon::today();
        foreach ($documentos as $doc) {
            if ($doc->fecha_vencimiento && $doc->saldo_pendiente > 0) {
                $fechaVenc = \Carbon\Carbon::parse($doc->fecha_vencimiento);
                $nuevoEstado = $fechaVenc->lt($hoy) ? 'Vencido' : 'Al día';
                if ($doc->status_original !== $nuevoEstado) {
                    $doc->status_original = $nuevoEstado;
                    $doc->save();
                }
            }
        }

        // === 🔟 Exportación final ===
        $fecha = now()->format('Y-m-d_H-i-s');
        return Excel::download(
            new DocumentoCompraExport($documentos),
            "documentos_compras_todos_{$fecha}.xlsx"
        );
    }

    public function updateEstado(Request $request, $id)
    {
        // 🧩 Validación básica
        $request->validate([
            'estado' => 'nullable|string|max:50',
        ]);

        // 🔍 Buscar el documento
        $documento = DocumentoCompra::findOrFail($id);

        // 📝 Guardar datos originales antes del cambio
        $datosAnteriores = [
            'estado' => $documento->estado,
            'fecha_estado_manual' => $documento->fecha_estado_manual,
        ];

        // ⚙️ Actualizar el estado manual y fecha
        $documento->update([
            'estado' => $request->estado,
            'fecha_estado_manual' => now(),
        ]);

        // 🧾 Registrar movimiento con trazabilidad extendida
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

        // ✅ Redirigir con mensaje de éxito
        return redirect()
            ->route('finanzas_compras.index')
            ->with('success', 'Estado actualizado correctamente.');
    }

    public function show(DocumentoCompra $documento)
    {
        // Cargar relaciones necesarias para mostrar los detalles del documento
        $documento->load([
            'empresa',
            'abonos',
            'cruces.proveedor', // relación anidada con proveedor
            'pagos',
            'prontoPagos',
            'cobranzaCompra',
        ]);

        // Guardar la URL anterior solo si viene desde el listado
        if (url()->previous() && !str_contains(url()->previous(), '/finanzas/compras/')) {
            session(['return_to_listado' => url()->previous()]);
        }

        $cobranzasCompras = \App\Models\CobranzaCompra::select('id', 'razon_social', 'rut_cliente')
            ->orderBy('razon_social')
            ->get();


        // Cargar proveedores para los posibles cruces
        $proveedores = \App\Models\Proveedor::orderBy('razon_social')->get(['id', 'razon_social', 'rut']);

        return view('cobranzas.finanzas_compras.detalles', compact('documento', 'proveedores',
    'cobranzasCompras'));
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

        // 1️⃣ Validar límite del saldo
        $saldoPendiente = $documento->saldo_pendiente;
        if ($request->monto > $saldoPendiente) {
            return back()
                ->withErrors(['monto' => 'El abono no puede ser mayor al saldo pendiente actual.'])
                ->withInput();
        }

        // 2️⃣ Guardar datos anteriores
        $datosAnteriores = [
            'saldo_pendiente' => $saldoPendiente,
            'estado' => $documento->estado,
        ];

        // 3️⃣ Registrar abono
        $documento->abonos()->create([
            'monto' => $request->monto,
            'fecha_abono' => $request->fecha_abono,
        ]);

        // 4️⃣ Recalcular saldo pendiente
        $documento->recalcularSaldoPendiente();

        // 5️⃣ Actualizar estado manual
        $documento->update([
            'estado' => 'Abono',
            'fecha_estado_manual' => now(),
        ]);

        // 6️⃣ Registrar movimiento con trazabilidad extendida
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
        // ✅ Validación usando cobranza_compras
        $request->validate([
            'monto' => 'required|integer|min:1',
            'fecha_cruce' => 'required|date|before_or_equal:today',
            'cobranza_compra_id' => 'required|exists:cobranza_compras,id',
        ], [
            'fecha_cruce.before_or_equal' => 'La fecha del cruce no debe sobrepasar la fecha actual.',
            'fecha_cruce.required' => 'La fecha del cruce es obligatoria.',
            'cobranza_compra_id.required' => 'Debe seleccionar un cliente.',
            'cobranza_compra_id.exists' => 'El cliente seleccionado no es válido.',
        ]);

        // 1️⃣ Validar que el cruce no supere el saldo pendiente
        $saldoPendiente = $documento->saldo_pendiente;

        if ($request->monto > $saldoPendiente) {
            return back()
                ->withErrors(['monto' => 'El cruce no puede ser mayor al saldo pendiente actual.'])
                ->withInput();
        }

        // 2️⃣ Guardar datos anteriores
        $datosAnteriores = [
            'saldo_pendiente' => $saldoPendiente,
            'estado' => $documento->estado,
        ];

        // 3️⃣ Registrar el cruce (SIN proveedores)
        $cruce = $documento->cruces()->create([
            'monto' => $request->monto,
            'fecha_cruce' => $request->fecha_cruce,
            'cobranza_compra_id' => $request->cobranza_compra_id,
        ]);

        // 4️⃣ Recalcular saldo pendiente
        $documento->recalcularSaldoPendiente();

        // 5️⃣ Actualizar estado manual
        $documento->update([
            'estado' => 'Cruce',
            'fecha_estado_manual' => now(),
        ]);

        // 6️⃣ Registrar movimiento con trazabilidad
        MovimientoCompra::create([
            'documento_compra_id' => $documento->id,
            'usuario_id' => Auth::id(),
            'estado_anterior' => $datosAnteriores['estado'],
            'nuevo_estado' => 'Cruce',
            'fecha_cambio' => now(),
            'tipo_movimiento' => 'Registro de cruce',
            'descripcion' => "Se registró un cruce de {$request->monto} el {$request->fecha_cruce} con cobranza_compra ID {$request->cobranza_compra_id}.",
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos' => [
                'monto' => $request->monto,
                'fecha_cruce' => $request->fecha_cruce,
                'cobranza_compra_id' => $request->cobranza_compra_id,
                'nuevo_saldo' => $documento->saldo_pendiente,
            ],
        ]);

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








}
