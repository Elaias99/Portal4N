<?php

namespace App\Http\Controllers;

use App\Models\TipoVestimenta;
use Illuminate\Http\Request;

use App\Http\Requests\TipoVestimenta\StoreTipoVestimentaRequest;
use App\Http\Requests\TipoVestimenta\UpdateTipoVestimentaRequest;


class TipoVestimentaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $tipoVestimentas = TipoVestimenta::all();
        return view('tipo_vestimentas.index', compact('tipoVestimentas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        return view('tipo_vestimentas.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTipoVestimentaRequest $request)
    {
        // Crear el Tipo de Vestimenta con los datos validados
        TipoVestimenta::create($request->validated());

        return redirect()->route('tipo_vestimentas.index')->with('success', 'Tipo de Vestimenta creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(TipoVestimenta $tipoVestimenta)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        //
        $tipoVestimenta = TipoVestimenta::findOrFail($id);
        return view('tipo_vestimentas.edit', compact('tipoVestimenta'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTipoVestimentaRequest $request, $id)
    {
        $tipoVestimenta = TipoVestimenta::findOrFail($id);

        // Actualizar el Tipo de Vestimenta con los datos validados
        $tipoVestimenta->update($request->validated());

        return redirect()->route('tipo_vestimentas.index')->with('success', 'Tipo de Vestimenta actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
        $tipoVestimenta = TipoVestimenta::findOrFail($id);
        $tipoVestimenta->delete();
        return redirect()->route('tipo_vestimentas.index')->with('success', 'Tipo de Vestimenta eliminada exitosamente.');
    }
}
