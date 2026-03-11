<?php

namespace App\Services;

use App\Models\DocumentoCompra;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SincronizarMovimientoReferenciaCompraService
{
    public function sincronizar(DocumentoCompra $factura): void
    {
        $factura->refresh();

        // Solo aplica a facturas electrónicas
        if ((int) $factura->tipo_documento_id !== 33) {
            return;
        }

        $abonosReferencia = $factura->abonos()
            ->where('origen', 'referencia_nc');

        $pagosReferencia = $factura->pagos()
            ->where('origen', 'referencia_nc');

        $tienePagoReal = $factura->pagosReales()->exists();
        $saldoActual = (int) $factura->saldo_pendiente;

        $referenciados = $factura->referenciados()->get();

        $totalNotasCredito = (int) $referenciados
            ->where('tipo_documento_id', 61)
            ->sum('monto_total');

        // Caso 1: quedó totalmente saldada
        if ($saldoActual === 0 && $totalNotasCredito > 0) {
            // borrar abonos automáticos por referencia
            $abonosReferencia->delete();

            // marcar como pago
            $factura->update([
                'estado' => 'Pago',
                'fecha_estado_manual' => now(),
            ]);

            if (!$tienePagoReal && !$pagosReferencia->exists()) {
                $factura->pagos()->create([
                    'fecha_pago' => now()->toDateString(),
                    'user_id'    => Auth::id(),
                    'origen'     => 'referencia_nc',
                ]);
            }

            return;
        }

        // Caso 2: tiene NC asociadas pero no quedó en cero
        if ($saldoActual > 0 && $totalNotasCredito > 0) {
            // quitar pago automático por referencia si existiera
            $pagosReferencia->delete();

            // recrear abono automático único por el total de NC asociadas
            $abonosReferencia->delete();

            $factura->abonos()->create([
                'monto' => $totalNotasCredito,
                'fecha_abono' => now()->toDateString(),
                'origen' => 'referencia_nc',
            ]);

            $factura->update([
                'estado' => 'Abono',
                'fecha_estado_manual' => now(),
            ]);

            return;
        }

        // Caso 3: ya no tiene NC aplicables
        $abonosReferencia->delete();
        $pagosReferencia->delete();

        if (!$tienePagoReal) {
            $nuevoStatusOriginal = 'Pendiente';

            if ($factura->fecha_vencimiento) {
                $nuevoStatusOriginal = Carbon::parse($factura->fecha_vencimiento)->isPast()
                    ? 'Vencido'
                    : 'Al día';
            }

            $factura->update([
                'estado' => null,
                'fecha_estado_manual' => null,
                'status_original' => $nuevoStatusOriginal,
            ]);
        }
    }
}