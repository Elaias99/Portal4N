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

        if ($request->filled('fecha_docto')) {
            $query->whereDate('fecha_docto', $request->fecha_docto);
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
            $documento->fecha_estado_manual = now();
        } else {
            // Si vuelve a un estado calculado (Al día / Vencido) limpiamos la fecha
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




}
