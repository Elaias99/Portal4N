<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentoFinanciero;
use Illuminate\Support\Facades\Auth;
use App\Models\MovimientoDocumento;
use App\Models\MovimientoCompra;
use App\Models\DocumentoCompra;
use App\Models\Pago;

class PagoDocumentoController extends Controller
{
    //

    public function store(Request $request, $id)
    {
        $request->validate([
            'fecha_pago' => 'required|date|before_or_equal:today',
        ], [
            'fecha_pago.before_or_equal' => 'La fecha del pago no debe sobrepasar la fecha actual.',
            'fecha_pago.required' => 'La fecha del pago es obligatoria.',
        ]);

        // 🔍 Detectar el tipo de documento (ventas o compras)
        $documento = DocumentoFinanciero::find($id);
        $tipo = 'ventas';

        if (!$documento) {
            $documento = DocumentoCompra::findOrFail($id);
            $tipo = 'compras';
        }

        // Evitar pagos duplicados
        if ($documento->pagos()->exists()) {
            return back()->withErrors(['fecha_pago' => 'Este documento ya tiene un pago registrado.']);
        }

        // ✅ Crear el pago (campo FK dinámico)
        $fkCampo = $tipo === 'ventas' ? 'documento_financiero_id' : 'documento_compra_id';

        $documento->pagos()->create([
            $fkCampo => $documento->id,
            'fecha_pago' => $request->fecha_pago,
            'user_id' => Auth::id(),
        ]);

        // ✅ Actualizar estado
        $documento->update([
            $tipo === 'ventas' ? 'status' : 'estado' => 'Pago',
            'fecha_estado_manual' => now(),
            'saldo_pendiente' => 0,
        ]);

        // ✅ Registrar movimiento
        if ($tipo === 'ventas') {
            MovimientoDocumento::create([
                'documento_financiero_id' => $documento->id,
                'user_id' => Auth::id(),
                'tipo_movimiento' => 'Pago registrado',
                'descripcion' => "Se registró un pago el {$request->fecha_pago}.",
                'datos_nuevos' => ['fecha_pago' => $request->fecha_pago],
            ]);
        } else {
            MovimientoCompra::create([
                'documento_compra_id' => $documento->id,
                'usuario_id' => Auth::id(),
                'estado_anterior' => $documento->estado,
                'nuevo_estado' => 'Pago',
                'fecha_cambio' => now(),
            ]);
        }

        return back()->with('success', 'Pago registrado correctamente y estado actualizado.');
    }


    /**
     * Eliminar un pago (revertir estado de pago).
     */
    public function destroy($id)
    {
        $pago = \App\Models\Pago::findOrFail($id);
        $documento = $pago->documentoFinanciero ?? $pago->documentoCompra;

        // 🧩 Detectar tipo de documento
        $tipoDocumento = $documento instanceof \App\Models\DocumentoCompra ? 'compra' : 'financiero';

        // 🔹 Eliminar el pago
        $pago->delete();

        // 🔹 Verificar si quedan más pagos
        if ($documento->pagos()->count() === 0) {
            // 🔹 Recalcular estado automático (Al día o Vencido)
            $nuevoEstado = now()->gt(\Carbon\Carbon::parse($documento->fecha_vencimiento))
                ? 'Vencido'
                : 'Al día';

            // 🔹 Actualizar el documento según su tipo
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

        // 🔹 Redirección según módulo
        if ($tipoDocumento === 'compra') {
            return redirect()
                ->route('finanzas_compras.show', $documento->id)
                ->with('success', 'Pago eliminado y estado actualizado correctamente.');
        }

        return redirect()
            ->route('documentos.detalles', $documento->id)
            ->with('success', 'Pago eliminado y estado actualizado correctamente.');
    }


}
