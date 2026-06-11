<?php

namespace App\Services\Suscripciones;

use App\Models\SuscripcionLiquidacionDetalle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SuscripcionPrefacturaAgrupacionService
{
    public const GRUPO_GENERAL = 'GENERAL';

    public function grupoDesdeDetalle(SuscripcionLiquidacionDetalle $detalle): ?string
    {
        $grupo = trim((string) ($detalle->asignacion?->grupo_prefactura ?? ''));

        return $grupo !== '' ? $grupo : null;
    }

    public function etiquetaGrupo(?string $grupoPrefactura): string
    {
        $grupoPrefactura = $this->normalizarGrupo($grupoPrefactura);

        return $grupoPrefactura ?: self::GRUPO_GENERAL;
    }

    public function claveGrupo(?string $grupoPrefactura): string
    {
        $grupoPrefactura = $this->normalizarGrupo($grupoPrefactura);

        return $grupoPrefactura
            ? mb_strtoupper($grupoPrefactura)
            : self::GRUPO_GENERAL;
    }

    public function clavePrefactura(SuscripcionLiquidacionDetalle $detalle): string
    {
        $suscripcionProveedorId = $detalle->asignacion?->suscripcion_proveedor_id ?? 'sin_proveedor';
        $grupoPrefactura = $this->claveGrupo($this->grupoDesdeDetalle($detalle));

        return implode('_', [
            $suscripcionProveedorId,
            $detalle->anio,
            $detalle->mes,
            $grupoPrefactura,
        ]);
    }

    public function agruparPorPrefactura(iterable $detalles): Collection
    {
        return collect($detalles)
            ->groupBy(fn ($detalle) => $this->clavePrefactura($detalle));
    }

    public function aplicarFiltroGrupoEnAsignacion(Builder $query, ?string $grupoPrefactura): Builder
    {
        $grupoPrefactura = $this->normalizarGrupo($grupoPrefactura);

        if ($grupoPrefactura === null) {
            return $query->where(function ($q) {
                $q->whereNull('grupo_prefactura')
                    ->orWhereRaw("TRIM(grupo_prefactura) = ''");
            });
        }

        return $query->whereRaw(
            'UPPER(TRIM(grupo_prefactura)) = ?',
            [mb_strtoupper($grupoPrefactura)]
        );
    }

    private function normalizarGrupo(?string $grupoPrefactura): ?string
    {
        $grupoPrefactura = trim((string) $grupoPrefactura);

        return $grupoPrefactura !== '' ? $grupoPrefactura : null;
    }
}