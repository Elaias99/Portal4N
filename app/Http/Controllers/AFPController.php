<?php

namespace App\Http\Controllers;

use App\Models\AFP;
use App\Models\TasaAfp;
use Illuminate\Http\Request;
use App\Http\Requests\AFP\StoreAFPRequest;
use App\Http\Requests\AFP\UpdateAFPRequest;

class AFPController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $afps = AFP::all();
        return view('afps.index', compact('afps'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('afps.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAFPRequest $request)
    {
        // Crear la AFP
        $afp = AFP::create([
            'Nombre' => $request->input('Nombre'),
        ]);

        // Crear la TasaAfp asociada
        TasaAfp::create([
            'id_afp' => $afp->id,
            'tasa_cotizacion' => $request->input('tasa_cotizacion'),
            'tasa_sis' => $request->input('tasa_sis'),
        ]);

        return redirect()->route('afps.index')->with('success', 'AFP y tasas creadas exitosamente.');


    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AFP $afp)
    {
        return view('afps.edit', compact('afp'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AFP $afp)
    {
        // Actualizar la AFP
        $afp->update([
            'Nombre' => $request->input('Nombre'),
        ]);

        // Actualizar la TasaAfp asociada
        if ($afp->tasaAfp) {
            $afp->tasaAfp->update([
                'tasa_cotizacion' => $request->input('tasa_cotizacion'),
                'tasa_sis' => $request->input('tasa_sis'),
            ]);
        } else {
            // Si no existe, la creamos
            TasaAfp::create([
                'id_afp' => $afp->id,
                'tasa_cotizacion' => $request->input('tasa_cotizacion'),
                'tasa_sis' => $request->input('tasa_sis'),
            ]);
        }

        return redirect()->route('afps.index')->with('success', 'AFP y tasas actualizadas exitosamente.');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AFP $afp)
    {
        $afp->delete();

        return redirect()->route('afps.index')->with('success', 'AFP eliminada exitosamente.');
    }
}
