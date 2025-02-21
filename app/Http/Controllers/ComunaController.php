<?php

namespace App\Http\Controllers;

use App\Models\Comuna;
use App\Models\Region;
use Illuminate\Http\Request;
use App\Http\Requests\Comuna\StoreComunaRequest;
use App\Http\Requests\Comuna\UpdateComunaRequest;

class ComunaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $regions = Region::with(['comunas' => function ($query) {
            $query->orderBy('Nombre', 'asc');
        }])->get();
        return view('comunas.index', compact('regions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $regions = Region::all();
        return view('comunas.create', compact('regions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreComunaRequest $request)
    {
        // Crear la Comuna con datos validados
        Comuna::create($request->validated());

        return redirect()->route('comunas.index')->with('success', 'Comuna creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Comuna $comuna)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        //
        $comuna = Comuna::findOrFail($id);
        $regions = Region::all();
        return view('comunas.edit', compact('comuna', 'regions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateComunaRequest $request, $id)
    {
        //
        $comuna = Comuna::findOrFail($id);
        // Actualizar la Comuna con datos validados
        $comuna->update($request->validated());

        return redirect()->route('comunas.index')->with('success', 'Comuna actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Comuna $comuna)
    {
        //
        $comuna->delete();

        return redirect()->route('comunas.index')->with('success', 'Comuna eliminada exitosamente.');

    }







}
