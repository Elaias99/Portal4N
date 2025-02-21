<?php

namespace App\Http\Controllers;

use App\Models\Hijo;
use App\Models\Trabajador;
use Illuminate\Http\Request;

class HijoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $trabajadores = Trabajador::with('hijos')->get();
        return view('hijos.index', compact('trabajadores'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $trabajadores = Trabajador::all();
        return view('hijos.create', compact('trabajadores'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $request->validate([
            'hijos.*.nombre' => 'required|string',
            'hijos.*.genero' => 'required|string',
            'hijos.*.parentesco' => 'required|string',
            'hijos.*.fecha_nacimiento' => 'required|date',
            'hijos.*.edad' => 'required|integer',
            'trabajador_id' => 'required|exists:trabajadors,id',
        ]);
    
        foreach ($request->input('hijos') as $hijoData) {
            Hijo::create(array_merge($hijoData, ['trabajador_id' => $request->trabajador_id]));
        }
    
        return redirect('hijos')->with('success', 'Hijos creados exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(Hijo $hijo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        //
        $hijo = Hijo::findOrFail($id);
        $trabajadores = Trabajador::all();
        return view('hijos.edit', compact('hijo', 'trabajadores'));
        
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
        $request->validate([
            'hijos.*.nombre' => 'required|string',
            'hijos.*.genero' => 'required|string',
            'hijos.*.parentesco' => 'required|string',
            'hijos.*.fecha_nacimiento' => 'required|date',
            'hijos.*.edad' => 'required|integer',
            'trabajador_id' => 'required|exists:trabajadors,id',
        ]);
    
        $hijo = Hijo::findOrFail($id);
        $hijo->update($request->all());
    
        return redirect('hijos')->with('success', 'Hijo actualizado exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
        $hijo = Hijo::findOrFail($id);
        $hijo->delete();
        return redirect()->route('hijos.index')->with('success', 'Hijo eliminado exitosamente.');
    }
}
