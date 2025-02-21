<?php

namespace App\Http\Controllers;

use App\Models\Cargo;
use App\Http\Requests\Cargos\StoreCargoRequest;  // Importa el StoreCargoRequest desde la carpeta Cargos
use App\Http\Requests\Cargos\UpdateCargoRequest;
use Illuminate\Http\Request;


class CargoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $cargos = Cargo::orderBy('Nombre', 'asc')->get();
        return view('cargos.index', compact('cargos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        return view('cargos.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCargoRequest $request)
    {
        $validated = $request->validated();
        Cargo::create($validated);

        return redirect()->route('cargos.index')->with('success', 'Cargo creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Cargo $cargo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cargo $cargo)
    {
        //
        return view('cargos.edit',compact('cargo'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCargoRequest $request, Cargo $cargo)
    {
        $validated = $request->validated();
        $cargo->update($validated);

        return redirect()->route('cargos.index')->with('success', 'Cargo actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cargo $cargo)
    {
        //
        $cargo->delete();

        return redirect()->route('cargos.index')->with('success', 'Cargo eliminado exitosamente.');

    }
}
