<?php

namespace App\Http\Controllers;

use App\Models\Cruce;
use App\Models\DocumentoFinanciero;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CruceController extends Controller
{
    /**
     * Mostrar todos los cruces asociados a un documento.
     */
    public function index(DocumentoFinanciero $documento)
    {
        // Obtener cruces ordenados por fecha
        $cruces = $documento->cruces()->orderBy('fecha_cruce', 'asc')->get();

        // Calcular totales
        $totalCruzado = $cruces->sum('monto');
        $saldoPendiente = $documento->saldo_pendiente;

        return view('cruces.index', compact('documento', 'cruces', 'totalCruzado', 'saldoPendiente'));
    }

    /**
     * Mostrar formulario de edición de un cruce.
     */
    public function edit($id)
    {
        $cruce = Cruce::findOrFail($id);
        $documento = $cruce->documento;

        return view('cruces.edit', compact('cruce', 'documento'));
    }

    /**
     * Actualizar los datos de un cruce.
     */
    public function update(Request $request, $id)
    {
        $cruce = Cruce::findOrFail($id);

        $request->validate([
            'monto' => 'required|integer|min:1',
            'fecha_cruce' => 'required|date|before_or_equal:today',
        ], [
            'fecha_cruce.before_or_equal' => 'La fecha del cruce no debe sobrepasar la fecha actual.',
            'fecha_cruce.required' => 'La fecha del cruce es obligatoria.',
        ]);

        $cruce->update([
            'monto' => $request->monto,
            'fecha_cruce' => $request->fecha_cruce,
        ]);

        return redirect()
            ->route('cruces.index', $cruce->documento_financiero_id)
            ->with('success', 'Cruce actualizado correctamente.');
    }

    /**
     * Eliminar un cruce.
     */
    public function destroy($id)
    {
        $cruce = Cruce::findOrFail($id);
        $documento = $cruce->documento;

        $cruce->delete();

        // Recalcular total de cruces
        $totalCruces = $documento->cruces()->sum('monto');

        if ($totalCruces === 0) {
            // Si no quedan cruces, volver a estado automático
            $nuevoEstado = now()->gt(Carbon::parse($documento->fecha_vencimiento))
                ? 'Vencido'
                : 'Al día';

            $documento->update([
                'status' => $nuevoEstado,
                'fecha_estado_manual' => null,
            ]);
        } else {
            // Si aún hay cruces, mantener el estado “Cruce”
            $documento->update([
                'status' => 'Cruce',
                'fecha_estado_manual' => now(),
            ]);
        }

        return redirect()
            ->route('cruces.index', $documento->id)
            ->with('success', 'Cruce eliminado y estado actualizado correctamente.');
    }
}
