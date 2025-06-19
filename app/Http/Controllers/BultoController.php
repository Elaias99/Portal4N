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
            $request->validate([
                'file' => 'required|mimes:xlsx,xls'
            ]);

            $import = new BultosImport();
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

            $nuevos = $import->insertados;
            $repetidos = $import->duplicados;

            if ($nuevos === 0 && $repetidos > 0) {
                return back()->with('info', "Todos los bultos del archivo ya estaban registrados. No se agregó ninguno.");
            }

            if ($nuevos > 0 && $repetidos > 0) {
                return back()->with('success', "$nuevos bultos fueron agregados correctamente. $repetidos ya estaban en el sistema y fueron ignorados.");
            }

            if ($nuevos > 0 && $repetidos === 0) {
                return back()->with('success', "Importación completada: $nuevos bultos nuevos agregados.");
            }

            return back()->with('info', "El archivo estaba vacío o mal formateado. No se procesaron bultos.");

        } catch (\Exception $e) {
            return back()->with('error', 'Error al importar: ' . $e->getMessage());
        }
    }




    



}
