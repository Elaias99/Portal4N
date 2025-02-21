<?php

namespace App\Http\Controllers;

use App\Models\Salud;
use Illuminate\Http\Request;

use App\Http\Requests\Salud\StoreSaludRequest;
use App\Http\Requests\Salud\UpdateSaludRequest;

class SaludController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $saluds = Salud::all();
        return view('saluds.index', compact('saluds'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        return view('saluds.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSaludRequest $request)
    {
        //
        Salud::create($request->validated());

        return redirect()->route('saluds.index')->with('success', 'Salud creada exitosamente.');

    }

    /**
     * Display the specified resource.
     */
    public function show(Salud $salud)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Salud $salud)
    {
        //
        return view('saluds.edit', compact('salud'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSaludRequest $request, Salud $salud)
    {
        //
        $salud->update($request->validated());

        return redirect()->route('saluds.index')->with('success', 'Salud actualizada exitosamente.');
    }

    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Salud $salud)
    {
        //
        $salud->delete();

        return redirect()->route('saluds.index')->with('success', ' Salud eliminada exitosamente.');
    }
}
