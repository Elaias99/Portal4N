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

            session()->flash('import_result_proveedores', [
                'importadas' => $importador->importadas,
                'omitidas' => $importador->omitidas,
                'errores' => $importador->errores,
                'exitosos' => $importador->exitosos,
                'incompletos' => $importador->conDatosIncompletos,
                'hayErrores' => count($importador->errores) > 0,
                'hayOmitidas' => $importador->omitidas > 0,
                'hayIncompletos' => count($importador->conDatosIncompletos) > 0,

                // 🔽 Clasificación explícita para separar mensajes en la vista
                'erroresDuplicados' => collect($importador->errores)->filter(
                    fn($e) => str_contains($e, 'ya existe el proveedor')
                )->values(),

                'erroresFaltantes' => collect($importador->errores)->filter(
                    fn($e) => str_contains($e, 'falta RUT') || str_contains($e, 'falta RUT o razón social')
                )->values(),

                'erroresCamposInvalidos' => collect($importador->errores)->reject(
                    fn($e) => str_contains($e, 'ya existe el proveedor') || str_contains($e, 'falta RUT')
                )->values(),
            ]);

            return back();
        } catch (\Exception $e) {
            return back()->with('error', 'Error durante la importación: ' . $e->getMessage());
        }
    }







}

