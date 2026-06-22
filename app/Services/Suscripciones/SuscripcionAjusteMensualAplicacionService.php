<?php

namespace App\Services\Suscripciones;

use App\Models\Asignaciones;
use App\Models\SuscripcionAjusteMensual;
use App\Models\SuscripcionLiquidacionDetalle;
use Illuminate\Support\Facades\DB;

class SuscripcionAjusteMensualAplicacionService
{
    private const TIPOS_LINEA_ADICIONAL = [
        'LINEA_ADICIONAL',
        'PAGO_ADICIONAL',
        'REEMPLAZO',
    ];

    private const TIPOS_IGNORADOS = [
        'COMISION',
    ];

    public function aplicarPeriodo(int $anio, int $mes): array
    {
        return DB::transaction(function () use ($anio, $mes) {
            $resultado = [
                'ajustes_procesados' => 0,

                'detalles_actualizados' => 0,
                'detalles_sin_cambios' => 0,

                'lineas_adicionales_creadas' => 0,
                'lineas_adicionales_actualizadas' => 0,
                'lineas_adicionales_sin_cambios' => 0,

                'facturacion_registrada' => 0,

                'sin_detalle' => 0,
                'sin_asignacion' => 0,
                'ignorados' => 0,
            ];

            $ajustes = SuscripcionAjusteMensual::with([
                    'asignacion.suscripcionProveedor.cobranzaCompra',
                    'asignacion.transportista',
                    'asignacion.opvPuntos',
                    'proveedorFacturacion.cobranzaCompra',
                    'transportistaOverride',
                ])
                ->where('anio', $anio)
                ->where('mes', $mes)
                ->where('activo', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($ajustes as $ajuste) {
                $resultado['ajustes_procesados']++;

                $tipo = $this->normalizarTipo($ajuste->tipo_ajuste);

                if (in_array($tipo, self::TIPOS_IGNORADOS, true)) {
                    $resultado['ignorados']++;
                    continue;
                }

                if ($this->esTipoLineaAdicional($tipo)) {
                    $estado = $this->aplicarLineaAdicional($ajuste);

                    if ($estado === 'creado') {
                        $resultado['lineas_adicionales_creadas']++;
                    } elseif ($estado === 'actualizado') {
                        $resultado['lineas_adicionales_actualizadas']++;
                    } elseif ($estado === 'sin_cambios') {
                        $resultado['lineas_adicionales_sin_cambios']++;
                    } elseif ($estado === 'sin_asignacion') {
                        $resultado['sin_asignacion']++;
                    }

                    continue;
                }

                $estado = $this->aplicarSobreDetalleExistente($ajuste);

                if ($estado === 'actualizado') {
                    $resultado['detalles_actualizados']++;
                } elseif ($estado === 'solo_facturacion') {
                    $resultado['facturacion_registrada']++;
                } elseif ($estado === 'sin_cambios') {
                    $resultado['detalles_sin_cambios']++;
                } elseif ($estado === 'sin_detalle') {
                    $resultado['sin_detalle']++;
                }
            }

            return $resultado;
        });
    }

    private function aplicarSobreDetalleExistente(SuscripcionAjusteMensual $ajuste): string
    {
        $detalle = SuscripcionLiquidacionDetalle::with([
                'asignacion.opvPuntos',
                'asignacion.suscripcionProveedor.cobranzaCompra',
                'asignacion.transportista',
            ])
            ->where('suscripcion_asignacion_id', $ajuste->suscripcion_asignacion_id)
            ->where('anio', $ajuste->anio)
            ->where('mes', $ajuste->mes)
            ->lockForUpdate()
            ->first();

        if (!$detalle) {
            return 'sin_detalle';
        }

        $payload = $this->payloadDetalleDesdeAjuste($ajuste, $detalle);

        if (empty($payload)) {
            return $this->tieneDatosFacturacion($ajuste)
                ? 'solo_facturacion'
                : 'sin_cambios';
        }

        $detalle->fill($payload);

        if ($detalle->isDirty()) {
            $detalle->save();

            return 'actualizado';
        }

        return $this->tieneDatosFacturacion($ajuste)
            ? 'solo_facturacion'
            : 'sin_cambios';
    }

    private function aplicarLineaAdicional(SuscripcionAjusteMensual $ajuste): string
    {
        $asignacion = $ajuste->asignacion;

        if (!$asignacion) {
            return 'sin_asignacion';
        }

        $detalle = SuscripcionLiquidacionDetalle::where('suscripcion_asignacion_id', $asignacion->id)
            ->where('anio', $ajuste->anio)
            ->where('mes', $ajuste->mes)
            ->lockForUpdate()
            ->first();

        $existe = $detalle !== null;

        if (!$detalle) {
            $detalle = new SuscripcionLiquidacionDetalle([
                'suscripcion_asignacion_id' => $asignacion->id,
                'anio' => $ajuste->anio,
                'mes' => $ajuste->mes,
            ]);
        }

        $payload = $this->payloadLineaAdicional($ajuste, $asignacion);

        $detalle->fill($payload);

        if (!$existe) {
            $detalle->save();

            return 'creado';
        }

        if ($detalle->isDirty()) {
            $detalle->save();

            return 'actualizado';
        }

        return 'sin_cambios';
    }

    private function payloadDetalleDesdeAjuste(
        SuscripcionAjusteMensual $ajuste,
        SuscripcionLiquidacionDetalle $detalle
    ): array {
        $payload = [];

        $codigo = $this->valorTexto($ajuste->codigo);

        if ($codigo !== null) {
            $payload['codigo'] = $codigo;
        }

        if ($ajuste->costo !== null) {
            $payload['costo'] = (int) $ajuste->costo;
        }

        if ($ajuste->q_calendario !== null) {
            $payload['q_calendario'] = (int) $ajuste->q_calendario;
        }

        if ($ajuste->q_inasistencia !== null) {
            $payload['q_inasistencia'] = (int) $ajuste->q_inasistencia;
        }

        if ($ajuste->cantidad !== null) {
            $payload['cantidad'] = (int) $ajuste->cantidad;
        } elseif ($this->debeRecalcularCantidad($ajuste)) {
            $payload['cantidad'] = $this->calcularCantidadDesdeAjuste($ajuste, $detalle);
        }

        if ($ajuste->total !== null) {
            $payload['total'] = (int) $ajuste->total;
        } elseif (array_key_exists('cantidad', $payload) || array_key_exists('costo', $payload)) {
            $costo = (int) ($payload['costo'] ?? $detalle->costo);
            $cantidad = (int) ($payload['cantidad'] ?? $detalle->cantidad);

            $payload['total'] = $costo * $cantidad;
        }

        return $payload;
    }

    private function payloadLineaAdicional(
        SuscripcionAjusteMensual $ajuste,
        Asignaciones $asignacion
    ): array {
        $codigo = $this->valorTexto($ajuste->codigo)
            ?? $this->valorTexto($asignacion->codigo)
            ?? 'LINEA_ADICIONAL';

        $costo = (int) ($ajuste->costo ?? $asignacion->costo ?? 0);
        $qCalendario = (int) ($ajuste->q_calendario ?? 1);
        $qInasistencia = (int) ($ajuste->q_inasistencia ?? 0);

        $cantidad = $ajuste->cantidad !== null
            ? (int) $ajuste->cantidad
            : max(0, $qCalendario - $qInasistencia);

        $total = $ajuste->total !== null
            ? (int) $ajuste->total
            : $costo * $cantidad;

        return [
            'codigo' => $codigo,
            'costo' => $costo,
            'q_calendario' => $qCalendario,
            'q_inasistencia' => $qInasistencia,
            'cantidad' => $cantidad,
            'total' => $total,
        ];
    }

    private function calcularCantidadDesdeAjuste(
        SuscripcionAjusteMensual $ajuste,
        SuscripcionLiquidacionDetalle $detalle
    ): int {
        $qCalendario = (int) ($ajuste->q_calendario ?? $detalle->q_calendario ?? 0);
        $qInasistencia = (int) ($ajuste->q_inasistencia ?? $detalle->q_inasistencia ?? 0);

        if ($this->esCodigoValorFijo((string) ($ajuste->codigo ?? $detalle->codigo))) {
            return 1;
        }

        $asignacion = $detalle->asignacion;

        if ($asignacion && $this->esAsignacionOPV($asignacion)) {
            $cantidadPuntos = $asignacion->opvPuntos->count();

            return max(0, $qCalendario - $qInasistencia) * $cantidadPuntos;
        }

        return max(0, $qCalendario - $qInasistencia);
    }

    private function debeRecalcularCantidad(SuscripcionAjusteMensual $ajuste): bool
    {
        return $ajuste->q_calendario !== null
            || $ajuste->q_inasistencia !== null;
    }

    private function esTipoLineaAdicional(string $tipo): bool
    {
        return in_array($tipo, self::TIPOS_LINEA_ADICIONAL, true);
    }

    private function tieneDatosFacturacion(SuscripcionAjusteMensual $ajuste): bool
    {
        return $ajuste->suscripcion_proveedor_facturacion_id !== null
            || $this->valorTexto($ajuste->tipo_documento) !== null
            || $this->valorTexto($ajuste->detalle_documento) !== null
            || $this->valorTexto($ajuste->detalle_impuesto) !== null
            || $this->valorTexto($ajuste->final) !== null;
    }

    private function normalizarTipo(?string $tipo): string
    {
        $tipo = mb_strtoupper(trim((string) $tipo));
        $tipo = str_replace([' ', '-'], '_', $tipo);

        return $tipo;
    }

    private function valorTexto(mixed $valor): ?string
    {
        $valor = trim((string) ($valor ?? ''));

        return $valor === '' ? null : $valor;
    }

    private function esCodigoValorFijo(string $codigo): bool
    {
        $codigo = mb_strtoupper(trim($codigo));

        return str_ends_with($codigo, '.COM')
            || str_contains($codigo, 'COMISION');
    }

    private function esAsignacionOPV(Asignaciones $asignacion): bool
    {
        $codigo = mb_strtoupper(trim((string) $asignacion->codigo));
        $servicio = mb_strtoupper(trim((string) $asignacion->servicio));
        $origenGasto = mb_strtoupper(trim((string) $asignacion->origen_gasto));

        return $codigo === 'OPV'
            || str_ends_with($codigo, '.OPV')
            || $servicio === 'OPV'
            || $origenGasto === 'OPV';
    }
}