<?php

namespace App\Http\Controllers;

use App\Models\EstadoCivil;
use Illuminate\Http\Request;
use App\Http\Requests\EstadoCivil\StoreEstadoCivilRequest;
use App\Http\Requests\EstadoCivil\UpdateEstadoCivilRequest;

class EstadoCivilController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $estadoCivils = EstadoCivil::all();
        return view('estado_civil.index', compact('estadoCivils'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        return view('estado_civil.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEstadoCivilRequest $request)
    {
        //
        $request->validate([
            'Nombre' => 'required|unique:estado_civils|max:255',
        ]);
    
        EstadoCivil::create($request->all());
        return redirect()->route('estado_civil.index')
                         ->with('success', 'Estado civil creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(EstadoCivil $estadoCivil)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EstadoCivil $estadoCivil)
    {
        //
        return view('estado_civil.edit', compact('estadoCivil'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEstadoCivilRequest $request, EstadoCivil $estadoCivil)
    {
        //
        $estadoCivil->update($request->validated());
        return redirect()->route('estado_civil.index')
                         ->with('success', 'Estado civil actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EstadoCivil $estadoCivil)
    {
        //
        $estadoCivil->delete();
        return redirect()->route('estado_civil.index')
                         ->with('success', 'Estado civil eliminado exitosamente.');
    }
}
