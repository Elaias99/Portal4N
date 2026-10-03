<?php

namespace App\Services\Courier;

use Illuminate\Support\Str;

/*
 * Impuesto según el documento del proveedor, igual que LogisticaCL:
 *
 *   Factura                          → IVA 19 %, se suma
 *   Boleta de Honorarios (y Tercero) → retención 15,25 %, se resta
 *   Factura Exenta                   → sin impuesto
 *
 * Cualquier otro documento no tiene regla: no se puede cerrar.
 * El redondeo es al entero más cercano, con las mismas fracciones
 * (19/100 y 61/400) para que no aparezcan diferencias de un peso.
 */
class CourierImpuestos
{
    public const IVA = 'IVA';
    public const RETENCION = 'Retencion';
    public const EXENTO = 'Exento';

    public static function tieneRegla(?string $documento): bool
    {
        return self::regla($documento) !== null;
    }

    /**
     * @return array{impuesto: string, porcentaje: string, valor_impuesto: int, total: int}|null
     */
    public static function calcular(?string $documento, int $monto): ?array
    {
        $regla = self::regla($documento);

        if ($regla === null) {
            return null;
        }

        [$impuesto, $porcentaje, $numerador, $denominador] = $regla;
        $valor = intdiv($monto * $numerador + intdiv($denominador, 2), $denominador);

        return [
            'impuesto' => $impuesto,
            'porcentaje' => $porcentaje,
            'valor_impuesto' => $valor,
            'total' => $impuesto === self::RETENCION ? $monto - $valor : $monto + $valor,
        ];
    }

    /*
     * Dentro de una OC el impuesto se calcula sobre el acumulado, y a cada
     * pago le toca la diferencia: así la suma de los pagos da exactamente
     * el impuesto de la OC completa.
     *
     * @param  array<string, int>  $bases     acumulado por OC e impuesto
     * @param  array<string, int>  $asignados impuesto ya repartido
     */
    public static function repartirEnOc(?string $documento, int $monto, string $oc, array &$bases, array &$asignados): ?array
    {
        $propio = self::calcular($documento, $monto);

        if ($propio === null) {
            return null;
        }

        $clave = $oc . '|' . $propio['impuesto'];
        $bases[$clave] = ($bases[$clave] ?? 0) + $monto;
        $acumulado = self::calcular($documento, $bases[$clave])['valor_impuesto'];

        $propio['valor_impuesto'] = $acumulado - ($asignados[$clave] ?? 0);
        $propio['total'] = $propio['impuesto'] === self::RETENCION
            ? $monto - $propio['valor_impuesto']
            : $monto + $propio['valor_impuesto'];
        $asignados[$clave] = $acumulado;

        return $propio;
    }

    /* Boleta y factura no pueden ir en la misma OC. */
    public static function familia(?string $documento): string
    {
        return str_starts_with(self::normalizar($documento), 'BOLETA') ? 'Boleta' : 'Factura';
    }

    /** @return array{0: string, 1: string, 2: int, 3: int}|null */
    private static function regla(?string $documento): ?array
    {
        return match (self::normalizar($documento)) {
            'FACTURA' => [self::IVA, '19.00', 19, 100],
            'BOLETA DE HONORARIOS', 'BOLETA DE HONORARIOS TERCERO' => [self::RETENCION, '15.25', 61, 400],
            'FACTURA EXENTA' => [self::EXENTO, '0.00', 0, 1],
            default => null,
        };
    }

    private static function normalizar(?string $documento): string
    {
        return preg_replace('/\s+/', ' ', strtoupper(Str::ascii(trim((string) $documento)))) ?? '';
    }
}
