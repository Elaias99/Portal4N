<?php

namespace App\Services\Suscripciones;

use App\Models\Asignaciones;
use App\Models\SuscripcionAjusteMensual;
use App\Models\SuscripcionLiquidacionDetalle;

class SuscripcionAjusteMensualService
{
    private array $cache = [];

    public function resolverParaDetalle(SuscripcionLiquidacionDetalle $detalle): ?SuscripcionAjusteMensual
    {
        if (!$detalle->suscripcion_asignacion_id) {
            return null;
        }

        return $this->resolverParaAsignacion(
            (int) $detalle->suscripcion_asignacion_id,
            (int) $detalle->anio,
            (int) $detalle->mes
        );
    }

    public function resolverParaAsignacionModel(Asignaciones $asignacion, int $anio, int $mes): ?SuscripcionAjusteMensual
    {
        return $this->resolverParaAsignacion(
            (int) $asignacion->id,
            $anio,
            $mes
        );
    }

    public function resolverParaAsignacion(int $suscripcionAsignacionId, int $anio, int $mes): ?SuscripcionAjusteMensual
    {
        $cacheKey = $suscripcionAsignacionId . '_' . $anio . '_' . $mes;

        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $ajuste = SuscripcionAjusteMensual::with([
                'asignacion.suscripcionProveedor.cobranzaCompra',
                'asignacion.transportista',
                'proveedorFacturacion.cobranzaCompra',
                'transportistaOverride',
            ])
            ->where('suscripcion_asignacion_id', $suscripcionAsignacionId)
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->where('activo', true)
            ->first();

        $this->cache[$cacheKey] = $ajuste;

        return $ajuste;
    }

    public function tieneAjuste(SuscripcionLiquidacionDetalle $detalle): bool
    {
        return $this->resolverParaDetalle($detalle) !== null;
    }

    public function valorAjustado(?SuscripcionAjusteMensual $ajuste, string $campo, mixed $valorOriginal): mixed
    {
        if (!$ajuste) {
            return $valorOriginal;
        }

        $valorAjustado = $ajuste->{$campo} ?? null;

        if ($valorAjustado === null || $valorAjustado === '') {
            return $valorOriginal;
        }

        return $valorAjustado;
    }

    public function tipoDocumentoParaDetalle(SuscripcionLiquidacionDetalle $detalle): ?string
    {
        $ajuste = $this->resolverParaDetalle($detalle);

        return $this->valorAjustado(
            $ajuste,
            'tipo_documento',
            $detalle->asignacion?->suscripcionProveedor?->tipo
        );
    }

    public function detalleDocumentoParaDetalle(SuscripcionLiquidacionDetalle $detalle): ?string
    {
        $ajuste = $this->resolverParaDetalle($detalle);

        return $this->valorAjustado(
            $ajuste,
            'detalle_documento',
            $detalle->asignacion?->suscripcionProveedor?->detalle_documento
        );
    }

    public function detalleImpuestoParaDetalle(SuscripcionLiquidacionDetalle $detalle): ?string
    {
        $ajuste = $this->resolverParaDetalle($detalle);

        return $this->valorAjustado(
            $ajuste,
            'detalle_impuesto',
            $detalle->asignacion?->suscripcionProveedor?->detalle_impuesto
        );
    }

    public function finalParaDetalle(SuscripcionLiquidacionDetalle $detalle): ?string
    {
        $ajuste = $this->resolverParaDetalle($detalle);

        return $this->valorAjustado(
            $ajuste,
            'final',
            $detalle->asignacion?->suscripcionProveedor?->final
        );
    }

    public function proveedorFacturacionParaDetalle(SuscripcionLiquidacionDetalle $detalle)
    {
        $ajuste = $this->resolverParaDetalle($detalle);

        if ($ajuste?->proveedorFacturacion) {
            return $ajuste->proveedorFacturacion;
        }

        return $detalle->asignacion?->suscripcionProveedor;
    }

    public function transportistaParaDetalle(SuscripcionLiquidacionDetalle $detalle)
    {
        $ajuste = $this->resolverParaDetalle($detalle);

        if ($ajuste?->transportistaOverride) {
            return $ajuste->transportistaOverride;
        }

        return $detalle->asignacion?->transportista;
    }
}