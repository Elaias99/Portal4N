<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trabajador;
use App\Models\Contrato;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContratoController extends Controller
{
    public function index()
    {
        $trabajadores = Trabajador::with(['contratos'])
            ->whereNull('deleted_at')

            ->where(function ($query) {
                $query->whereHas('contratos')
                    ->orWhereNotNull('fecha_inicio_trabajo');
            })


            ->orderBy('Nombre')
            ->get();

        return view('contratos.index', compact('trabajadores'));
    }


    public function create($trabajadorId)
    {
        $trabajador = Trabajador::where('id', $trabajadorId)
            ->whereNull('deleted_at')
            ->firstOrFail();

        return view('contratos.create', compact('trabajador'));
    }


    public function store(Request $request, $trabajadorId)
    {
        $request->validate([
            'tipo' => 'required|in:Contrato,Anexo',
            'estado' => 'required|in:Firmado,Pendiente,Rechazado',
            'archivo' => 'nullable|file|mimes:pdf|max:2048',
            'firmado_por' => 'nullable|string|max:255'
        ]);

        $archivoPath = null;

        if ($request->hasFile('archivo')) {
            $archivo = $request->file('archivo');
            $archivoNombre = Str::uuid() . '.' . $archivo->getClientOriginalExtension();
            $archivoPath = $archivo->storeAs('contratos', $archivoNombre, 'public');
        }

        Contrato::create([
            'trabajador_id' => $trabajadorId,
            'tipo' => $request->tipo,
            'estado' => $request->estado,
            'archivo' => $archivoPath,
            'firmado_por' => $request->firmado_por,
        ]);

        $trabajador = Trabajador::where('id', $trabajadorId)
            ->whereNull('deleted_at')
            ->firstOrFail();

        return redirect()->route('contratos.index')->with('success', 'Contrato registrado correctamente.');
    }


    public function edit($id)
    {
        $contrato = Contrato::with(['trabajador' => function ($q) {
            $q->whereNull('deleted_at');
        }])->findOrFail($id);

        return view('contratos.edit', compact('contrato'));
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'tipo' => 'required|in:Contrato,Anexo',
            'estado' => 'required|in:Firmado,Pendiente,Rechazado',
            'archivo' => 'nullable|file|mimes:pdf|max:2048',
            'firmado_por' => 'nullable|string|max:255'
        ]);

        $contrato = Contrato::findOrFail($id);
        $archivoPath = $contrato->archivo;

        if ($request->hasFile('archivo')) {
            if ($archivoPath && Storage::disk('public')->exists($archivoPath)) {
                Storage::disk('public')->delete($archivoPath);
            }

            $archivo = $request->file('archivo');
            $archivoNombre = Str::uuid() . '.' . $archivo->getClientOriginalExtension();
            $archivoPath = $archivo->storeAs('contratos', $archivoNombre, 'public');
        }

        $contrato->update([
            'tipo' => $request->tipo,
            'estado' => $request->estado,
            'archivo' => $archivoPath,
            'firmado_por' => $request->firmado_por,
        ]);

        return redirect()->route('contratos.index')->with('success', 'Contrato actualizado correctamente.');
    }

    public function destroy($id)
    {
        $contrato = Contrato::findOrFail($id);

        if ($contrato->archivo && Storage::disk('public')->exists($contrato->archivo)) {
            Storage::disk('public')->delete($contrato->archivo);
        }

        $trabajadorId = $contrato->trabajador_id;
        $contrato->delete();

        return redirect()->route('contratos.index')->with('success', 'Contrato eliminado correctamente.');
    }

    public function download($id)
    {
        $contrato = Contrato::findOrFail($id);

        if (!$contrato->archivo || !Storage::disk('public')->exists($contrato->archivo)) {
            abort(404, 'Archivo no encontrado');
        }

        return Storage::disk('public')->download($contrato->archivo);
    }
}
