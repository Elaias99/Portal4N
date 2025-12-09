<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentoCompra;
use App\Imports\ComprasImport;
use App\Models\Empresa;
use App\Models\TipoDocumento;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use App\Exports\DocumentoCompraExport;
use App\Models\MovimientoCompra;
use Illuminate\Support\Facades\DB;


class DocumentoCompraController extends Controller
{
    /**
     * Muestra todos los registros de compras
     */
    public function index(Request $request)
    {
        // === BASE QUERY con relaciones ===
        $baseQuery = \App\Models\DocumentoCompra::with([
            'empresa',
            'tipoDocumento',
            'movimientos',
            'abonos',
            'cruces',
            'pagos',
            'prontoPagos'
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


        if ($request->filled('saldo_pendiente')) {
            // Normaliza número (quita puntos y comas)
            $valor = (float) str_replace(['.', ','], '', $request->saldo_pendiente);

            // Coincidencia exacta o muy cercana (± 1 peso)
            $baseQuery->whereBetween('saldo_pendiente', [$valor - 1, $valor + 1]);
        }

        // === CLONAR PARA CONTAR ESTADOS ===
        $queryAlDia = (clone $baseQuery)->where('status_original', 'Al día');
        $queryVencido = (clone $baseQuery)->where('status_original', 'Vencido');

        $totalAlDia = $queryAlDia->count();
        $totalVencido = $queryVencido->count();

        // === OBTENER DOCUMENTOS ===
        $documentosOriginal = $baseQuery->orderBy('fecha_vencimiento', 'desc')->get();

        // === ACTUALIZAR ESTADO AUTOMÁTICO ===
        // === ACTUALIZAR ESTADO AUTOMÁTICO (optimizado) ===
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


        // === FILTRO POR ESTADO DE PAGO ===
        $documentosCompras = $documentosOriginal;
        if ($request->filled('estado_pago')) {
            $documentosCompras = $documentosOriginal->filter(function ($doc) use ($request) {
                if ($request->estado_pago === 'Pagado') {
                    return $doc->saldo_pendiente <= 0;
                }
                if ($request->estado_pago === 'Pendiente') {
                    return $doc->saldo_pendiente > 0;
                }
                return true;
            });
        }

        // === RECALCULAR TOTALES TRAS FILTRO ===
        $totalPagados = $documentosCompras->filter(fn($d) => $d->saldo_pendiente <= 0)->count();
        $totalPendientes = $documentosCompras->filter(fn($d) => $d->saldo_pendiente > 0)->count();

        // === SALDO PENDIENTE GLOBAL ===
        $totalSaldoPendiente = $documentosCompras
            ->filter(function ($doc) {
                if (in_array($doc->tipo_documento_id, [61, 56])) return false;
                if ($doc->saldo_pendiente <= 0) return false;
                return true;
            })
            ->sum('saldo_pendiente');

        // === PAGINACIÓN MANUAL ===
        $page = $request->get('page', 1);
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        $itemsPaginated = $documentosCompras->slice($offset, $perPage)->values();

        $documentosCompras = new \Illuminate\Pagination\LengthAwarePaginator(
            $itemsPaginated,
            $documentosCompras->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // === LISTAS AUXILIARES ===
        $proveedores = \App\Models\Proveedor::select('id', 'razon_social', 'rut')->orderBy('razon_social')->get();
        $tiposDocumento = \App\Models\TipoDocumento::orderBy('nombre')->get();
        $empresas = \App\Models\Empresa::orderBy('Nombre')->get();

        // === RETORNAR VISTA ===
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




        // 🔍 Extraer el RUT del nombre del archivo (ej: RCV_COMPRA_REGISTRO_77639015-1_202510)
        $rut = null;
        if (preg_match('/(\d{7,8}-[0-9Kk])/', $filename, $matches)) {
            $rut = $matches[1];
        }

        // 🧩 Normalizar formato: quitar puntos, guiones y espacios
        $rutLimpio = null;
        if ($rut) {
            $rutLimpio = str_replace(['.', '-', ' '], '', $rut);
        }

        // 🏢 Buscar empresa con ese RUT normalizado
        $empresa = null;
        if ($rutLimpio) {
            $empresa = \App\Models\Empresa::whereRaw("
                REPLACE(REPLACE(REPLACE(rut, '.', ''), '-', ''), ' ', '') = ?
            ", [$rutLimpio])->first();
        }

        // 🟢 Si no se encontró empresa, evita romper el flujo
        if (!$empresa) {
            return redirect()->back()->with('error', "No se encontró ninguna empresa asociada al RUT {$rut} (archivo: {$filename}).");
        }

        // ✅ Importar vinculando a la empresa encontrada
        $import = new ComprasImport($empresa->id);
        Excel::import($import, $request->file('file'));
        
        $totalImportados = $import->nuevos;
        $totalDuplicados = count($import->duplicados);

        if (count($import->sugerenciasNotas) > 0) {
            session()->put('sugerencias_notas_compras', $import->sugerenciasNotas);
        }
        // dd(session('sugerencias_notas_compras'));

        
        // Cobranzas faltantes
        if (count($import->sinCobranza) > 0) {

            $mensajes = [];

            foreach ($import->sinCobranza as $item) {
                $mensajes[] = "No existe cobranza para la razón social '{$item['razon_social']}' (RUT: {$item['rut_proveedor']}), 
                folio {$item['folio']}. <a href='#' 
                class='btn-link text-primary crear-compra-link' 
                data-rut='{$item['rut_proveedor']}' 
                data-razon='{$item['razon_social']}'>
                Cree la cobranza aquí</a>";
            }

            // Guardamos los pendientes para el flujo guiado
            session([
                'sin_compra_pendientes' => $import->sinCobranza
            ]);

            // Opcional: limpiar las sesiones del otro flujo
            session()->forget('sin_cobranza');
            session()->forget('sin_cobranza_pendientes');

        } else {
            session()->forget('sin_compra_pendientes');
        }

        // Registrar movimiento si hubo importaciones exitosas
        if ($totalImportados > 0) {
            \App\Models\MovimientoCompra::create([
                'documento_compra_id' => null,
                'usuario_id' => Auth::id(),
                'tipo_movimiento' => 'Importación masiva',
                'descripcion' => "Se importaron {$totalImportados} documentos de compra desde el archivo '{$filename}' el " . now()->format('d/m/Y H:i:s'),
                'datos_nuevos' => [
                    'archivo' => $filename,
                    'total_importados' => $totalImportados,
                    'total_duplicados' => $totalDuplicados,
                    'empresa_id' => $empresa->id,
                    'empresa_nombre' => $empresa->razon_social ?? null,
                ],
                'fecha_cambio' => now(),
            ]);
        }




        // Caso 1: Importación exitosa, sin duplicados
        if ($totalImportados > 0 && $totalDuplicados === 0) {
            return redirect()->route('finanzas_compras.index')->with('success', "Archivo importado correctamente.");
        }

        //  Caso 2: Todo duplicado (no se importó nada)
        if ($totalImportados === 0 && $totalDuplicados > 0) {
            return redirect()->route('finanzas_compras.index')->with([
                'warning' => "Todos los registros del archivo ya existían. No se importó ningún registro nuevo.",
                'detalles_errores' => $import->duplicados
            ]);
        }

        // Caso 3: Mezcla (algunos nuevos y otros duplicados)
        if ($totalImportados > 0 && $totalDuplicados > 0) {
            return redirect()->route('finanzas_compras.index')->with([
                'success' => "Se importaron {$totalImportados} registros nuevos.",
                'warning' => "Se detectaron {$totalDuplicados} folios duplicados que no fueron importados.",
                'detalles_errores' => $import->duplicados
            ]);
        }

        // Caso 4: Archivo vacío o sin registros válidos
        return redirect()->route('finanzas_compras.index')->with('error', 'No se encontraron registros válidos para importar.');
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
        foreach ($request->referencia as $notaId => $facturaId) {

            DocumentoCompra::where('id', $notaId)->update([
                'referencia_id' => $facturaId
            ]);
        }

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
        $query->whereNotIn('tipo_documento_id', [61, 56]);

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

        // Cargar proveedores para los posibles cruces
        $proveedores = \App\Models\Proveedor::orderBy('razon_social')->get(['id', 'razon_social', 'rut']);

        return view('cobranzas.finanzas_compras.detalles', compact('documento', 'proveedores'));
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
        $request->validate([
            'monto' => 'required|integer|min:1',
            'fecha_cruce' => 'required|date|before_or_equal:today',
            'proveedor_id' => 'required|exists:proveedores,id',
        ], [
            'fecha_cruce.before_or_equal' => 'La fecha del cruce no debe sobrepasar la fecha actual.',
            'fecha_cruce.required' => 'La fecha del cruce es obligatoria.',
            'proveedor_id.required' => 'Debe seleccionar un proveedor.',
            'proveedor_id.exists' => 'El proveedor seleccionado no es válido.',
        ]);

        // 1️⃣ Validar límite del saldo
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

        // 3️⃣ Registrar cruce
        $cruce = $documento->cruces()->create([
            'monto' => $request->monto,
            'fecha_cruce' => $request->fecha_cruce,
            'proveedor_id' => $request->proveedor_id,
        ]);

        // 4️⃣ Recalcular saldo pendiente
        $documento->recalcularSaldoPendiente();

        // 5️⃣ Actualizar estado manual
        $documento->update([
            'estado' => 'Cruce',
            'fecha_estado_manual' => now(),
        ]);

        // 6️⃣ Registrar movimiento con trazabilidad extendida
        MovimientoCompra::create([
            'documento_compra_id' => $documento->id,
            'usuario_id' => Auth::id(),
            'estado_anterior' => $datosAnteriores['estado'],
            'nuevo_estado' => 'Cruce',
            'fecha_cambio' => now(),
            'tipo_movimiento' => 'Registro de cruce',
            'descripcion' => "Se registró un cruce de {$request->monto} el {$request->fecha_cruce} con proveedor ID {$request->proveedor_id}.",
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos' => [
                'monto' => $request->monto,
                'fecha_cruce' => $request->fecha_cruce,
                'proveedor_id' => $request->proveedor_id,
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
