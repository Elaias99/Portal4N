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
        $documento = $cruce->documento ?? $cruce->documentoCompra;

        // 🧩 Detectar tipo de documento
        $tipoDocumento = $documento instanceof \App\Models\DocumentoCompra ? 'compra' : 'financiero';

        // 🔹 Eliminar el cruce
        $cruce->delete();

        // 🔹 Recalcular totales
        $totalCruces = $documento->cruces()->sum('monto');
        $totalAbonos = $documento->abonos()->sum('monto');

        // 🔹 Determinar nuevo estado
        if ($totalCruces > 0) {
            $nuevoEstado = 'Cruce';
        } elseif ($totalAbonos > 0) {
            $nuevoEstado = 'Abono';
        } else {
            $nuevoEstado = now()->gt(\Carbon\Carbon::parse($documento->fecha_vencimiento))
                ? 'Vencido'
                : 'Al día';
        }

        // 🔹 Actualizar el documento según tipo
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
                ->with('success', 'Cruce eliminado y estado actualizado correctamente.');
        }

        return redirect()
            ->route('documentos.detalles', $documento->id)
            ->with('success', 'Cruce eliminado y estado actualizado correctamente.');
    }




    public function show()
    {
        // Traer todos los cruces con su documento asociado
        $cruces = \App\Models\Cruce::with('documento')
            ->orderByDesc('fecha_cruce')
            ->get();

        // Totales generales
        $totalCruzado = $cruces->sum('monto');
        $cantidadCruces = $cruces->count();

        return view('cruces.show', compact('cruces', 'totalCruzado', 'cantidadCruces'));
    }


}
