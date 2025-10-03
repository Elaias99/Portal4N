<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentoFinanciero;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DocumentosImport;
use Illuminate\Database\QueryException;
use App\Exports\DocumentosExport;

class DocumentoFinancieroController extends Controller
{
    //

    public function index(Request $request)
    {
        $query = DocumentoFinanciero::with(['cobranza', 'empresa']);

        // Filtros individuales
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

        // 🔹 Filtro por rango de Fecha Vencimiento
        if ($request->filled('vencimiento_inicio') && $request->filled('vencimiento_fin')) {
            $query->whereBetween('fecha_vencimiento', [$request->vencimiento_inicio, $request->vencimiento_fin]);
        } elseif ($request->filled('vencimiento_inicio')) {
            $query->whereDate('fecha_vencimiento', '>=', $request->vencimiento_inicio);
        } elseif ($request->filled('vencimiento_fin')) {
            $query->whereDate('fecha_vencimiento', '<=', $request->vencimiento_fin);
        }


        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $documentoFinancieros = $query->orderBy('fecha_docto', 'desc')->paginate(7);


        return view('cobranzas.documentos', compact('documentoFinancieros'));
    }







    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimetypes:text/plain,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);

        $filename = $request->file('file')->getClientOriginalName();

        // Detectar RUT en el nombre del archivo
        $rut = null;
        if (preg_match('/(\d{7,8}-[0-9Kk])/', $filename, $matches)) {
            $rut = $this->normalizarRut($matches[1]);
        }

        // Buscar empresa con ese RUT
        $empresa = null;
        if ($rut) {
            $empresa = \App\Models\Empresa::whereRaw("REPLACE(REPLACE(rut, '.', ''), '-', '-') = ?", [$rut])->first();
        }



        // Pasar empresa_id al importador
        $import = new DocumentosImport($empresa?->id);
        Excel::import($import, $request->file('file'));

        if (count($import->errores) > 0) {
            return redirect()->route('cobranzas.documentos')
                ->with('warning', 'La importación finalizó con observaciones.')
                ->with('detalles_errores', $import->errores);
        }


        return redirect()->route('cobranzas.documentos')->with('success', 'Archivo importado correctamente');
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
        ]);

        $nuevoStatus = $request->status;

        $documento->status = $nuevoStatus;

        // Si es uno de los estados manuales → guardamos la fecha actual
        if (in_array($nuevoStatus, ['Abono', 'Pago', 'Cobranza judicial'])) {
            // Usa la fecha enviada desde el formulario o, si no existe, la fecha actual
            $documento->fecha_estado_manual = $request->fecha_estado_manual ?? now();
        } else {
            $documento->fecha_estado_manual = null;
        }


        $documento->save();

        return redirect()->back()->with('success', 'Estado actualizado correctamente.');
    }




    public function export()
    {
        $fecha = now()->format('Y-m-d_H-i-s');
        return Excel::download(new DocumentosExport, "documentos_financieros_{$fecha}.xlsx");
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
        $totalAbonado = $documento->abonos()->sum('monto');
        $saldoPendiente = $documento->monto_total - $totalAbonado;

        if ($request->monto > $saldoPendiente) {
            return back()
                ->withErrors(['monto' => 'El abono no puede ser mayor al saldo pendiente.'])
                ->withInput();
        }

        // Guardar el abono en la tabla abonos
        $documento->abonos()->create([
            'monto' => $request->monto,
            'fecha_abono' => $request->fecha_abono,
        ]);

        // 🔥 Actualizar el documento con status "Abono" y la fecha manual como hoy
        $documento->update([
            'status' => 'Abono',
            'fecha_estado_manual' => now(),
        ]);

        return back()->with('success', 'Abono registrado correctamente.');
    }





}
