<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentoFinanciero;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DocumentosImport;
use Illuminate\Database\QueryException;

class DocumentoFinancieroController extends Controller
{
    //

    public function index(Request $request)
    {
        $query = DocumentoFinanciero::with('cobranza');

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

        $documentoFinancieros = $query->get();

        return view('cobranzas.documentos', compact('documentoFinancieros'));
    }






    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimetypes:text/plain,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);

        $import = new DocumentosImport();
        Excel::import($import, $request->file('file'));

        if (count($import->errores) > 0) {
            return redirect()->route('cobranzas.documentos')
                ->with('warning', 'Algunos registros no se importaron por folios duplicados.')
                ->with('detalles_errores', $import->errores);
        }

        return redirect()->route('cobranzas.documentos')->with('success', 'Archivo importado correctamente');
    }


}
