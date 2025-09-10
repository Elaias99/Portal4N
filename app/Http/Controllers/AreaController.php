<?php

namespace App\Http\Controllers;
use App\Models\Area;
use App\Models\Trabajador;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index()
    {
        $areas = Area::with('trabajadores')->get();
        $trabajadores = Trabajador::whereNull('deleted_at')
            ->whereHas('sistemaTrabajo', fn($q) => $q->where('nombre','!=','Desvinculado'))
            ->whereHas('situacion', fn($q) => $q->where('Nombre','!=','Desvinculado'))
            ->get();


        return view('areas.index', compact('areas','trabajadores'));
    }

    public function create()
    {
        $trabajadores = Trabajador::whereNull('deleted_at')->get(); // solo activos
        return view('areas.create', compact('trabajadores'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:areas,nombre',
        ]);

        Area::create($request->only('nombre'));

        return redirect()->route('areas.index')->with('success', 'Área creada correctamente.');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        $area = Area::findOrFail($id);
        $trabajadores = Trabajador::whereNull('deleted_at')->get(); // solo activos
        return view('areas.edit', compact('trabajadores'));
    }

    public function update(Request $request, string $id)
    {
        $area = Area::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:255|unique:areas,nombre,' . $area->id,
        ]);

        $area->update($request->only('nombre'));

        return redirect()->route('areas.index')->with('success', 'Área actualizada correctamente.');
    }

    public function destroy(string $id)
    {
        $area = Area::findOrFail($id);
        $area->delete();

        return redirect()->route('areas.index')->with('success', 'Área eliminada correctamente.');
    }

    public function asignar(Request $request, $id)
    {
        $request->validate([
            'trabajador_id' => 'required|exists:trabajadors,id',
        ]);

        $trabajador = Trabajador::where('id', $request->trabajador_id)
            ->whereNull('deleted_at')
            ->firstOrFail();

        if (is_null($trabajador->area_id)) {
            $trabajador->area_id = $id;
            $trabajador->save();
        } else {
            $trabajador->areasSecundarias()->syncWithoutDetaching([$id]);
        }

        return redirect()->route('areas.index')->with('success', 'Trabajador asignado correctamente.');
    }

    public function quitar($areaId, $trabajadorId)
    {
        $trabajador = Trabajador::where('id', $trabajadorId)
            ->where('area_id', $areaId)
            ->whereNull('deleted_at')
            ->first();

        if ($trabajador) {
            $trabajador->area_id = null;
            $trabajador->save();
        } else {
            $trabajador = Trabajador::where('id', $trabajadorId)
                ->whereNull('deleted_at')
                ->firstOrFail();
            $trabajador->areasSecundarias()->detach($areaId);
        }

        return redirect()->route('areas.index')->with('success', 'Trabajador removido del área correctamente.');
    }

    public function asignarSecundaria(Request $request, $areaId)
    {
        $request->validate([
            'trabajador_id' => 'required|exists:trabajadors,id',
        ]);

        $trabajador = Trabajador::where('id', $request->trabajador_id)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $trabajador->areasSecundarias()->syncWithoutDetaching([$areaId]);

        return back()->with('success', 'Área secundaria asignada correctamente.');
    }

    public function quitarSecundaria($areaId, $trabajadorId)
    {
        $trabajador = Trabajador::where('id', $trabajadorId)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $trabajador->areasSecundarias()->detach($areaId);

        return back()->with('success', 'Área secundaria removida correctamente.');
    }
}
