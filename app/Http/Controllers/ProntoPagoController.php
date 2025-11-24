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

        // 🔍 Detectar tipo de documento por el campo enviado en el formulario
        $tipo = $request->input('tipo', 'ventas');
        $esCompra = $tipo === 'compra';

        if ($esCompra) {
            $documento = DocumentoCompra::findOrFail($documentoId);
        } else {
            $documento = DocumentoFinanciero::findOrFail($documentoId);
        }

        // 🚫 Evitar duplicar registros
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

        // ✅ Guardar estado manual y saldo pendiente en 0 (igual que en PagoDocumentoController)
        $campoEstado = $tipo === 'ventas' ? 'status' : 'estado';
        $documento->update([
            $campoEstado => 'Pronto pago',
            'fecha_estado_manual' => now(),
            'saldo_pendiente' => 0,
        ]);

        // ✅ Registrar movimiento según el tipo de documento
        if ($tipo === 'ventas') {
            MovimientoDocumento::create([
                'documento_financiero_id' => $documento->id,
                'user_id' => Auth::id(),
                'tipo_movimiento' => 'Registro de Pronto pago',
                'descripcion' => "El documento fue marcado como 'Pronto pago' el {$request->fecha_pronto_pago}.",
                'datos_nuevos' => [
                    'fecha_pronto_pago' => $request->fecha_pronto_pago,
                    'saldo_pendiente' => 0,
                ],
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

        return back()->with('success', 'Pronto pago registrado correctamente y saldo pendiente actualizado a 0.');
    }


    /**
     * Eliminar un registro de Pronto Pago
     */
    // public function destroy($id)
    // {
    //     $prontoPago = \App\Models\ProntoPago::findOrFail($id);
    //     $documento = $prontoPago->documentoFinanciero ?? $prontoPago->documentoCompra;

    //     // 🧩 Detectar tipo de documento
    //     $tipoDocumento = $documento instanceof \App\Models\DocumentoCompra ? 'compra' : 'financiero';

    //     // 🔹 Eliminar registro
    //     $prontoPago->delete();

    //     // 🔹 Si ya no quedan pronto pagos → limpiar estado ANTES del recálculo
    //     if ($documento->prontoPagos()->count() === 0) {

    //         $nuevoEstado = now()->gt(\Carbon\Carbon::parse($documento->fecha_vencimiento))
    //             ? 'Vencido'
    //             : 'Al día';

    //         if ($tipoDocumento === 'compra') {
    //             $documento->update([
    //                 'estado' => null,
    //                 'status_original' => $nuevoEstado,
    //                 'fecha_estado_manual' => null,
    //             ]);
    //         } else {
    //             $documento->update([
    //                 'status' => null,
    //                 'status_original' => $nuevoEstado,
    //                 'fecha_estado_manual' => null,
    //             ]);
    //         }
    //     }

    //     // 🔹 IMPORTANTE → liberar saldo_pendiente para que el accessor lo recalcule bien
    //     $documento->update(['saldo_pendiente' => null]);

    //     // 🔹 Recalcular saldo pendiente
    //     if (method_exists($documento, 'recalcularSaldoPendiente')) {
    //         $documento->recalcularSaldoPendiente();
    //     }

    //     // 🔄 REFRESH
    //     $documento->refresh();

    //     // 🔹 Redirección final
    //     if ($tipoDocumento === 'compra') {
    //         return redirect()
    //             ->route('finanzas_compras.show', $documento->id)
    //             ->with('success', 'Pronto pago eliminado y estado actualizado correctamente.');
    //     }

    //     return redirect()
    //         ->route('documentos.detalles', $documento->id)
    //         ->with('success', 'Pronto pago eliminado y estado actualizado correctamente.');
    // }
    public function destroy($id)
    {
        $prontoPago = \App\Models\ProntoPago::findOrFail($id);
        $documento = $prontoPago->documentoFinanciero ?? $prontoPago->documentoCompra;

        // 🧩 Detectar tipo de documento
        $tipoDocumento = $documento instanceof \App\Models\DocumentoCompra ? 'compra' : 'financiero';

        // 📝 Guardar datos antes de eliminar
        $datosAnteriores = [
            'fecha_pronto_pago' => $prontoPago->fecha_pronto_pago,
            'user_id' => $prontoPago->user_id,
        ];

        // 🔹 Eliminar registro
        $prontoPago->delete();

        // 🔹 Si ya no quedan pronto pagos → limpiar estado ANTES del recálculo
        if ($documento->prontoPagos()->count() === 0) {
            $nuevoEstado = now()->gt(\Carbon\Carbon::parse($documento->fecha_vencimiento))
                ? 'Vencido'
                : 'Al día';

            if ($tipoDocumento === 'compra') {
                $documento->update([
                    'estado' => null,
                    'status_original' => $nuevoEstado,
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

        // 🔹 IMPORTANTE → liberar saldo_pendiente para que el accessor lo recalcule bien
        $documento->update(['saldo_pendiente' => null]);

        // 🔹 Recalcular saldo pendiente
        if (method_exists($documento, 'recalcularSaldoPendiente')) {
            $documento->recalcularSaldoPendiente();
        }

        // 🔄 REFRESH
        $documento->refresh();

        // 🔹 Registrar movimiento (solo si es documento financiero)
        if ($tipoDocumento === 'financiero') {
            \App\Models\MovimientoDocumento::create([
                'documento_financiero_id' => $documento->id,
                'user_id' => Auth::id(),
                'tipo_movimiento' => 'Eliminación de pronto pago',
                'descripcion' => "Se eliminó un pronto pago registrado el {$datosAnteriores['fecha_pronto_pago']} correspondiente al documento folio {$documento->folio}.",
                'datos_anteriores' => $datosAnteriores,
            ]);
        }

        // 🔹 Redirección final
        if ($tipoDocumento === 'compra') {
            return redirect()
                ->route('finanzas_compras.show', $documento->id)
                ->with('success', 'Pronto pago eliminado, movimiento registrado y estado actualizado correctamente.');
        }

        return redirect()
            ->route('documentos.detalles', $documento->id)
            ->with('success', 'Pronto pago eliminado, movimiento registrado y estado actualizado correctamente.');
    }




}
