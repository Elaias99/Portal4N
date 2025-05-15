<?php

namespace App\Http\Controllers;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\BultosImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\Bultos;
use App\Models\Jefe;
use App\Models\Area;
use App\Models\Casuistica;
use Illuminate\Support\Facades\Storage;
use App\Jobs\ImportBultosJob;



class BultoController extends Controller
{
    // Método para listar los últimos 10 bultos
    public function index(Request $request)
    {
        $codigo = $request->input('codigo_bulto');
        $bultos = collect(); // colección vacía por defecto

        if ($codigo) {
            $bultos = Bultos::where('codigo_bulto', 'like', '%' . $codigo . '%')->get();
        }

        // Obtener todas las áreas para asignar reclamos
        $areas = Area::all();
        $casuisticas = Casuistica::all();

        return view('bultos.index', compact('bultos', 'areas', 'casuisticas'));
    }



    // Método para mostrar los detalles de un bulto específico
    public function show($id)
    {
        $bulto = Bultos::findOrFail($id);
        return view('bultos.show', compact('bulto'));
    }

    public function importExcel(Request $request)
    {
        try {
            // Validar que el archivo sea un Excel
            $request->validate([
                'file' => 'required|mimes:xlsx,xls'
            ]);

            // Procesar el archivo usando la importación
            Excel::import(new BultosImport, $request->file('file'));

            // Mensaje de éxito
            return back()->with('success', 'Los bultos fueron importados correctamente.');

        } catch (\Exception $e) {
            // Mostrar el error exacto en pantalla
            dd("Error durante la importación:", $e->getMessage());
        }
    }


    



}
