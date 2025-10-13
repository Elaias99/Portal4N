<?php

namespace App\Http\Controllers;

use App\Models\Cobranza;
use Illuminate\Http\Request;

class CobranzaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Cobranza::query();

        if ($request->filled('buscar')) {
            $busqueda = $request->input('buscar');
            $query->where('razon_social', 'like', "%{$busqueda}%")
                ->orWhere('rut_cliente', 'like', "%{$busqueda}%");
        }

        $cobranzas = $query->orderBy('id', 'desc')->paginate(10);

        return view('cobranzas.index', compact('cobranzas'));
    }



    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        return view('cobranzas.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $validated = $request->validate([
            'rut_cliente' =>  'required|string|max:255', 
            'razon_social' =>  'required|string|max:255',
            'servicio'    =>  'required|string|max:255',
            'creditos'    =>  'required|string|max:255',
        ]);

        Cobranza::create($validated);

        return redirect()->route('cobranzas.index')
                        ->with('success', 'Cobranza creada correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Cobranza $cobranza)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cobranza $cobranza)
    {
        //
        return view('cobranzas.edit', compact('cobranza'));

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cobranza $cobranza)
    {
        //
        $validated = $request->validate([

            'rut_cliente' =>  'required|string|max:255', 
            'razon_social' =>  'required|string|max:255',
            'servicio' =>  'required|string|max:255',
            'creditos' =>  'required|string|max:255',

        ]);

        $cobranza->update($validated);

        return redirect()->route('cobranzas.index')
                        ->with('success', 'Cobranza actualizada correctamente.');



    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cobranza $cobranza)
    {
        //
        $cobranza->delete();

        return redirect()->route('cobranzas.index')->with('success', 'Cobranza eliminado correctamente.');

    }
}
