<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProveedoresImport;

class ProveedorImportController extends Controller
{
    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => 'required|mimes:xlsx,xls'
        ]);

        $importador = new ProveedoresImport();

        try {
            Excel::import($importador, $request->file('archivo'));

            session()->flash('import_result', [
                'importadas' => $importador->importadas,
                'omitidas' => $importador->omitidas,
                'errores' => $importador->errores,
                'exitosos' => $importador->exitosos,
            ]);

            return back();
        } catch (\Exception $e) {
            return back()->with('error', 'Error durante la importación: ' . $e->getMessage());
        }
    }





}

