<?php

namespace App\Http\Controllers;

use App\Models\ProntoPago;
use App\Models\DocumentoFinanciero;
use App\Models\DocumentoCompra;
use App\Models\MovimientoDocumento;
use App\Models\MovimientoCompra;
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
            'fecha_pronto_pago' => 'required|date|before_or_equal:today',
        ], [
            'fecha_pronto_pago.before_or_equal' => 'La fecha del pronto pago no debe sobrepasar la fecha actual.',
            'fecha_pronto_pago.required' => 'La fecha del pronto pago es obligatoria.',
        ]);

        // 🔍 Detectar si el ID pertenece a DocumentoFinanciero o DocumentoCompra
        $documento = DocumentoFinanciero::find($documentoId);
        $tipo = 'ventas';

        if (!$documento) {
            $documento = DocumentoCompra::findOrFail($documentoId);
            $tipo = 'compras';
        }

        // Evitar duplicar registros
        if ($documento->prontoPagos()->exists()) {
            return back()->withErrors(['fecha_pronto_pago' => 'Ya existe un registro de pronto pago para este documento.']);
        }

        // ✅ Crear el pronto pago
        $fkCampo = $tipo === 'ventas' ? 'documento_financiero_id' : 'documento_compra_id';

        $documento->prontoPagos()->create([
            $fkCampo => $documento->id,
            'fecha_pronto_pago' => $request->fecha_pronto_pago,
            'user_id' => Auth::id(),
        ]);

        // ✅ Actualizar el estado del documento
        $documento->update([
            $tipo === 'ventas' ? 'status' : 'estado' => 'Pronto pago',
            'fecha_estado_manual' => now(),
        ]);

        // ✅ Registrar el movimiento según el tipo de documento
        if ($tipo === 'ventas') {
            MovimientoDocumento::create([
                'documento_financiero_id' => $documento->id,
                'user_id' => Auth::id(),
                'tipo_movimiento' => 'Registro de Pronto pago',
                'descripcion' => "El documento fue marcado como 'Pronto pago' el {$request->fecha_pronto_pago}.",
                'datos_nuevos' => ['fecha_pronto_pago' => $request->fecha_pronto_pago],
            ]);
        } else {
            MovimientoCompra::create([
                'documento_compra_id' => $documento->id,
                'usuario_id' => Auth::id(),
                'estado_anterior' => $documento->estado,
                'nuevo_estado' => 'Pronto pago',
                'fecha_cambio' => now(),
            ]);
        }

        return back()->with('success', 'Pronto pago registrado correctamente.');
    }

    /**
     * Eliminar un registro de Pronto Pago
     */
    public function destroy($id)
    {
        $prontoPago = \App\Models\ProntoPago::findOrFail($id);
        $documento = $prontoPago->documentoFinanciero ?? $prontoPago->documentoCompra;

        // 🧩 Detectar tipo de documento
        $tipoDocumento = $documento instanceof \App\Models\DocumentoCompra ? 'compra' : 'financiero';

        // 🔹 Eliminar registro
        $prontoPago->delete();

        // 🔹 Si no quedan más registros, recalcular estado automático
        if ($documento->prontoPagos()->count() === 0) {
            $nuevoEstado = now()->gt(\Carbon\Carbon::parse($documento->fecha_vencimiento))
                ? 'Vencido'
                : 'Al día';

            // 🔹 Actualizar el documento según tipo
            if ($tipoDocumento === 'compra') {
                $documento->update([
                    'estado' => null, // limpiar estado manual
                    'status_original' => $nuevoEstado, // mantener estado automático
                    'fecha_estado_manual' => null,
                ]);
            } else {
                $documento->update([
                    'status' => null,
                    'status_original' => $nuevoEstado,
                    'fecha_estado_manual' => null,
                ]);
            }
        }

        // 🔹 Redirección según el módulo
        if ($tipoDocumento === 'compra') {
            return redirect()
                ->route('finanzas_compras.show', $documento->id)
                ->with('success', 'Pronto pago eliminado y estado actualizado correctamente.');
        }

        return redirect()
            ->route('documentos.detalles', $documento->id)
            ->with('success', 'Pronto pago eliminado y estado actualizado correctamente.');
    }

}
