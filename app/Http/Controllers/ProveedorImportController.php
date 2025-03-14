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

        Excel::import(new ProveedoresImport, $request->file('archivo'));

        return back()->with('success', 'Los proveedores fueron importados correctamente.');
    }
}

