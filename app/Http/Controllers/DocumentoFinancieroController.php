<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentoFinanciero;
use App\Models\Empresa;
use App\Models\TipoDocumento;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DocumentosImport;
use App\Models\MovimientoDocumento;
use App\Exports\DocumentosExport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class DocumentoFinancieroController extends Controller
{
    //
    public function index(Request $request)
    {
        $usuariosFinanzas = [1, 405, 374, 375];

        if (!in_array(Auth::id(), $usuariosFinanzas)) {
            abort(403, 'Acceso denegado. No tienes permiso para ingresar a este módulo.');
        }

        $query = DocumentoFinanciero::with(['cobranza', 'empresa', 'abonos', 'cruces', 'referenciados', 'pagos', 'prontoPagos', ]);

        // === FILTROS GENERALES ===
        if ($request->filled('razon_social')) {
            $query->where('razon_social', 'like', "%{$request->razon_social}%");
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

        // === FILTRO FECHA DE VENCIMIENTO ===
        if ($request->filled('vencimiento_inicio') && $request->filled('vencimiento_fin')) {
            $query->whereBetween('fecha_vencimiento', [$request->vencimiento_inicio, $request->vencimiento_fin]);
        } elseif ($request->filled('vencimiento_inicio')) {
            $query->whereDate('fecha_vencimiento', '>=', $request->vencimiento_inicio);
        } elseif ($request->filled('vencimiento_fin')) {
            $query->whereDate('fecha_vencimiento', '<=', $request->vencimiento_fin);
        }

        // === FILTRO POR ESTADO ORIGINAL ===
        if ($request->filled('status')) {
            $query->where('status_original', $request->status);
        }

        // === CONTADORES ===
        $totalAlDia = DocumentoFinanciero::where('status_original', 'Al día')->count();
        $totalVencido = DocumentoFinanciero::where('status_original', 'Vencido')->count();


        // === OBTENER DATOS BASE ===
        $documentoFinancieros = $query
            ->orderByRaw('ISNULL(fecha_vencimiento), fecha_vencimiento DESC')
            ->get();


        $hoy = \Carbon\Carbon::today();

        foreach ($documentoFinancieros as $doc) {
            if ($doc->fecha_vencimiento && $doc->saldo_pendiente > 0) {
                $fechaVenc = \Carbon\Carbon::parse($doc->fecha_vencimiento);

                if ($fechaVenc->lt($hoy) && $doc->status_original !== 'Vencido') {
                    $doc->status_original = 'Vencido';
                    $doc->save();
                } elseif ($fechaVenc->gte($hoy) && $doc->status_original !== 'Al día') {
                    $doc->status_original = 'Al día';
                    $doc->save();
                }
            }
        }

        // === FILTRO POR ESTADO DE PAGO (Pagado / Pendiente) ===
        if ($request->filled('estado_pago')) {
            $documentoFinancieros = $documentoFinancieros->filter(function ($doc) use ($request) {
                if ($request->estado_pago === 'Pagado') {
                    return $doc->saldo_pendiente <= 0;
                }
                if ($request->estado_pago === 'Pendiente') {
                    return $doc->saldo_pendiente > 0;
                }
                return true;
            });
        }

        // === CÁLCULO CORREGIDO DEL SALDO PENDIENTE TOTAL ===
        $totalSaldoPendiente = $documentoFinancieros
            ->filter(function ($doc) {
                // Excluir notas de crédito o débito
                if (in_array($doc->tipo_documento_id, [61, 56])) {
                    return false;
                }

                
                // Excluir documentos con pagos registrados
                if ($doc->pagos->count() > 0) {
                    return false;
                }


                // ✅ Incluir todo lo demás
                return true;
            })
            ->sum(function ($doc) {
                return $doc->saldo_pendiente;
            });

        // === PAGINACIÓN MANUAL ===
        $page = $request->get('page', 1);
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        $itemsPaginated = $documentoFinancieros->slice($offset, $perPage)->values();

        $documentoFinancieros = new \Illuminate\Pagination\LengthAwarePaginator(
            $itemsPaginated,
            $documentoFinancieros->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $totalPagados = $documentoFinancieros->filter(fn($d) => $d->pagos->count() > 0)->count();
        $totalPendientes = $documentoFinancieros->filter(fn($d) => $d->pagos->count() === 0)->count();


        $empresas = Empresa::orderBy('Nombre')->get(['id', 'Nombre']);
        $tiposDocumento = TipoDocumento::orderBy('nombre')->get(['id', 'nombre']);
        $proveedores = \App\Models\Proveedor::orderBy('razon_social')->get(['id', 'razon_social', 'rut']);



        return view('cobranzas.documentos', compact(
            'documentoFinancieros',
            'totalAlDia',
            'totalVencido',
            'totalSaldoPendiente',
            'totalPagados',
            'totalPendientes',
            'empresas',
            'tiposDocumento',
            'proveedores'

        ));

    }




    public function filtrarColumnas(Request $request)
    {
        // 🔒 Control de acceso
        $usuariosFinanzas = [1, 405, 374, 375];
        if (!in_array(Auth::id(), $usuariosFinanzas)) {
            abort(403, 'Acceso denegado.');
        }

        // === BASE QUERY ===
        $query = DocumentoFinanciero::with(['empresa', 'tipoDocumento']);

        // === PARAMETROS DE FILTRO DIRECTO (desde el dropdown) ===
        $columna = $request->get('columna');
        $valor = $request->get('valor');

        // === APLICAR FILTRO DIRECTO SEGÚN EL TIPO DE COLUMNA ===
        if ($columna && $valor) {
            switch ($columna) {
                case 'razon_social':
                case 'rut_cliente':
                case 'folio':
                    $query->where($columna, 'like', "%{$valor}%");
                    break;

                case 'fecha_docto':
                case 'fecha_vencimiento':
                    $query->whereDate($columna, '=', $valor);
                    break;

                case 'monto_total':
                    $query->where($columna, '=', $valor);
                    break;

                case 'empresa_id':
                    $query->where('empresa_id', $valor);
                    break;

                case 'tipo_doc_id':
                    $query->where('tipo_doc_id', $valor);
                    break;
            }
        }

        // === FILTROS GENERALES ===
        if ($request->filled('razon_social')) {
            $query->where('razon_social', 'like', "%{$request->razon_social}%");
        }

        if ($request->filled('rut_cliente')) {
            $query->where('rut_cliente', 'like', "%{$request->rut_cliente}%");
        }

        if ($request->filled('folio')) {
            $query->where('folio', 'like', "%{$request->folio}%");
        }

        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        if ($request->filled('tipo_doc_id')) {
            $query->where('tipo_doc_id', $request->tipo_doc_id);
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

        if ($request->filled('status')) {
            $query->where('status_original', $request->status);
        }

        // === ORDENAMIENTO ===
        $sortBy = $request->get('sort_by', 'razon_social');
        $sortOrder = $request->get('sort_order', 'asc');

        $columnasPermitidas = [
            'razon_social',
            'rut_cliente',
            'folio',
            'fecha_docto',
            'fecha_vencimiento',
            'monto_total',
            'empresa_id',
            'tipo_doc_id',
        ];

        if (!in_array($sortBy, $columnasPermitidas)) {
            $sortBy = 'razon_social';
        }

        if (!in_array(strtolower($sortOrder), ['asc', 'desc'])) {
            $sortOrder = 'asc';
        }

        // === CONSULTA FINAL ===
        $documentoFinancieros = $query->orderBy($sortBy, $sortOrder)->paginate(10);

        // === CALCULOS AUXILIARES ===
        $totalSaldoPendiente = $documentoFinancieros->sum(fn($d) => $d->saldo_pendiente);
        $totalPagados = $documentoFinancieros->filter(fn($d) => $d->saldo_pendiente <= 0)->count();
        $totalPendientes = $documentoFinancieros->filter(fn($d) => $d->saldo_pendiente > 0)->count();

        // ✅ Variables adicionales para mantener coherencia con index()
        $totalAlDia = DocumentoFinanciero::where('status_original', 'Al día')->count();
        $totalVencido = DocumentoFinanciero::where('status_original', 'Vencido')->count();

        $empresas = Empresa::orderBy('Nombre')->get(['id', 'Nombre']);
        $tiposDocumento = TipoDocumento::orderBy('nombre')->get(['id', 'nombre']);
        $proveedores = \App\Models\Proveedor::orderBy('razon_social')->get(['id', 'razon_social', 'rut']); // ✅ añadido

        // === RENDERIZAR VISTA ===
        return view('cobranzas.documentos', compact(
            'documentoFinancieros',
            'totalSaldoPendiente',
            'totalPagados',
            'totalPendientes',
            'totalAlDia',
            'totalVencido',
            'sortBy',
            'sortOrder',
            'empresas',
            'tiposDocumento',
            'proveedores' // ✅ añadido
        ));
    }



    public function general(Request $request)
    {
        // 🚫 Restricción de acceso solo para usuario 405
        $usuariosFinanzas = [1, 405, 374, 375];

        if (!in_array(Auth::id(), $usuariosFinanzas)) {
            abort(403, 'Acceso denegado. No tienes permiso para ingresar a este módulo.');
        }

        $query = \App\Models\DocumentoFinanciero::with(['empresa', 'tipoDocumento', 'movimientos.user']);

        // 🔍 Filtros dinámicos según el formulario
        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('folio', 'like', "%$q%")
                    ->orWhere('razon_social', 'like', "%$q%")
                    ->orWhere('rut_cliente', 'like', "%$q%");
            });
        }

        if ($request->filled('rut')) {
            $query->where('rut_cliente', 'like', "%{$request->rut}%");
        }

        if ($request->filled('estado')) {
            $estado = $request->estado;
            $query->where(function ($sub) use ($estado) {
                $sub->where('status', $estado)
                    ->orWhere('status', $estado);
            });
        }


        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha_docto', [$request->fecha_inicio, $request->fecha_fin]);
        } elseif ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_docto', '>=', $request->fecha_inicio);
        } elseif ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_docto', '<=', $request->fecha_fin);
        }

        // === FILTRO FECHA DE VENCIMIENTO ===
        if ($request->filled('vencimiento_inicio') && $request->filled('vencimiento_fin')) {
            $query->whereBetween('fecha_vencimiento', [$request->vencimiento_inicio, $request->vencimiento_fin]);
        } elseif ($request->filled('vencimiento_inicio')) {
            $query->whereDate('fecha_vencimiento', '>=', $request->vencimiento_inicio);
        } elseif ($request->filled('vencimiento_fin')) {
            $query->whereDate('fecha_vencimiento', '<=', $request->vencimiento_fin);
        }


        // 🔹 Si hay filtros, traer los resultados; si no, solo mostrar vista vacía
        $documentos = null;
        if ($request->hasAny(['q', 'rut', 'estado', 'fecha_inicio', 'fecha_fin', 'vencimiento_inicio', 'vencimiento_fin'])) {
            $documentos = $query->orderByDesc('fecha_docto')->limit(30)->get();
        }

        // 🔹 Si hay un documento específico seleccionado
        $documentoSeleccionado = null;
        if ($request->filled('documento_id')) {
            $documentoSeleccionado = \App\Models\DocumentoFinanciero::with(['empresa', 'tipoDocumento', 'movimientos.user'])
                ->find($request->documento_id);
        }

        return view('cobranzas.general', compact('documentos', 'documentoSeleccionado'));
    }


    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimetypes:text/plain,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);

        $filename = $request->file('file')->getClientOriginalName();

        // 🔍 Detectar RUT en el nombre del archivo
        $rut = null;
        if (preg_match('/(\d{7,8}-[0-9Kk])/', $filename, $matches)) {
            $rut = $this->normalizarRut($matches[1]);
        }

        // 🏢 Buscar empresa con ese RUT
        $empresa = null;
        if ($rut) {
            $empresa = \App\Models\Empresa::whereRaw("REPLACE(REPLACE(rut, '.', ''), '-', '-') = ?", [$rut])->first();
        }

        // 📥 Ejecutar importación
        $import = new DocumentosImport($empresa?->id);
        Excel::import($import, $request->file('file'));

        $import->afterImport();

        $mensajes = [];

        // 🛑 1️⃣ Errores estructurales
        if (count($import->errores) > 0) {
            foreach ($import->errores as $error) {
                $mensajes[] = "⚠️ " . $error;
            }

            return redirect()->route('cobranzas.documentos')
                ->with('error', 'El archivo no cumple con la estructura esperada.')
                ->with('detalles_errores', $mensajes);
        }

        // ✅ 2️⃣ Mensajes informativos
        if (count($import->importados) > 0) {
            $mensajes[] = count($import->importados) . " documentos importados correctamente: " 
                        . implode(', ', $import->importados) . ".";
        }

        if (count($import->duplicados) > 0) {
            $mensajes[] = "Los siguientes folios ya existían y no se importaron: " 
                        . implode(', ', $import->duplicados);
        }

        // ⚡️ 3️⃣ Cobranzas faltantes (flujo guiado sin mostrar alerta)
        if (count($import->sinCobranza) > 0) {

            // 💾 Guardar listas en sesión, pero sin mostrar mensajes duplicados
            session([
                'sin_cobranza_guiada' => $import->sinCobranza,
                'sin_cobranza_pendientes' => $import->sinCobranza,
            ]);

            // Limpia alertas previas
            session()->forget(['sin_cobranza', 'detalles_errores']);

            return redirect()->route('cobranzas.documentos')
                ->with('info', 'Se detectaron nuevos clientes sin cobranza. El sistema abrirá el formulario para crearlos.');
        } else {
            // Si no hay pendientes, limpiar sesiones previas
            session()->forget(['sin_cobranza', 'sin_cobranza_guiada', 'sin_cobranza_pendientes']);
        }

        // 🧾 4️⃣ Notas de crédito
        if (count($import->notasCredito) > 0) {
            foreach ($import->notasCredito as $nota) {
                $mensajes[] = $nota;
            }
        }

        // ⚠️ 5️⃣ Si hubo observaciones (pero no errores)
        if (count($mensajes) > 0) {
            return redirect()->route('cobranzas.documentos')
                ->with('warning', 'La importación finalizó con observaciones.')
                ->with('detalles_errores', $mensajes);
        }

        // 🧾 6️⃣ Registrar movimiento solo si todo fue correcto
        if (count($import->importados) > 0) {
            MovimientoDocumento::create([
                'documento_financiero_id' => null,
                'user_id' => Auth::id(),
                'tipo_movimiento' => 'Importación masiva',
                'descripcion' => "Se importaron " . count($import->importados) . 
                                " documentos desde el archivo '{$filename}' el " . now()->format('d/m/Y H:i:s'),
            ]);
        }

        // 🟢 7️⃣ Mensaje final
        return redirect()->route('cobranzas.documentos')
            ->with('success', 'Archivo importado correctamente.');
    }


    private function normalizarRut($rut)
    {
        if (!$rut) return null;

        // Quitar puntos y espacios
        $rut = preg_replace('/[^0-9kK-]/', '', $rut);

        // Pasar la K a mayúscula
        return strtoupper($rut);
    }


    public function updateStatus(Request $request, DocumentoFinanciero $documento)
    {
        $request->validate([
            'status' => 'nullable|string|max:50',
            'fecha_estado_manual' => 'nullable|date',
        ]);

        $nuevoStatus = $request->status;
        $original = $documento->getOriginal();

        // Guardar estado manual
        $documento->status = $nuevoStatus;

        // Si el estado requiere fecha manual
        if (in_array($nuevoStatus, ['Abono', 'Cruce', 'Pago', 'Pronto pago', 'Cobranza judicial'])) {
            $documento->fecha_estado_manual = $request->fecha_estado_manual ?? now();
        } else {
            $documento->fecha_estado_manual = null;
        }

        // Si el estado es Pago → guardar también en la tabla `pagos`
        if ($nuevoStatus === 'Pago') {
            if ($documento->pagos()->count() === 0) {
                $documento->pagos()->create([
                    'fecha_pago' => $documento->fecha_estado_manual,
                    'user_id' => Auth::id(),
                ]);
            }
        }

        // 🟢 Si el estado es Pronto pago → guardar en la tabla `pronto_pagos`
        if ($nuevoStatus === 'Pronto pago') {
            if ($documento->prontoPagos()->count() === 0) {
                $documento->prontoPagos()->create([
                    'fecha_pronto_pago' => $documento->fecha_estado_manual,
                    'user_id' => Auth::id(),
                ]);
            }
        }

        // Guardar solo si hay cambios
        if ($documento->isDirty(['status', 'fecha_estado_manual'])) {
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

        return redirect()->back()->with('success', 'Estado manual actualizado correctamente.');
    }


    /////////////////////////////////////////////////////
    ////// EXPORTACIÓN //////////////////////////////////
    ///////////////////////////////////////////////////// 
    public function export(Request $request)
    {
        $perPage = 10; // igual que el index()
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $perPage;

        // === Reutilizar la query base del index ===
        $query = DocumentoFinanciero::with(['empresa', 'abonos', 'cruces', 'tipoDocumento', 'referencia', 'referenciados', 'cobranza']);

        // Aplicar mismos filtros dinámicamente
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

        if ($request->filled('estado_pago')) {
            if ($request->estado_pago === 'Pagado') {
                $query->whereHas('pagos');
            } elseif ($request->estado_pago === 'Pendiente') {
                $query->whereDoesntHave('pagos');
            }
        }

        // === Filtros desde filtrarColumnas (si existen) ===
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

                case 'tipo_doc_id':
                    $query->where('tipo_doc_id', $request->valor);
                    break;
            }
        }









        // === Fechas ===
        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha_docto', [$request->fecha_inicio, $request->fecha_fin]);
        }

        if ($request->filled('vencimiento_inicio') && $request->filled('vencimiento_fin')) {
            $query->whereBetween('fecha_vencimiento', [$request->vencimiento_inicio, $request->vencimiento_fin]);
        }

        // === Orden idéntico al index() ===
        // $query->orderByRaw('ISNULL(fecha_vencimiento), fecha_vencimiento DESC');



        // === Ordenamiento desde filtrarColumnas (si existe) ===
        if ($request->filled('sort_by')) {
            $sortBy = $request->get('sort_by', 'razon_social');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderByRaw('ISNULL(fecha_vencimiento), fecha_vencimiento DESC');
        }


        // === Paginación manual (solo los registros de la página actual) ===
        $documentos = $query->skip($offset)->take($perPage)->get();

        

        // === Exportación ===
        $fecha = now()->format('Y-m-d_H-i-s');
        return Excel::download(new DocumentosExport($documentos), "documentos_financieros_pagina_{$page}_{$fecha}.xlsx");
    }

    public function exportAll(Request $request)
    {
        // === Reutilizar la query base del index ===
        $query = DocumentoFinanciero::with(['empresa', 'abonos', 'cruces', 'tipoDocumento', 'referencia', 'referenciados', 'cobranza']);

        // === Aplicar mismos filtros ===
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

        if ($request->filled('estado_pago')) {
            if ($request->estado_pago === 'Pagado') {
                $query->whereHas('pagos');
            } elseif ($request->estado_pago === 'Pendiente') {
                $query->whereDoesntHave('pagos');
            }
        }

        // === Filtros desde filtrarColumnas (si existen) ===
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

                case 'tipo_doc_id':
                    $query->where('tipo_doc_id', $request->valor);
                    break;
            }
        }





        // === Fechas ===
        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha_docto', [$request->fecha_inicio, $request->fecha_fin]);
        }

        if ($request->filled('vencimiento_inicio') && $request->filled('vencimiento_fin')) {
            $query->whereBetween('fecha_vencimiento', [$request->vencimiento_inicio, $request->vencimiento_fin]);
        }

        // === Orden idéntico al index() ===
        // === Ordenamiento desde filtrarColumnas (si existe) ===
        if ($request->filled('sort_by')) {
            $sortBy = $request->get('sort_by', 'razon_social');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderByRaw('ISNULL(fecha_vencimiento), fecha_vencimiento DESC');
        }


        // 🚀 Sin paginación: obtenemos todos los resultados filtrados
        $documentos = $query->get();

        // === Exportación ===
        $fecha = now()->format('Y-m-d_H-i-s');
        return Excel::download(new DocumentosExport($documentos), "documentos_financieros_todos_{$fecha}.xlsx");
    }

    ////////////////////////////////////////////////////
    ////////////////////////////////////////////////////
    ////////////////////////////////////////////////////


    





    public function show(DocumentoFinanciero $documento)
    {
        // Cargar relaciones relevantes, incluyendo proveedor dentro de cruces
        $documento->load([
            'empresa',
            'abonos',
            'cruces.proveedor', // ✅ relación anidada
            'referencia',
            'referenciados'
        ]);

        // 🔹 Guardar la URL anterior solo si viene del listado y no de otra acción (como updateStatus)
        if (url()->previous() && !str_contains(url()->previous(), '/documentos/')) {
            session(['return_to_listado' => url()->previous()]);
        }

        // Si está referenciado por una nota de crédito o hace referencia a una
        $referencias = [
            'referencia' => $documento->referencia,
            'referenciadoPor' => $documento->referenciados,
        ];

        // Cargar proveedores disponibles (para poder mostrar o editar cruces)
        $proveedores = \App\Models\Proveedor::orderBy('razon_social')->get(['id', 'razon_social', 'rut']);

        return view('cobranzas.detalles', compact('documento', 'referencias', 'proveedores'));
    }














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
            'proveedor_id' => 'required|exists:proveedores,id',
        ], [
            'fecha_cruce.before_or_equal' => 'La fecha del cruce no debe sobrepasar la fecha actual.',
            'fecha_cruce.required' => 'La fecha del cruce es obligatoria.',
            'proveedor_id.required' => 'Debe seleccionar un proveedor.',
            'proveedor_id.exists' => 'El proveedor seleccionado no es válido.',
        ]);

        // Validar que el cruce no supere el saldo pendiente
        $saldoPendiente = $documento->saldo_pendiente;

        if ($request->monto > $saldoPendiente) {
            return back()
                ->withErrors(['monto' => 'El cruce no puede ser mayor al saldo pendiente actual.'])
                ->withInput();
        }

        // Crear el registro del cruce
        $cruce = $documento->cruces()->create([
            'monto' => $request->monto,
            'fecha_cruce' => $request->fecha_cruce,
            'proveedor_id' => $request->proveedor_id,
        ]);

        // Actualizar el estado del documento
        $documento->update([
            'status' => 'Cruce',
            'fecha_estado_manual' => now(),
        ]);

        // Registrar movimiento
        \App\Models\MovimientoDocumento::create([
            'documento_financiero_id' => $documento->id,
            'user_id' => Auth::id(),
            'tipo_movimiento' => 'Cruce registrado',
            'descripcion' => "Se registró un cruce de {$request->monto} el {$request->fecha_cruce} con el proveedor ID {$request->proveedor_id}.",
            'datos_nuevos' => [
                'monto' => $request->monto,
                'fecha_cruce' => $request->fecha_cruce,
                'proveedor_id' => $request->proveedor_id,
            ],
        ]);

        return back()->with('success', 'Cruce registrado correctamente.');
    }

}
