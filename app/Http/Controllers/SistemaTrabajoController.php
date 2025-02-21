<?php

namespace App\Http\Controllers;

use App\Models\SistemaTrabajo;
use Illuminate\Http\Request;
use App\Http\Requests\SistemaTrabajo\StoreSistemaTrabajoRequest;
use App\Http\Requests\SistemaTrabajo\UpdateSistemaTrabajoRequest;

class SistemaTrabajoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $sistemasTrabajo = SistemaTrabajo::all();
        return view('sistema_trabajos.index', compact('sistemasTrabajo'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        return view('sistema_trabajos.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSistemaTrabajoRequest $request)
    {
        //
        SistemaTrabajo::create($request->validated());

        return redirect()->route('sistema_trabajos.index')
                         ->with('success', 'Sistema de trabajo creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(SistemaTrabajo $sistemaTrabajo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SistemaTrabajo $sistemaTrabajo)
    {
        //
        return view('sistema_trabajos.edit', compact('sistemaTrabajo'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSistemaTrabajoRequest $request, SistemaTrabajo $sistemaTrabajo)
    {
        //
        $sistemaTrabajo->update($request->validated());

        return redirect()->route('sistema_trabajos.index')
                         ->with('success', 'Sistema de trabajo actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SistemaTrabajo $sistemaTrabajo)
    {
        //
        $sistemaTrabajo->delete();

        return redirect()->route('sistema_trabajos.index')
                        ->with('success', 'Sistema de trabajo eliminado exitosamente.');
    }
}
