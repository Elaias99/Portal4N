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

        $tipoDocumento = $documento instanceof \App\Models\DocumentoCompra
            ? 'compra'
            : 'financiero';

        $estadoAnterior = $documento->estado ?? $documento->status;
        $saldoAnterior = $documento->saldo_pendiente;

        $datosAnteriores = [
            'fecha_pronto_pago' => $prontoPago->fecha_pronto_pago,
            'user_id' => $prontoPago->user_id,
        ];

        /*
        |--------------------------------------------------------------------------
        | Eliminar pronto pago y recalcular saldo real
        |--------------------------------------------------------------------------
        */
        $prontoPago->delete();

        if (method_exists($documento, 'recalcularSaldoPendiente')) {
            $documento->recalcularSaldoPendiente();
        }

        $documento->refresh();

        /*
        |--------------------------------------------------------------------------
        | CxC / Ventas
        |--------------------------------------------------------------------------
        | Después de eliminar Pronto pago, el documento debe recuperar el estado
        | manual vigente según los movimientos reales que permanezcan registrados:
        | Pago, Factory, Cruce, Abono u otro estado manual respaldado.
        |--------------------------------------------------------------------------
        */
        if ($tipoDocumento === 'financiero') {
            if (method_exists($documento, 'sincronizarEstadosDesdeMovimientos')) {
                $nuevoEstadoManual = $documento->sincronizarEstadosDesdeMovimientos();
                $documento->refresh();

                $nuevoEstado = $nuevoEstadoManual ?? $documento->status_original;
            } else {
                $nuevoEstado = $documento->status ?? $documento->status_original;
            }

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

            return redirect()
                ->route('documentos.detalles', $documento->id)
                ->with('success', 'Pronto pago eliminado, saldo recalculado y estado actualizado correctamente.');
        }

        /*
        |--------------------------------------------------------------------------
        | CxP / Compras: resincronizar referencias NC cuando correspondan
        |--------------------------------------------------------------------------
        | Si existen Notas de Crédito referenciadas, el servicio mantiene o
        | reconstruye el Abono/Pago automático que corresponda por referencia.
        |--------------------------------------------------------------------------
        */
        if (
            method_exists($documento, 'referenciados') &&
            $documento->referenciados()->where('tipo_documento_id', 61)->exists()
        ) {
            $syncMovimientoReferencia = app(\App\Services\SincronizarMovimientoReferenciaCompraService::class);

            $syncMovimientoReferencia->sincronizar($documento);

            $documento->refresh();
            $documento->recalcularSaldoPendiente();
            $documento->refresh();
        }

        /*
        |--------------------------------------------------------------------------
        | CxP / Compras: resolver estado automático actual
        |--------------------------------------------------------------------------
        | Se mantiene la regla existente de Compras: documentos sin fecha de
        | vencimiento pueden conservar status_original = Pendiente.
        |--------------------------------------------------------------------------
        */
        $nuevoStatusOriginal = 'Pendiente';

        if ($documento->fecha_vencimiento) {
            $nuevoStatusOriginal = now()->gt(\Carbon\Carbon::parse($documento->fecha_vencimiento))
                ? 'Vencido'
                : 'Al día';
        }

        /*
        |--------------------------------------------------------------------------
        | CxP / Compras: recuperar estado vigente después de eliminar Pronto pago
        |--------------------------------------------------------------------------
        | Se mantiene el mismo criterio actualmente utilizado al eliminar Pago.
        |--------------------------------------------------------------------------
        */
        if (
            $documento->pagosReales()->exists() ||
            $documento->pagosPorReferencia()->exists()
        ) {
            $nuevoEstadoManual = 'Pago';
        } elseif ($documento->prontoPagos()->exists()) {
            $nuevoEstadoManual = 'Pronto pago';
        } elseif ($documento->cruces()->exists()) {
            $nuevoEstadoManual = 'Cruce';
        } elseif ($documento->abonos()->exists()) {
            $nuevoEstadoManual = 'Abono';
        } else {
            $nuevoEstadoManual = null;
        }

        $documento->update([
            'estado' => $nuevoEstadoManual,
            'status_original' => $nuevoStatusOriginal,
            'fecha_estado_manual' => $nuevoEstadoManual ? now() : null,
        ]);

        $documento->refresh();

        $nuevoEstado = $nuevoEstadoManual ?? $nuevoStatusOriginal;

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

        return redirect()
            ->route('finanzas_compras.show', $documento->id)
            ->with('success', 'Pronto pago eliminado, movimiento registrado y estado actualizado correctamente.');
    }






}
