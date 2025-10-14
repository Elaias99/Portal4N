<?php

namespace App\Http\Controllers;

use App\Models\DocumentoFinanciero;
use Illuminate\Http\Request;

class AbonoController extends Controller
{
    //
    public function index(DocumentoFinanciero $documento)
    {
        // Traer todos los abonos ordenados por fecha
        $abonos = $documento->abonos()->orderBy('fecha_abono', 'asc')->get();

        // Calcular el total abonado y el saldo pendiente
        $totalAbonado = $abonos->sum('monto');
        $saldoPendiente = $documento->saldo_pendiente;

        return view('abonos.index', compact('documento', 'abonos', 'totalAbonado', 'saldoPendiente'));
    }


        /**
     * Mostrar formulario de edición de un abono específico.
     */
    public function edit($id)
    {
        $abono = \App\Models\Abono::findOrFail($id);
        $documento = $abono->documento;

        return view('abonos.edit', compact('abono', 'documento'));
    }

    /**
     * Actualizar los datos del abono (fecha y/o monto).
     */
    public function update(Request $request, $id)
    {
        $abono = \App\Models\Abono::findOrFail($id);

        $request->validate([
            'monto' => 'required|integer|min:1',
            'fecha_abono' => 'required|date|before_or_equal:today',
        ], [
            'fecha_abono.before_or_equal' => 'La fecha del abono no debe sobrepasar la fecha actual.',
            'fecha_abono.required' => 'La fecha del abono es obligatoria.',
        ]);

        $abono->update([
            'monto' => $request->monto,
            'fecha_abono' => $request->fecha_abono,
        ]);

        return redirect()
            ->route('abonos.index', $abono->documento_financiero_id)
            ->with('success', 'Abono actualizado correctamente.');
    }

    /**
     * Eliminar un abono específico.
     */
    public function destroy($id)
    {
        $abono = \App\Models\Abono::findOrFail($id);
        $documento = $abono->documento;

        // Eliminar el abono
        $abono->delete();

        // Recalcular totales
        $totalAbonos = $documento->abonos()->sum('monto');
        $totalCruces = $documento->cruces()->sum('monto');

        // Lógica de actualización de estado
        if ($totalAbonos > 0) {
            // Aún quedan abonos → mantener estado Abono
            $nuevoEstado = 'Abono';
        } elseif ($totalCruces > 0) {
            // No hay abonos, pero sí cruces → mantener Cruce
            $nuevoEstado = 'Cruce';
        } else {
            // No hay ni abonos ni cruces → volver a estado automático
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
            ->with('success', 'Abono eliminado y estado actualizado correctamente.');
    }





}
