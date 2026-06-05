<?php

namespace App\Services\Suscripciones;

use Illuminate\Support\Collection;

class SuscripcionLiquidacionResumenService
{
    public function calcularPorDetalles(iterable $detalles): Collection
    {
        return collect($detalles)
            ->mapWithKeys(function ($detalle) {
                $suscripcionProveedor = $detalle
                    ->asignacion
                    ?->suscripcionProveedor;

                $tipo = mb_strtoupper(trim((string) $suscripcionProveedor?->tipo));

                $netoBruto = (float) $detalle->total;

                $impuesto = $this->resolverPorcentajeImpuesto($tipo);

                $totalImpuesto = round($netoBruto * ($impuesto / 100), 2);

                $liquido = $this->calcularLiquido(
                    $tipo,
                    $netoBruto,
                    $totalImpuesto
                );

                return [
                    $detalle->id => [
                        'tipo' => $suscripcionProveedor?->tipo,
                        'detalle_documento' => $suscripcionProveedor?->detalle_documento,
                        'detalle_impuesto' => $suscripcionProveedor?->detalle_impuesto,
                        'final' => $suscripcionProveedor?->final,
                        'neto_bruto' => $netoBruto,
                        'impuesto' => $impuesto,
                        'total_impuesto' => $totalImpuesto,
                        'liquido' => $liquido,
                    ],
                ];
            });
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