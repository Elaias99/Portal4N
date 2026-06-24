<?php

namespace App\Services\Suscripciones;

use App\Models\Asignaciones;
use App\Models\SuscripcionCantidadMensual;
use App\Models\SuscripcionComisionMensual;
use App\Models\SuscripcionLiquidacionDetalle;
use Carbon\Carbon;

class SuscripcionGeneracionMensualService
{
    public function generar(int $anio, int $mes): array
    {
        $asignaciones = Asignaciones::with([
            'transportista',
            'suscripcionProveedor.cobranzaCompra',
            'opvPuntos',
        ])
            ->where(function ($query) {
                $query->whereNull('generar_automaticamente')
                    ->orWhere('generar_automaticamente', 1);
            })
            ->whereNotIn('tipo_asignacion', [
                'COMISION',
                'CONTENEDOR_AJUSTE',
                'VARIABLE',
            ])
            ->orderBy('codigo')
            ->get();

        $creados = 0;
        $duplicados = 0;

        $cantidadesCreadas = 0;
        $cantidadesDuplicadas = 0;

        $comisionesCreadas = 0;
        $comisionesDuplicadas = 0;

        $opvSinRutas = collect();

        foreach ($asignaciones as $asignacion) {
            $existe = SuscripcionLiquidacionDetalle::where('suscripcion_asignacion_id', $asignacion->id)
                ->where('anio', $anio)
                ->where('mes', $mes)
                ->exists();

            if ($existe) {
                $duplicados++;
                continue;
            }

            if ($this->esAsignacionOPV($asignacion) && $asignacion->opvPuntos->count() === 0) {
                $nombreResponsable = $asignacion->transportista?->nombre_transportista
                    ?? $asignacion->suscripcionProveedor?->cobranzaCompra?->razon_social
                    ?? 'Sin transportista';

                $punto = $asignacion->punto_1 ?? 'Sin punto';

                $opvSinRutas->push($nombreResponsable . ' / ' . $punto);

                continue;
            }

            $calculo = $this->calcularDetalleMensual($asignacion, $anio, $mes, 0);

            SuscripcionLiquidacionDetalle::create([
                'suscripcion_asignacion_id' => $asignacion->id,
                'anio' => $anio,
                'mes' => $mes,
                'codigo' => $asignacion->codigo,
                'costo' => $calculo['costo'],
                'q_calendario' => $calculo['q_calendario'],
                'q_inasistencia' => $calculo['q_inasistencia'],
                'cantidad' => $calculo['cantidad'],
                'total' => $calculo['total'],
            ]);

            $creados++;
        }

        $cantidadesMensuales = SuscripcionCantidadMensual::with('asignacion')
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->orderBy('codigo')
            ->get();

        foreach ($cantidadesMensuales as $cantidadMensual) {
            $existe = SuscripcionLiquidacionDetalle::where('suscripcion_asignacion_id', $cantidadMensual->suscripcion_asignacion_id)
                ->where('anio', $anio)
                ->where('mes', $mes)
                ->exists();

            if ($existe) {
                $duplicados++;
                $cantidadesDuplicadas++;
                continue;
            }

            SuscripcionLiquidacionDetalle::create([
                'suscripcion_asignacion_id' => $cantidadMensual->suscripcion_asignacion_id,
                'anio' => $anio,
                'mes' => $mes,
                'codigo' => $cantidadMensual->codigo ?? $cantidadMensual->asignacion?->codigo,
                'costo' => $cantidadMensual->costo,
                'q_calendario' => 1,
                'q_inasistencia' => 0,
                'cantidad' => $cantidadMensual->cantidad,
                'total' => $cantidadMensual->total,
            ]);

            $creados++;
            $cantidadesCreadas++;
        }

        $comisionesMensuales = SuscripcionComisionMensual::with('asignacion')
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->orderBy('codigo')
            ->get();

        foreach ($comisionesMensuales as $comision) {
            $existe = SuscripcionLiquidacionDetalle::where('suscripcion_asignacion_id', $comision->suscripcion_asignacion_id)
                ->where('anio', $anio)
                ->where('mes', $mes)
                ->exists();

            if ($existe) {
                $duplicados++;
                $comisionesDuplicadas++;
                continue;
            }

            SuscripcionLiquidacionDetalle::create([
                'suscripcion_asignacion_id' => $comision->suscripcion_asignacion_id,
                'anio' => $anio,
                'mes' => $mes,
                'codigo' => $comision->codigo ?? $comision->asignacion?->codigo,
                'costo' => $comision->costo,
                'q_calendario' => 1,
                'q_inasistencia' => 0,
                'cantidad' => $comision->cantidad,
                'total' => $comision->total,
            ]);

            $creados++;
            $comisionesCreadas++;
        }

        return [
            'creados' => $creados,
            'duplicados' => $duplicados,

            'cantidades_creadas' => $cantidadesCreadas,
            'cantidades_duplicadas' => $cantidadesDuplicadas,

            'comisiones_creadas' => $comisionesCreadas,
            'comisiones_duplicadas' => $comisionesDuplicadas,

            'opv_sin_rutas' => $opvSinRutas,
        ];
    }

