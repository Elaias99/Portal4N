<?php

namespace App\Services\Suscripciones;

use Illuminate\Support\Collection;

class SuscripcionLiquidacionResumenService
{
    public function __construct(
        private SuscripcionAjusteMensualService $ajusteMensualService
    ) {}

    public function calcularPorDetalles(iterable $detalles): Collection
    {
        return collect($detalles)
            ->mapWithKeys(function ($detalle) {
                $ajuste = $this->ajusteMensualService->resolverParaDetalle($detalle);

                $suscripcionProveedorBase = $detalle
                    ->asignacion
                    ?->suscripcionProveedor;

                /*
                 * Si existe proveedor de facturación en el ajuste mensual,
                 * se usa ese proveedor para el cálculo tributario.
                 *
                 * Si no existe ajuste, se usa el proveedor normal de la asignación.
                 */
                $suscripcionProveedorCalculo = $ajuste?->proveedorFacturacion
                    ?: $suscripcionProveedorBase;

                /*
                 * Si el ajuste trae tipo_documento explícito, manda ese valor.
                 * Si no, se usa el tipo del proveedor de cálculo.
                 */
                $tipoOriginal = $suscripcionProveedorCalculo?->tipo;
                $tipo = $this->valorAjustado($ajuste?->tipo_documento, $tipoOriginal);

                $tipoNormalizado = mb_strtoupper(trim((string) $tipo));

                $detalleDocumento = $this->valorAjustado(
                    $ajuste?->detalle_documento,
                    $suscripcionProveedorCalculo?->detalle_documento
                );

                $detalleImpuesto = $this->valorAjustado(
                    $ajuste?->detalle_impuesto,
                    $suscripcionProveedorCalculo?->detalle_impuesto
                );

                $final = $this->valorAjustado(
                    $ajuste?->final,
                    $suscripcionProveedorCalculo?->final
                );

                $netoBruto = (float) $detalle->total;

                $impuesto = $this->resolverPorcentajeImpuesto($tipoNormalizado);

                $totalImpuesto = round($netoBruto * ($impuesto / 100), 2);

                $liquido = $this->calcularLiquido(
                    $tipoNormalizado,
                    $netoBruto,
                    $totalImpuesto
                );

                return [
                    $detalle->id => [
                        'tipo' => $tipo,
                        'detalle_documento' => $detalleDocumento,
                        'detalle_impuesto' => $detalleImpuesto,
                        'final' => $final,
                        'neto_bruto' => $netoBruto,
                        'impuesto' => $impuesto,
                        'total_impuesto' => $totalImpuesto,
                        'liquido' => $liquido,
                        'ajuste_mensual_id' => $ajuste?->id,
                    ],
                ];
            });
    }

    private function valorAjustado(mixed $valorAjustado, mixed $valorOriginal): mixed
    {
        if ($valorAjustado === null || $valorAjustado === '') {
            return $valorOriginal;
        }

        return $valorAjustado;
    }

    private function resolverPorcentajeImpuesto(string $tipo): float
    {
        if (str_contains($tipo, 'FACTURA')) {
            return 19.00;
        }

        if (str_contains($tipo, 'BOLETA')) {
            return 15.25;
        }

        if (str_contains($tipo, 'DOCUMENTO')) {
            return 0.00;
        }

        return 0.00;
    }

    private function calcularLiquido(string $tipo, float $netoBruto, float $totalImpuesto): float
    {
        if (str_contains($tipo, 'FACTURA')) {
            return floor($netoBruto + $totalImpuesto);
        }

        if (str_contains($tipo, 'BOLETA')) {
            return floor($netoBruto - $totalImpuesto);
        }

        return floor($netoBruto);
    }
}