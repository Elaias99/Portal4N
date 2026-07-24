<?php

namespace App\Http\Controllers;

use App\Models\AlmacenamientoBodega;
use Illuminate\Http\Request;
use App\Services\AlmacenamientoBodegaHistorialService;
use Illuminate\Support\Facades\DB;

class AlmacenamientoBodegaController extends Controller
{
    private AlmacenamientoBodegaHistorialService $historialService;

    public function __construct(
        AlmacenamientoBodegaHistorialService $historialService
    ) {
        $this->historialService = $historialService;
    }


    public function index()
    {
        $almacenamiento = AlmacenamientoBodega::all();

        return view('almacenamiento.index', compact('almacenamiento'));
    }

    public function create()
    {
        return view('almacenamiento.create');
    }



    public function store(Request $request)
    {
        $validated = $request->validate([
            'Nombre' => 'required|string|max:255',
            'precio' => 'required|numeric|min:0',
            'cantidad' => 'required|integer|min:0',
            'descripcion' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($validated) {
            $producto = AlmacenamientoBodega::create($validated);

            $this->historialService->registrarCreacion($producto);
        });

        return redirect()
            ->route('almacenamiento.index')
            ->with('success', 'Guardado exitosamente');
    }




    public function show(AlmacenamientoBodega $almacenamiento)
    {
        $almacenamientoBodega = $almacenamiento;

        return view(
            'almacenamiento.show',
            compact('almacenamientoBodega')
        );
    }

    public function edit(AlmacenamientoBodega $almacenamiento)
    {
        $almacenamientoBodega = $almacenamiento;

        return view(
            'almacenamiento.edit',
            compact('almacenamientoBodega')
        );
    }

    public function update(
        Request $request,
        AlmacenamientoBodega $almacenamiento
    ) {
        $almacenamiento->update($request->all());

        return redirect()
            ->route('almacenamiento.index')
            ->with('success', 'Actualizado correctamente');
    }

    public function destroy(AlmacenamientoBodega $almacenamiento)
    {
        DB::transaction(function () use ($almacenamiento) {
            $this->historialService->registrarEliminacion($almacenamiento);

            $almacenamiento->delete();
        });

        return redirect()
            ->route('almacenamiento.index')
            ->with('success', 'Eliminado correctamente');
    }
}