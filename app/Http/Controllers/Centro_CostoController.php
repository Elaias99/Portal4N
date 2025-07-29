<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CentroCosto;

class Centro_CostoController extends Controller
{
    //
    public function index(Request $request)
    {

        $search = $request->input('search');
        $query = CentroCosto::query();

        if ($search) {
            $query->where('nombre', 'like', "%{$search}%");

        }
        $centrocostos = $query->get();
        // $centrocostos = CentroCosto::all();

        return view('centro_costos.index', compact('centrocostos'));

        
    }

    public function create()
    {
        $centro_costo = new CentroCosto();
        return view('centro_costos.create', compact('centro_costo'));
    }


    public function store(Request $request)
    {

        $validated = $request->validate([
            'nombre' => 'required|string'
        ]);

        CentroCosto::create($validated);

        return redirect()->route('centro_costos.index')->with('success', 'Centro costo registrado exitosamente.');
        
    }

    public function edit(CentroCosto $centro_costo)
    {
        return view('centro_costos.edit', compact('centro_costo'));
        
    }

    public function update(Request $request, CentroCosto $centro_costo)
    {

        $validated = $request->validate([
            'nombre' => 'required|string'
        ]);

        $centro_costo->update($validated);

        return redirect()->route('centro_costos.index')->with('success', 'Centro costo actualizada exitosamente.');
        
    }


    public function destroy(CentroCosto $centro_costo)
    {

        $centro_costo->delete();
        return redirect()->route('centro_costos.index')->with('success', 'Registro eliminado exitosamente.');
        
    }


}
