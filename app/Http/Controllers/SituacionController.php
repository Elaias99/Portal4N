<?php

namespace App\Http\Controllers;

use App\Models\Situacion;
use Illuminate\Http\Request;
use App\Http\Requests\Situacion\StoreSituacionRequest;
use App\Http\Requests\Situacion\UpdateSituacionRequest;

class SituacionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $situacions = Situacion::all();
        return view('situacions.index', compact('situacions'));


    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        return view('situacions.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSituacionRequest $request)
    {
        //
        Situacion::create($request->validated());

        return redirect()->route('situacions.index')->with('success', 'Estado Laboral creada exitosamente.');


    }

    /**
     * Display the specified resource.
     */
    public function show(Situacion $situacion)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Situacion $situacion)
    {
        //
        return view('situacions.edit', compact('situacion'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSituacionRequest $request, Situacion $situacion)
    {
        //
        $situacion->update($request->validated());

        return redirect()->route('situacions.index')->with('success', 'Situación actualizada exitosamente.');
        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Situacion $situacion)
    {
        //
        $situacion->delete();

        return redirect()->route('situacions.index')->with('success', ' situacions eliminada exitosamente.');
    }
}
