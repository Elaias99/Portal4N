<?php

namespace App\Http\Controllers;

use App\Models\ProntoPago;
use App\Models\DocumentoFinanciero;
use App\Models\MovimientoDocumento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProntoPagoController extends Controller
{
    /**
     * Registrar un nuevo Pronto Pago
     */
    public function store(Request $request, $documentoId)
    {
        $request->validate([
            'fecha_pronto_pago' => 'required|date',
        ]);

        $documento = DocumentoFinanciero::findOrFail($documentoId);

        // Evita duplicar registros
        if ($documento->prontoPagos()->count() === 0) {
            $documento->prontoPagos()->create([
                'fecha_pronto_pago' => $request->fecha_pronto_pago,
                'user_id' => Auth::id(),
            ]);

            // Actualizar estado manual del documento
            $documento->update([
                'status' => 'Pronto pago',
                'fecha_estado_manual' => $request->fecha_pronto_pago,
            ]);

            // Registrar movimiento
            MovimientoDocumento::create([
                'documento_financiero_id' => $documento->id,
                'user_id' => Auth::id(),
                'tipo_movimiento' => 'Registro de Pronto pago',
                'descripcion' => "El documento fue marcado como 'Pronto pago'.",
            ]);
        }

        return back()->with('success', 'Pronto pago registrado correctamente.');
    }

    /**
     * Eliminar un registro de Pronto Pago
     */
    public function destroy($id)
    {
        $prontoPago = ProntoPago::findOrFail($id);
        $documento = $prontoPago->documentoFinanciero;

        // Eliminar registro
        $prontoPago->delete();

        // Si no quedan más registros, restablecer estado
        if ($documento->prontoPagos()->count() === 0) {
            $nuevoEstado = now()->gt(\Carbon\Carbon::parse($documento->fecha_vencimiento))
                ? 'Vencido'
                : 'Al día';

            $documento->update([
                'status' => $nuevoEstado,
                'fecha_estado_manual' => null,
            ]);

            MovimientoDocumento::create([
                'documento_financiero_id' => $documento->id,
                'user_id' => Auth::id(),
                'tipo_movimiento' => 'Pronto pago eliminado',
                'descripcion' => "Se eliminó el estado 'Pronto pago'. El documento volvió a '{$nuevoEstado}'.",
            ]);
        }

        return back()->with('success', 'Pronto pago eliminado correctamente.');
    }
}
