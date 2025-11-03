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


class DocumentoCompraController extends Controller
{
    /**
     * Muestra todos los registros de compras
     */
    public function index(Request $request)
    {
        // 🔹 Base query con relaciones relevantes
        $query = \App\Models\DocumentoCompra::with([
            'empresa',
            'tipoDocumento',
            'movimientos',
            'abonos',
            'cruces',
            'pagos',
            'prontoPagos'
        ]);

        // 🔹 Filtros dinámicos
        if ($request->filled('rut_proveedor')) {
            $query->where('rut_proveedor', 'like', '%' . $request->rut_proveedor . '%');
        }

        if ($request->filled('razon_social')) {
            $query->where('razon_social', 'like', '%' . $request->razon_social . '%');
        }

        if ($request->filled('folio')) {
            $query->where('folio', 'like', '%' . $request->folio . '%');
        }

        if ($request->filled('estado')) {
            $query->where(function ($q) use ($request) {
                $q->where('status_original', $request->estado)
                ->orWhere('estado', $request->estado);
            });
        }

        // 🔹 Filtro por rango de fecha del documento
        if ($request->filled('fecha_docto_inicio') && $request->filled('fecha_docto_fin')) {
            $query->whereBetween('fecha_docto', [
                $request->fecha_docto_inicio,
                $request->fecha_docto_fin
            ]);
        } elseif ($request->filled('fecha_docto_inicio')) {
            $query->whereDate('fecha_docto', '>=', $request->fecha_docto_inicio);
        } elseif ($request->filled('fecha_docto_fin')) {
            $query->whereDate('fecha_docto', '<=', $request->fecha_docto_fin);
        }

        // 🔹 Filtro por rango de fecha de vencimiento
        if ($request->filled('fecha_venc_inicio') && $request->filled('fecha_venc_fin')) {
            $query->whereBetween('fecha_vencimiento', [
                $request->fecha_venc_inicio,
                $request->fecha_venc_fin
            ]);
        } elseif ($request->filled('fecha_venc_inicio')) {
            $query->whereDate('fecha_vencimiento', '>=', $request->fecha_venc_inicio);
        } elseif ($request->filled('fecha_venc_fin')) {
            $query->whereDate('fecha_vencimiento', '<=', $request->fecha_venc_fin);
        }

        // 🔹 Obtenemos resultados
        $documentosCompras = $query->latest()->get();

        $hoy = \Carbon\Carbon::today();

        // 🔹 Actualizamos estado original (Al día / Vencido)
        foreach ($documentosCompras as $doc) {
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

        // 🔹 Totales por estado original
        $totalAlDia = \App\Models\DocumentoCompra::where('status_original', 'Al día')->count();
        $totalVencido = \App\Models\DocumentoCompra::where('status_original', 'Vencido')->count();

        // 🔹 Filtro por estado de pago
        if ($request->filled('estado_pago')) {
            $documentosCompras = $documentosCompras->filter(function ($doc) use ($request) {
                if ($request->estado_pago === 'Pagado') {
                    return $doc->saldo_pendiente <= 0;
                }
                if ($request->estado_pago === 'Pendiente') {
                    return $doc->saldo_pendiente > 0;
                }
                return true;
            });
        }

        // 🔹 Totales por estado de pago
        $totalPagados = $documentosCompras->filter(fn($d) => $d->saldo_pendiente <= 0)->count();
        $totalPendientes = $documentosCompras->filter(fn($d) => $d->saldo_pendiente > 0)->count();

        // 🔹 Aplicar paginación después del filtrado
        $page = $request->get('page', 1);
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        $itemsPaginated = $documentosCompras->slice($offset, $perPage)->values();

        // 🔹 Cálculo del total de saldo pendiente SOLO de la página actual
        $totalSaldoPendiente = $itemsPaginated
            ->filter(function ($doc) {
                if (in_array($doc->tipo_documento_id, [61, 56])) return false;
                if ($doc->pagos->count() > 0) return false;
                return true;
            })
            ->sum(fn($doc) => $doc->saldo_pendiente);

        // 🔹 Crear paginador
        $documentosCompras = new \Illuminate\Pagination\LengthAwarePaginator(
            $itemsPaginated,
            $documentosCompras->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // 🔹 Listas auxiliares para los filtros
        $proveedores = \App\Models\Proveedor::select('id', 'razon_social', 'rut')->orderBy('razon_social')->get();
        $tiposDocumento = \App\Models\TipoDocumento::orderBy('nombre')->get();
        $empresas = \App\Models\Empresa::orderBy('Nombre')->get();

        // 🔹 Renderizamos la vista
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

    public function filtrarColumnas(Request $request)
    {
        // ✅ Control de acceso (ajusta si es necesario)
        $usuariosFinanzas = [1, 405, 374, 375];
        if (!in_array(Auth::id(), $usuariosFinanzas)) {
            abort(403, 'Acceso denegado.');
        }

        // === BASE QUERY ===
        $query = DocumentoCompra::with(['empresa', 'tipoDocumento']);

        // === FILTRO DIRECTO POR COLUMNA ===
        $columna = $request->get('columna');
        $valor = $request->get('valor');

        if ($columna && $valor) {
            $query->where($columna, 'like', "%{$valor}%");
        }

        // === ORDENAMIENTO POR COLUMNA ===
        $sortBy = $request->get('sort_by', 'razon_social');
        $sortOrder = $request->get('sort_order', 'asc');

        $columnasPermitidas = [
            'fecha_estado_manual',
            'status',
            'tipo_doc_id',
            'tipo_compra',
            'rut_proveedor',
            'razon_social',
            'folio',
            'fecha_docto',
            'fecha_vencimiento',
            'monto_neto',
            'iva_rec',
            'monto_total',
            'saldo_pendiente',
            'empresa_id'
        ];

        if (!in_array($sortBy, $columnasPermitidas)) {
            $sortBy = 'razon_social';
        }

        $documentosCompras = $query->orderBy($sortBy, $sortOrder)->paginate(10);

        // === CÁLCULOS ===
        $totalSaldoPendiente = $documentosCompras->sum('saldo_pendiente');
        $totalPagados = $documentosCompras->where('saldo_pendiente', '<=', 0)->count();
        $totalPendientes = $documentosCompras->where('saldo_pendiente', '>', 0)->count();

        // === SELECTS ===
        $empresas = Empresa::orderBy('Nombre')->get(['id', 'Nombre']);
        $tiposDocumento = TipoDocumento::orderBy('nombre')->get(['id', 'nombre']);

        // 🚨 DEBUG TEMPORAL
        dd('LLEGÓ AL MÉTODO filtrarColumnas', $sortBy, $sortOrder);

        // === RENDER ===
        return view('cobranzas.finanzas_compras.index', compact(
            'documentosCompras',
            'totalSaldoPendiente',
            'totalPagados',
            'totalPendientes',
            'sortBy',
            'sortOrder',
            'empresas',
            'tiposDocumento'
        ));
    }




    /**
     * 📤 Importa el archivo Excel RCV_COMPRAS
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

        
        // ⚡️ Cobranzas faltantes
        if (count($import->sinCobranza) > 0) {

            foreach ($import->sinCobranza as $item) {
                $mensajes[] = "No existe cobranza para la razón social '{$item['razon_social']}' (RUT: {$item['rut_proveedor']}), 
                folio {$item['folio']}. <a href='#' 
                class='btn-link text-primary crear-cobranza-link' 
                data-rut='{$item['rut_proveedor']}' 
                data-razon='{$item['razon_social']}'>Cree la cobranza aquí</a>";
            }

            session(['sin_cobranza' => $import->sinCobranza]);

        } else {
            session()->forget('sin_cobranza');
        }


        // 🟢 Caso 1: Importación exitosa, sin duplicados
        if ($totalImportados > 0 && $totalDuplicados === 0) {
            return redirect()->route('finanzas_compras.index')->with('success', "Archivo importado correctamente.");
        }

        // 🟡 Caso 2: Todo duplicado (no se importó nada)
        if ($totalImportados === 0 && $totalDuplicados > 0) {
            return redirect()->route('finanzas_compras.index')->with([
                'warning' => "Todos los registros del archivo ya existían. No se importó ningún registro nuevo.",
                'detalles_errores' => $import->duplicados
            ]);
        }

        // 🟡 Caso 3: Mezcla (algunos nuevos y otros duplicados)
        if ($totalImportados > 0 && $totalDuplicados > 0) {
            return redirect()->route('finanzas_compras.index')->with([
                'success' => "Se importaron {$totalImportados} registros nuevos.",
                'warning' => "Se detectaron {$totalDuplicados} folios duplicados que no fueron importados.",
                'detalles_errores' => $import->duplicados
            ]);
        }

        // 🔴 Caso 4: Archivo vacío o sin registros válidos
        return redirect()->route('finanzas_compras.index')->with('error', 'No se encontraron registros válidos para importar.');
    }



    public function export()
    {
        return Excel::download(new DocumentoCompraExport, 'documentos_compras.xlsx');
    }


    public function updateEstado(Request $request, $id)
    {
        // Validación básica
        $request->validate([
            'estado' => 'nullable|string|max:50',
        ]);

        // 🔍 Buscar el documento
        $documento = DocumentoCompra::findOrFail($id);

        // Guardar estado anterior
        $estadoAnterior = $documento->estado;

        // ⚙️ Actualizar el estado manual y fecha
        $documento->update([
            'estado' => $request->estado,
            'fecha_estado_manual' => now(), // ⬅️ aquí agregamos la fecha
        ]);

        //  Registrar movimiento con trazabilidad
        MovimientoCompra::create([
            'documento_compra_id' => $documento->id,
            'usuario_id' => Auth::id(),
            'estado_anterior' => $estadoAnterior,
            'nuevo_estado' => $request->estado,
            'fecha_cambio' => now(),
        ]);

        //  Redirigir con mensaje
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

        // ✅ Validar que el abono no supere el saldo pendiente
        $saldoPendiente = $documento->saldo_pendiente;

        if ($request->monto > $saldoPendiente) {
            return back()
                ->withErrors(['monto' => 'El abono no puede ser mayor al saldo pendiente actual.'])
                ->withInput();
        }

        // 🧾 Guardar el abono
        $documento->abonos()->create([
            'monto' => $request->monto,
            'fecha_abono' => $request->fecha_abono,
        ]);

        // 🔁 Actualizar estado del documento
        $documento->update([
            'estado' => 'Abono',
        ]);

        // 🧠 Registrar movimiento
        MovimientoCompra::create([
            'documento_compra_id' => $documento->id,
            'usuario_id' => Auth::id(),
            'estado_anterior' => $documento->estado ?? 'Sin estado previo',
            'nuevo_estado' => 'Abono',
            'fecha_cambio' => now(),
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

        // ✅ Validar que el cruce no supere el saldo pendiente
        $saldoPendiente = $documento->saldo_pendiente;

        if ($request->monto > $saldoPendiente) {
            return back()
                ->withErrors(['monto' => 'El cruce no puede ser mayor al saldo pendiente actual.'])
                ->withInput();
        }

        // 🧾 Crear el registro del cruce
        $documento->cruces()->create([
            'monto' => $request->monto,
            'fecha_cruce' => $request->fecha_cruce,
            'proveedor_id' => $request->proveedor_id,
        ]);

        // 🔁 Actualizar estado del documento
        $documento->update([
            'estado' => 'Cruce',
        ]);

        // 🧠 Registrar movimiento
        MovimientoCompra::create([
            'documento_compra_id' => $documento->id,
            'usuario_id' => Auth::id(),
            'estado_anterior' => $documento->estado ?? 'Sin estado previo',
            'nuevo_estado' => 'Cruce',
            'fecha_cambio' => now(),
        ]);

        return back()->with('success', 'Cruce registrado correctamente.');
    }






}