    private function calcularDetalleMensual(Asignaciones $asignacion, int $anio, int $mes, int $inasistencias = 0): array
    {
        $qCalendario = $this->contarFinesDeSemanaDelMes($anio, $mes);
        $qInasistencia = max(0, $inasistencias);
        $costo = (int) $asignacion->costo;

        if ($this->esAsignacionFijoMensual($asignacion)) {
            return [
                'costo' => $costo,
                'q_calendario' => 1,
                'q_inasistencia' => 0,
                'cantidad' => 1,
                'total' => $costo,
            ];
        }

        if ($this->esAsignacionOPV($asignacion)) {
            $cantidadPuntos = $asignacion->opvPuntos->count();
            $cantidad = max(0, $qCalendario - $qInasistencia) * $cantidadPuntos;

            return [
                'costo' => $costo,
                'q_calendario' => $qCalendario,
                'q_inasistencia' => $qInasistencia,
                'cantidad' => $cantidad,
                'total' => $costo * $cantidad,
            ];
        }

        $cantidad = max(0, $qCalendario - $qInasistencia);

        return [
            'costo' => $costo,
            'q_calendario' => $qCalendario,
            'q_inasistencia' => $qInasistencia,
            'cantidad' => $cantidad,
            'total' => $costo * $cantidad,
        ];
    }

    private function contarFinesDeSemanaDelMes(int $anio, int $mes): int
    {
        $fecha = Carbon::create($anio, $mes, 1)->startOfDay();
        $finMes = $fecha->copy()->endOfMonth();

        $cantidad = 0;

        while ($fecha->lte($finMes)) {
            if ($fecha->isSaturday() || $fecha->isSunday()) {
                $cantidad++;
            }

            $fecha->addDay();
        }

        return $cantidad;
    }

    private function esAsignacionFijoMensual(Asignaciones $asignacion): bool
    {
        return mb_strtoupper(trim((string) $asignacion->tipo_asignacion)) === 'FIJO_MENSUAL';
    }

    private function esAsignacionOPV(Asignaciones $asignacion): bool
    {
        $tipoAsignacion = mb_strtoupper(trim((string) $asignacion->tipo_asignacion));
        $codigo = mb_strtoupper(trim((string) $asignacion->codigo));
        $servicio = mb_strtoupper(trim((string) $asignacion->servicio));
        $origenGasto = mb_strtoupper(trim((string) $asignacion->origen_gasto));

        return $tipoAsignacion === 'OPV'
            || $codigo === 'OPV'
            || str_ends_with($codigo, '.OPV')
            || $servicio === 'OPV'
            || $origenGasto === 'OPV';
    }
}