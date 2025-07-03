<?php

namespace App\Http\Controllers;
use App\Models\Area;
use App\Models\Trabajador;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $areas = Area::all();
        $trabajadores = Trabajador::all();
        return view('areas.index', compact('areas','trabajadores'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $trabajadores = Trabajador::all();
        return view('areas.create', compact('trabajadores'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $request->validate([
            'nombre' => 'required|string|max:255|unique:areas,nombre',
        ]);

        Area::create($request->only('nombre'));

        return redirect()->route('areas.index')->with('success', 'Área creada correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
        $area = Area::findOrFail($id);
        $trabajadores = Trabajador::all();
        return view('areas.edit', compact('trabajadores'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $area = Area::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:255|unique:areas,nombre,' . $area->id,
        ]);

        $area->update($request->only('nombre'));

        return redirect()->route('areas.index')->with('success', 'Área actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $area = Area::findOrFail($id);
        $area->delete();

        return redirect()->route('areas.index')->with('success', 'Área eliminada correctamente.');
    }

    public function asignar(Request $request, $id)
    {
        $request->validate([
            'trabajador_id' => 'required|exists:trabajadors,id',
        ]);

        $trabajador = Trabajador::findOrFail($request->trabajador_id);

        if (is_null($trabajador->area_id)) {
            // Asignar como área principal
            $trabajador->area_id = $id;
            $trabajador->save();
        } else {
            // Ya tiene un área principal, asignar como adicional
            $trabajador->areasSecundarias()->syncWithoutDetaching([$id]);
        }

        return redirect()->route('areas.index')->with('success', 'Trabajador asignado correctamente.');
    }

    public function quitar($areaId, $trabajadorId)
    {
        $trabajador = Trabajador::where('id', $trabajadorId)
            ->where('area_id', $areaId)
            ->first();

        if ($trabajador) {
            $trabajador->area_id = null;
            $trabajador->save();
        } else {
            // En caso de que sea un área secundaria
            $trabajador = Trabajador::findOrFail($trabajadorId);
            $trabajador->areasSecundarias()->detach($areaId);
        }

        return redirect()->route('areas.index')->with('success', 'Trabajador removido del área correctamente.');
    }





    public function asignarSecundaria(Request $request, $areaId)
    {
        $request->validate([
            'trabajador_id' => 'required|exists:trabajadors,id',
        ]);

        $trabajador = Trabajador::findOrFail($request->trabajador_id);
        $trabajador->areasSecundarias()->syncWithoutDetaching([$areaId]);

        return back()->with('success', 'Área secundaria asignada correctamente.');
    }

    public function quitarSecundaria($areaId, $trabajadorId)
    {
        $trabajador = Trabajador::findOrFail($trabajadorId);
        $trabajador->areasSecundarias()->detach($areaId);

        return back()->with('success', 'Área secundaria removida correctamente.');
    }



}
