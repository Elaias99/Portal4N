<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentoCompra;
use App\Imports\ComprasImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use App\Exports\DocumentoCompraExport;
use App\Models\MovimientoCompra;


class DocumentoCompraController extends Controller
{
    /**
     * 📄 Muestra todos los registros de compras
     */
    public function index()
    {
        // 🔹 Cargamos los documentos con todas sus relaciones relevantes
        $documentosCompras = \App\Models\DocumentoCompra::with([
            'empresa',
            'tipoDocumento',
            'movimientos',
            'abonos',
            'cruces',
            'pagos',
            'prontoPagos'
        ])->latest()->paginate(10);

        // 🔹 Cargamos la lista de proveedores para los modales (formulario de Cruce)
        $proveedores = \App\Models\Proveedor::select('id', 'razon_social', 'rut')->orderBy('razon_social')->get();

        // 🔹 Renderizamos la vista
        return view('cobranzas.finanzas_compras.index', compact('documentosCompras', 'proveedores'));
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
