<?php

namespace App\Services;

use App\Models\DocumentoCompra;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SincronizarPagoReferenciaCompraService
{
    public function sincronizar(DocumentoCompra $factura): void
    {
        $factura->refresh();

        // Solo aplica a facturas electrónicas
        if ((int) $factura->tipo_documento_id !== 33) {
            return;
        }

        $pagoReferenciaQuery = $factura->pagos()
            ->where('origen', 'referencia_nc');

        $tienePagoReal = $factura->pagosReales()->exists();

        // Si la factura quedó completamente saldada
        if ((int) $factura->saldo_pendiente === 0) {
            $factura->update([
                'estado' => 'Pago',
                'fecha_estado_manual' => now(),
            ]);

            if (!$tienePagoReal && !$pagoReferenciaQuery->exists()) {
                $factura->pagos()->create([
                    'fecha_pago' => now()->toDateString(),
                    'user_id'    => Auth::id(),
                    'origen'     => 'referencia_nc',
                ]);
            }

            return;
        }

        // Si ya no está saldada, eliminar solo pago automático por referencia
        $pagoReferenciaQuery->delete();

        // Si no tiene pagos reales, restaurar estado automático
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