<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentoFinanciero;
use Illuminate\Support\Facades\Auth;
use App\Models\MovimientoDocumento;
use App\Models\Pago;

class PagoDocumentoController extends Controller
{
    //
    public function store(Request $request, DocumentoFinanciero $documento)
    {
        $request->validate([
            'fecha_pago' => 'required|date|before_or_equal:today',
        ], [
            'fecha_pago.before_or_equal' => 'La fecha del pago no debe sobrepasar la fecha actual.',
            'fecha_pago.required' => 'La fecha del pago es obligatoria.',
        ]);

        // Evitar pagos duplicados
        if ($documento->pagos()->exists()) {
            return back()->withErrors(['fecha_pago' => 'Este documento ya tiene un pago registrado.']);
        }

        // Crear el pago
        $pago = $documento->pagos()->create([
            'fecha_pago' => $request->fecha_pago,
            'user_id' => Auth::id(),
        ]);

        // ✅ Actualizar estado del documento
        $documento->update([
            'status' => 'Pago',
            'fecha_estado_manual' => $request->fecha_pago,
            'saldo_pendiente' => 0, // El saldo se cierra
        ]);

        // Registrar movimiento
        MovimientoDocumento::create([
            'documento_financiero_id' => $documento->id,
            'user_id' => Auth::id(),
            'tipo_movimiento' => 'Pago registrado',
            'descripcion' => "Se registró un pago el {$request->fecha_pago}. El documento fue marcado como 'Pago'.",
            'datos_nuevos' => ['fecha_pago' => $request->fecha_pago],
        ]);

        return back()->with('success', 'Pago registrado correctamente y estado actualizado.');
    }


    /**
     * Eliminar un pago (revertir estado de pago).
     */
    public function destroy($id)
    {
        $pago = Pago::findOrFail($id);
        $documento = $pago->documentoFinanciero;

        // Eliminar el pago
        $pago->delete();

        // Verificar si quedan más pagos
        if ($documento->pagos()->count() === 0) {
            // Recalcular estado automático (Al día o Vencido)
            $nuevoEstado = now()->gt(\Carbon\Carbon::parse($documento->fecha_vencimiento))
                ? 'Vencido'
                : 'Al día';

            $documento->update([
                'status' => null, // Se limpia el estado manual
                'status_original' => $nuevoEstado, // Se mantiene el estado automático
                'fecha_estado_manual' => null,
            ]);

            MovimientoDocumento::create([
                'documento_financiero_id' => $documento->id,
                'user_id' => Auth::id(),
                'tipo_movimiento' => 'Pago eliminado',
                'descripcion' => "Se eliminó un pago registrado, el estado volvió a '{$nuevoEstado}'.",
            ]);
        }

        return back()->with('success', 'Pago eliminado y estado actualizado correctamente.');
    }

}
