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
        $cruce = \App\Models\Cruce::findOrFail($id);
        $documento = $cruce->documento;

        // Eliminar el cruce
        $cruce->delete();

        // Recalcular totales
        $totalCruces = $documento->cruces()->sum('monto');
        $totalAbonos = $documento->abonos()->sum('monto');

        // Lógica de actualización de estado
        if ($totalCruces > 0) {
            // Aún quedan cruces → mantener estado Cruce
            $nuevoEstado = 'Cruce';
        } elseif ($totalAbonos > 0) {
            // No hay cruces, pero sí abonos → mantener Abono
            $nuevoEstado = 'Abono';
        } else {
            // No hay ni cruces ni abonos → volver a estado automático
            $nuevoEstado = now()->gt(\Carbon\Carbon::parse($documento->fecha_vencimiento))
                ? 'Vencido'
                : 'Al día';
        }

        // Actualizar documento
        $documento->update([
            'status' => $nuevoEstado,
            'fecha_estado_manual' => ($nuevoEstado === 'Vencido' || $nuevoEstado === 'Al día') ? null : now(),
        ]);

        return redirect()
            ->route('cobranzas.documentos', $documento->id)
            ->with('success', 'Cruce eliminado y estado actualizado correctamente.');
    }

}
