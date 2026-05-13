<?php

namespace App\Services;

use App\Models\DocumentoCompra;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class ReferenciaNotasCompraService
{
    /**
     * Punto principal del servicio.
     * Recibe una nota de crédito y devuelve:
     * - la factura sugerida
     * - una lista de alternativas
     */
    public function generarSugerencias(DocumentoCompra $notaCredito): array
    {

        // Solo procesar si es Nota de Crédito
        if ((int) $notaCredito->tipo_documento_id !== 61) {
            return [
                'sugerida' => null,
                'alternativas' => collect()
            ];
        }

        // Paso 1: obtener facturas
        $facturas = $this->obtenerFacturasProveedor($notaCredito);

        // Paso 2: aplicar filtro de elegibilidad
        $facturasElegibles = $this->filtrarFacturasElegibles($facturas, $notaCredito);

        // Paso 3: ordenar
        $ordenadas = $this->ordenarFacturas($facturasElegibles, $notaCredito);

        // Paso 4: seleccionar sugerida
        $sugerida = $this->seleccionarFacturaSugerida($ordenadas);

        return [
            'sugerida' => $sugerida,
            'alternativas' => $ordenadas
        ];
    }



    /**
     * Obtener todas las facturas del mismo proveedor.
     * Incluyo códigos de factura (30, 32, 33, 34, 40, 43, 45, 46) porque en compras pueden aparecer facturas de compra y exentas. Excluimos NC y ND automáticamente al filtrar por tipo_documento_id. */

    protected function obtenerFacturasProveedor(DocumentoCompra $notaCredito): Collection
    {
        $facturas = DocumentoCompra::query()
            ->where('empresa_id', $notaCredito->empresa_id)
            ->where('rut_proveedor', $notaCredito->rut_proveedor)
            ->whereIn('tipo_documento_id', [30, 32, 33, 34, 40, 43, 45, 46])
            ->orderBy('fecha_docto', 'asc')
            ->get();

        return $facturas;
    }



    /**
     * Filtrar facturas elegibles según saldo, tipo y validez.
     */
    protected function filtrarFacturasElegibles(Collection $facturas, DocumentoCompra $notaCredito): Collection
    {
        return $facturas->filter(function ($factura) use ($notaCredito) {

            // No permitir que una NC se referencie a sí misma.
            if ((int) $factura->id === (int) $notaCredito->id) {
                return false;
            }

            // La referencia documental debe ser dentro de la misma empresa.
            if ((int) $factura->empresa_id !== (int) $notaCredito->empresa_id) {
                return false;
            }

            // La referencia documental debe ser del mismo proveedor.
            if ((string) $factura->rut_proveedor !== (string) $notaCredito->rut_proveedor) {
                return false;
            }

            return true;
        })->values();
    }



    /**
     * Ordenar facturas según el algoritmo:
     * 1. Coincidencia por fecha docto
     * 2. Fecha docto cercana
     * 3. Monto >= monto NC
     * 4. Antigüedad
     */
    protected function ordenarFacturas(Collection $facturas, DocumentoCompra $notaCredito): Collection
    {
        $fechaNota = \Carbon\Carbon::parse($notaCredito->fecha_docto);
        $montoNota = $notaCredito->monto_total;

        return $facturas->sortBy(function ($factura) use ($fechaNota, $montoNota) {

            $fechaFactura = \Carbon\Carbon::parse($factura->fecha_docto);

            return [
                // PRIORIDAD 1 → coincidencia exacta de fecha (0 días de diferencia primero)
                $fechaFactura->diffInDays($fechaNota),

                // PRIORIDAD 2 → monto más cercano al de la nota
                abs($factura->monto_total - $montoNota),

                // PRIORIDAD 3 → factura más antigua primero
                $fechaFactura->timestamp,
            ];
        })->values();
    }



    /**
     * Seleccionar la mejor candidata.
     */
    protected function seleccionarFacturaSugerida(Collection $facturas): ?DocumentoCompra
    {
        if ($facturas->isEmpty()) {
            return null;
        }

        // La primera es la más relevante tras el ordenamiento
        return $facturas->first();
    }

}
