<?php

namespace App\Http\Controllers;

use App\Models\Turno;
use Illuminate\Http\Request;

use App\Http\Requests\Turno\StoreTurnoRequest;
use App\Http\Requests\Turno\UpdateTurnoRequest;

class TurnoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $turnos = Turno::all();
        return view('turnos.index', compact('turnos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        return view('turnos.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTurnoRequest $request)
    {
        //
        Turno::create($request->validated());

        return redirect()->route('turnos.index')
                         ->with('success', 'Turno creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Turno $turno)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Turno $turno)
    {
        //
        return view('turnos.edit', compact('turno'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTurnoRequest $request, Turno $turno)
    {
        //
        $turno->update($request->validated());

        return redirect()->route('turnos.index')
                         ->with('success', 'Turno actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Turno $turno)
    {
        //
        $turno->delete();

        return redirect()->route('turnos.index')
                        ->with('success', 'Turno eliminado exitosamente.');
    }
}
