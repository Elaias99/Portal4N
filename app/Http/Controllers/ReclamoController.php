<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reclamos;
use App\Models\Bultos;
use App\Models\Area;
use Illuminate\Support\Facades\Auth; // Importamos Auth para manejar la autenticación

class ReclamoController extends Controller
{
    // Listar los reclamos del trabajador autenticado
    public function index()
    {
        // Obtener el trabajador autenticado
        $trabajador = Auth::all(); 
        $areas = Area::all();
        // Obtener los reclamos del trabajador (usando la relación definida en el modelo)
        $reclamos = $trabajador->reclamos; 

        return view('reclamos.index', compact('reclamos','areas'));
    }

    // Mostrar formulario para crear un reclamo asociado a un bulto
    public function create($id_bulto)
    {
        $bulto = Bultos::findOrFail($id_bulto);
        return view('reclamos.create', compact('bulto'));
    }

    // Guardar un nuevo reclamo
    public function store(Request $request)
    {
        $request->validate([
            'id_bulto' => 'required|exists:bultos,id',
            'area_id' => 'required|exists:areas,id',
            'descripcion' => 'required|string'
        ]);

        // Obtener el trabajador autenticado
        $trabajador = Auth::user(); 

        // Crear el reclamo asociado al trabajador autenticado
        Reclamos::create([
            'id_bulto' => $request->id_bulto,
            'id_trabajador' => $trabajador->id,
            'area_id' => $request->area_id, // ✅ nuevo
            'descripcion' => $request->descripcion,
            'estado' => 'pendiente',
        ]);

        return redirect()->route('reclamos.index')->with('success', 'Reclamo enviado correctamente.');
    }
}
