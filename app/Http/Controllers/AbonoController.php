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
        $documento = $abono->documento ?? $abono->documentoCompra;

        // 🧩 Detectar tipo de documento
        $tipoDocumento = $documento instanceof \App\Models\DocumentoCompra ? 'compra' : 'financiero';

        // 🔹 Eliminar el abono
        $abono->delete();


         // 🔹 Recalcular saldo pendiente en la BD
        if (method_exists($documento, 'recalcularSaldoPendiente')) {
            $documento->recalcularSaldoPendiente(); 
        }

        // 🔹 Recalcular totales
        $totalAbonos = $documento->abonos()->sum('monto');
        $totalCruces = $documento->cruces()->sum('monto');

        // 🔹 Determinar nuevo estado
        if ($totalAbonos > 0) {
            $nuevoEstado = 'Abono';
        } elseif ($totalCruces > 0) {
            $nuevoEstado = 'Cruce';
        } else {
            $nuevoEstado = now()->gt(\Carbon\Carbon::parse($documento->fecha_vencimiento))
                ? 'Vencido'
                : 'Al día';
        }

        // 🔹 Actualizar el documento según su tipo
        if ($tipoDocumento === 'compra') {
            $documento->update([
                'estado' => in_array($nuevoEstado, ['Vencido', 'Al día']) ? null : $nuevoEstado,
                'status_original' => $nuevoEstado,
                'fecha_estado_manual' => in_array($nuevoEstado, ['Vencido', 'Al día']) ? null : now(),
            ]);
        } else {
            $documento->update([
                'status' => in_array($nuevoEstado, ['Vencido', 'Al día']) ? null : $nuevoEstado,
                'status_original' => $nuevoEstado,
                'fecha_estado_manual' => in_array($nuevoEstado, ['Vencido', 'Al día']) ? null : now(),
            ]);
        }

        // 🔹 Redirección inteligente
        if ($tipoDocumento === 'compra') {
            return redirect()
                ->route('finanzas_compras.show', $documento->id)
                ->with('success', 'Abono eliminado y estado actualizado correctamente.');
        }

        return redirect()
            ->route('documentos.detalles', $documento->id)
            ->with('success', 'Abono eliminado y estado actualizado correctamente.');
    }


    public function show()
    {
        // Traer todos los abonos con su documento asociado
        $abonos = \App\Models\Abono::with('documento')
            ->orderByDesc('fecha_abono')
            ->get();

        // Calcular totales generales
        $totalAbonado = $abonos->sum('monto');
        $cantidadAbonos = $abonos->count();

        return view('abonos.show', compact('abonos', 'totalAbonado', 'cantidadAbonos'));
    }






}
