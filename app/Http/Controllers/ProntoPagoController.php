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

        // Detectar tipo de documento
        $tipo = $request->input('tipo', 'ventas');
        $esCompra = $tipo === 'compra';

        $documento = $esCompra
            ? \App\Models\DocumentoCompra::findOrFail($documentoId)
            : \App\Models\DocumentoFinanciero::findOrFail($documentoId);

        // Evitar duplicar registros
        if ($documento->prontoPagos()->exists()) {
            return back()->withErrors(['fecha_pronto_pago' => 'Ya existe un registro de pronto pago para este documento.']);
        }

        // Guardar datos anteriores
        $estadoAnterior = $esCompra ? $documento->estado : $documento->status;
        $saldoAnterior = $documento->saldo_pendiente;

        // Crear el registro de pronto pago
        $documento->prontoPagos()->create([
            $esCompra ? 'documento_compra_id' : 'documento_financiero_id' => $documento->id,
            'fecha_pronto_pago' => $request->fecha_pronto_pago,
            'user_id' => Auth::id(),
        ]);

        // Actualizar estado y saldo
        $campoEstado = $esCompra ? 'estado' : 'status';
        $documento->update([
            $campoEstado => 'Pronto pago',
            'fecha_estado_manual' => now(),
            'saldo_pendiente' => 0,
        ]);

        // Registrar movimiento detallado
        if ($esCompra) {
            \App\Models\MovimientoCompra::create([
                'documento_compra_id' => $documento->id,
                'usuario_id' => Auth::id(),
                'estado_anterior' => $estadoAnterior,
                'nuevo_estado' => 'Pronto pago',
                'fecha_cambio' => now(),
                'tipo_movimiento' => 'Registro de Pronto pago',
                'descripcion' => "El documento fue marcado como 'Pronto pago' el {$request->fecha_pronto_pago}.",
                'datos_anteriores' => [
                    'estado' => $estadoAnterior,
                    'saldo_anterior' => $saldoAnterior,
                ],
                'datos_nuevos' => [
                    'fecha_pronto_pago' => $request->fecha_pronto_pago,
                    'saldo_actual' => 0,
                ],
            ]);
        } else {
            \App\Models\MovimientoDocumento::create([
                'documento_financiero_id' => $documento->id,
                'user_id' => Auth::id(),
                'tipo_movimiento' => 'Registro de Pronto pago',
                'descripcion' => "El documento fue marcado como 'Pronto pago' el {$request->fecha_pronto_pago}.",
                'datos_anteriores' => [
                    'estado' => $estadoAnterior,
                    'saldo_anterior' => $saldoAnterior,
                ],
                'datos_nuevos' => [
                    'fecha_pronto_pago' => $request->fecha_pronto_pago,
                    'saldo_actual' => 0,
                ],
            ]);
        }

        return back()->with('success', 'Pronto pago registrado correctamente y saldo pendiente actualizado a 0.');
    }



    public function destroy($id)
    {
        $prontoPago = \App\Models\ProntoPago::findOrFail($id);
        $documento = $prontoPago->documentoFinanciero ?? $prontoPago->documentoCompra;

        // Detectar tipo de documento
        $tipoDocumento = $documento instanceof \App\Models\DocumentoCompra ? 'compra' : 'financiero';

        // Guardar datos antes de eliminar
        $datosAnteriores = [
            'fecha_pronto_pago' => $prontoPago->fecha_pronto_pago,
            'user_id' => $prontoPago->user_id,
        ];

        $estadoAnterior = $documento->estado ?? $documento->status;
        $saldoAnterior = $documento->saldo_pendiente;

        // Eliminar el registro
        $prontoPago->delete();

        // Si ya no quedan pronto pagos → limpiar estado ANTES del recálculo
        if ($documento->prontoPagos()->count() === 0) {
            $nuevoEstado = now()->gt(\Carbon\Carbon::parse($documento->fecha_vencimiento))
                ? 'Vencido'
                : 'Al día';

            $campoEstado = $tipoDocumento === 'compra' ? 'estado' : 'status';
            $documento->update([
                $campoEstado => null,
                'status_original' => $nuevoEstado,
                'fecha_estado_manual' => null,
            ]);
        } else {
            $nuevoEstado = 'Pronto pago';
        }

        // Liberar saldo_pendiente para recalcular correctamente
        $documento->update(['saldo_pendiente' => null]);

        // Recalcular saldo pendiente
        if (method_exists($documento, 'recalcularSaldoPendiente')) {
            $documento->recalcularSaldoPendiente();
        }

        // Refrescar instancia
        $documento->refresh();

        // Registrar movimiento detallado
        if ($tipoDocumento === 'financiero') {
            \App\Models\MovimientoDocumento::create([
                'documento_financiero_id' => $documento->id,
                'user_id' => Auth::id(),
                'tipo_movimiento' => 'Eliminación de pronto pago',
                'descripcion' => "Se eliminó un pronto pago registrado el {$datosAnteriores['fecha_pronto_pago']} correspondiente al documento folio {$documento->folio}.",
                'datos_anteriores' => array_merge($datosAnteriores, [
                    'estado_anterior' => $estadoAnterior,
                    'saldo_anterior' => $saldoAnterior,
                ]),
                'datos_nuevos' => [
                    'nuevo_estado' => $nuevoEstado,
                    'saldo_actual' => $documento->saldo_pendiente,
                ],
            ]);
        } elseif ($tipoDocumento === 'compra') {
            \App\Models\MovimientoCompra::create([
                'documento_compra_id' => $documento->id,
                'usuario_id' => Auth::id(),
                'estado_anterior' => $estadoAnterior,
                'nuevo_estado' => $nuevoEstado,
                'fecha_cambio' => now(),
                'tipo_movimiento' => 'Eliminación de pronto pago',
                'descripcion' => "Se eliminó un pronto pago registrado el {$datosAnteriores['fecha_pronto_pago']} correspondiente al documento de compra folio {$documento->folio}.",
                'datos_anteriores' => array_merge($datosAnteriores, [
                    'estado_anterior' => $estadoAnterior,
                    'saldo_anterior' => $saldoAnterior,
                ]),
                'datos_nuevos' => [
                    'nuevo_estado' => $nuevoEstado,
                    'saldo_actual' => $documento->saldo_pendiente,
                ],
            ]);
        }

        // Redirección final
        $route = $tipoDocumento === 'compra' ? 'finanzas_compras.show' : 'documentos.detalles';
        return redirect()
            ->route($route, $documento->id)
            ->with('success', 'Pronto pago eliminado, movimiento registrado y estado actualizado correctamente.');
    }






}
