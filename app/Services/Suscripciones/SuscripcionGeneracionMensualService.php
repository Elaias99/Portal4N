<?php

namespace App\Services\Suscripciones;

use App\Models\Asignaciones;
use App\Models\SuscripcionCantidadMensual;
use App\Models\SuscripcionComisionMensual;
use App\Models\SuscripcionLiquidacionDetalle;
use App\Models\SuscripcionZonaDiaOperativo;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class SuscripcionGeneracionMensualService
{
    public function generar(int $anio, int $mes): array
    {
        $asignaciones = Asignaciones::with([
            'transportista',
            'suscripcionProveedor.cobranzaCompra',
            'opvPuntos',
            'zona',
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

        /*
        * Construir las fechas exactas de sábado y domingo
        * correspondientes al período solicitado.
        */
        $fechaCursor = Carbon::create(
            $anio,
            $mes,
            1
        )->startOfDay();

        $finMes = $fechaCursor
            ->copy()
            ->endOfMonth();

        $fechasFinSemana = [];

        while ($fechaCursor->lte($finMes)) {
            if (
                $fechaCursor->isSaturday()
                || $fechaCursor->isSunday()
            ) {
                $fechasFinSemana[] =
                    $fechaCursor->toDateString();
            }

            $fechaCursor->addDay();
        }

        $cantidadFechasEsperadas =
            count($fechasFinSemana);

        /*
        * Resumir el calendario guardado por zona.
        *
        * fechas_configuradas:
        * cantidad de sábados y domingos registrados.
        *
        * dias_con_despacho:
        * cantidad de fechas marcadas con hubo_despacho = 1.
        */
        $calendarioZonal =
            SuscripcionZonaDiaOperativo::query()
                ->select('suscripcion_zona_id')
                ->selectRaw(
                    'COUNT(*) AS fechas_configuradas'
                )
                ->selectRaw(
                    'SUM(
                        CASE
                            WHEN hubo_despacho = 1 THEN 1
                            ELSE 0
                        END
                    ) AS dias_con_despacho'
                )
                ->whereIn(
                    'fecha',
                    $fechasFinSemana
                )
                ->groupBy('suscripcion_zona_id')
                ->get()
                ->keyBy(
                    fn ($registro) =>
                        (int) $registro->suscripcion_zona_id
                );

        /*
        * Sólo requieren calendario zonal:
        *
        * - rutas normales;
        * - OPV que tengan puntos configurados.
        *
        * Los fijos mensuales mantienen cantidad 1.
        */
        $asignacionesConCalendario =
            $asignaciones->filter(
                function (Asignaciones $asignacion) {
                    if (
                        $this->esAsignacionFijoMensual(
                            $asignacion
                        )
                    ) {
                        return false;
                    }

                    if (
                        $this->esAsignacionReposiciones(
                            $asignacion
                        )
                    ) {
                        return false;
                    }

                    if (
                        $this->esAsignacionOPV($asignacion)
                        && $asignacion->opvPuntos->isEmpty()
                    ) {
                        return false;
                    }

                    return true;
                }
            );

        /*
        * Detectar asignaciones operativas sin zona.
        */
        $asignacionesSinZona =
            $asignacionesConCalendario
                ->filter(
                    fn (Asignaciones $asignacion) =>
                        empty(
                            $asignacion->suscripcion_zona_id
                        )
                );

        $erroresCalendario = collect();

        if ($asignacionesSinZona->isNotEmpty()) {
            $asignacionesDescripcion =
                $asignacionesSinZona
                    ->take(15)
                    ->map(function (
                        Asignaciones $asignacion
                    ) {
                        return
                            "#{$asignacion->id} "
                            . ($asignacion->codigo
                                ?? 'Sin código');
                    })
                    ->implode(', ');

            $erroresCalendario->push(
                'Existen asignaciones automáticas sin zona: '
                . $asignacionesDescripcion
                . '.'
            );
        }

        /*
        * Revisar que cada zona utilizada tenga todas las fechas
        * de fin de semana configuradas.
        */
        $asignacionesPorZona =
            $asignacionesConCalendario
                ->filter(
                    fn (Asignaciones $asignacion) =>
                        !empty(
                            $asignacion->suscripcion_zona_id
                        )
                )
                ->groupBy(
                    fn (Asignaciones $asignacion) =>
                        (int) $asignacion
                            ->suscripcion_zona_id
                );

        foreach (
            $asignacionesPorZona
            as $zonaId => $asignacionesZona
        ) {
            $resumenZona =
                $calendarioZonal->get(
                    (int) $zonaId
                );

            $asignacionReferencia =
                $asignacionesZona->first();

            $zona =
                $asignacionReferencia?->zona;

            $descripcionZona = $zona
                ? "Zona {$zona->numero_zona} - {$zona->despacho}"
                : "Zona ID {$zonaId}";

            $fechasConfiguradas = $resumenZona
                ? (int) $resumenZona
                    ->fechas_configuradas
                : 0;

            if (
                $fechasConfiguradas
                !== $cantidadFechasEsperadas
            ) {
                $erroresCalendario->push(
                    "{$descripcionZona} tiene "
                    . "{$fechasConfiguradas} de "
                    . "{$cantidadFechasEsperadas} "
                    . 'fechas configuradas.'
                );
            }
        }

        /*
        * Detener la generación antes de crear detalles si el
        * calendario zonal está incompleto.
        */
        if ($erroresCalendario->isNotEmpty()) {
            throw ValidationException::withMessages([
                'zonas_operativas' =>
                    'No se puede generar el período porque '
                    . 'el calendario zonal no está completo. '
                    . $erroresCalendario
                        ->unique()
                        ->implode(' '),
            ]);
        }

        $creados = 0;
        $duplicados = 0;

        $cantidadesCreadas = 0;
        $cantidadesDuplicadas = 0;

        $comisionesCreadas = 0;
        $comisionesDuplicadas = 0;

        $opvSinRutas = collect();

        /*
        * Generar asignaciones automáticas.
        */
        foreach ($asignaciones as $asignacion) {
            $existe =
                SuscripcionLiquidacionDetalle::query()
                    ->where(
                        'suscripcion_asignacion_id',
                        $asignacion->id
                    )
                    ->where('anio', $anio)
                    ->where('mes', $mes)
                    ->exists();

            if ($existe) {
                $duplicados++;
                continue;
            }

            /*
            * Las asignaciones OPV sin puntos no se generan.
            */
            if (
                $this->esAsignacionOPV($asignacion)
                && $asignacion->opvPuntos->count() === 0
            ) {
                $nombreResponsable =
                    $asignacion->transportista
                        ?->nombre_transportista
                    ?? $asignacion
                        ->suscripcionProveedor
                        ?->cobranzaCompra
                        ?->razon_social
                    ?? 'Sin transportista';

                $punto =
                    $asignacion->punto_1
                    ?? 'Sin punto';

                $opvSinRutas->push(
                    $nombreResponsable
                    . ' / '
                    . $punto
                );

                continue;
            }

            /*
            * Los fijos mensuales siempre tienen cantidad 1.
            *
            * El resto utiliza la cantidad de días con despacho
            * configurados para su zona.
            */


            if (
                $this->esAsignacionFijoMensual(
                    $asignacion
                )
            ) {
                $qCalendario = 1;
            } elseif (
                $this->esAsignacionReposiciones(
                    $asignacion
                )
            ) {
                $qCalendario =
                    $cantidadFechasEsperadas;
            } else {
                $resumenZona =
                    $calendarioZonal->get(
                        (int) $asignacion
                            ->suscripcion_zona_id
                    );

                $qCalendario =
                    (int) $resumenZona
                        ->dias_con_despacho;
            }






            $calculo =
                $this->calcularDetalleMensual(
                    $asignacion,
                    $qCalendario,
                    0
                );

            SuscripcionLiquidacionDetalle::create([
                'suscripcion_asignacion_id' =>
                    $asignacion->id,

                'anio' =>
                    $anio,

                'mes' =>
                    $mes,

                'codigo' =>
                    $asignacion->codigo,

                'costo' =>
                    $calculo['costo'],

                'q_calendario' =>
                    $calculo['q_calendario'],

                'q_inasistencia' =>
                    $calculo['q_inasistencia'],

                'cantidad' =>
                    $calculo['cantidad'],

                'total' =>
                    $calculo['total'],
            ]);

            $creados++;
        }

        /*
        * Generar cantidades variables mensuales.
        */
        $cantidadesMensuales =
            SuscripcionCantidadMensual::with(
                'asignacion'
            )
                ->where('anio', $anio)
                ->where('mes', $mes)
                ->orderBy('codigo')
                ->get();

        foreach (
            $cantidadesMensuales
            as $cantidadMensual
        ) {
            $existe =
                SuscripcionLiquidacionDetalle::query()
                    ->where(
                        'suscripcion_asignacion_id',
                        $cantidadMensual
                            ->suscripcion_asignacion_id
                    )
                    ->where('anio', $anio)
                    ->where('mes', $mes)
                    ->exists();

            if ($existe) {
                $duplicados++;
                $cantidadesDuplicadas++;

                continue;
            }

            SuscripcionLiquidacionDetalle::create([
                'suscripcion_asignacion_id' =>
                    $cantidadMensual
                        ->suscripcion_asignacion_id,

                'anio' =>
                    $anio,

                'mes' =>
                    $mes,

                'codigo' =>
                    $cantidadMensual->codigo
                    ?? $cantidadMensual
                        ->asignacion
                        ?->codigo,

                'costo' =>
                    $cantidadMensual->costo,

                'q_calendario' =>
                    1,

                'q_inasistencia' =>
                    0,

                'cantidad' =>
                    $cantidadMensual->cantidad,

                'total' =>
                    $cantidadMensual->total,
            ]);

            $creados++;
            $cantidadesCreadas++;
        }

        /*
        * Generar pagos adicionales mensuales.
        */
        $comisionesMensuales =
            SuscripcionComisionMensual::with(
                'asignacion'
            )
                ->where('anio', $anio)
                ->where('mes', $mes)
                ->orderBy('codigo')
                ->get();

        foreach (
            $comisionesMensuales
            as $comision
        ) {
            $existe =
                SuscripcionLiquidacionDetalle::query()
                    ->where(
                        'suscripcion_asignacion_id',
                        $comision
                            ->suscripcion_asignacion_id
                    )
                    ->where('anio', $anio)
                    ->where('mes', $mes)
                    ->exists();

            if ($existe) {
                $duplicados++;
                $comisionesDuplicadas++;

                continue;
            }

            SuscripcionLiquidacionDetalle::create([
                'suscripcion_asignacion_id' =>
                    $comision
                        ->suscripcion_asignacion_id,

                'anio' =>
                    $anio,

                'mes' =>
                    $mes,

                'codigo' =>
                    $comision->codigo
                    ?? $comision
                        ->asignacion
                        ?->codigo,

                'costo' =>
                    $comision->costo,

                'q_calendario' =>
                    1,

                'q_inasistencia' =>
                    0,

                'cantidad' =>
                    $comision->cantidad,

                'total' =>
                    $comision->total,
            ]);

            $creados++;
            $comisionesCreadas++;
        }

        return [
            'creados' =>
                $creados,

            'duplicados' =>
                $duplicados,

            'cantidades_creadas' =>
                $cantidadesCreadas,

            'cantidades_duplicadas' =>
                $cantidadesDuplicadas,

            'comisiones_creadas' =>
                $comisionesCreadas,

            'comisiones_duplicadas' =>
                $comisionesDuplicadas,

            'opv_sin_rutas' =>
                $opvSinRutas,
        ];
    }

    private function calcularDetalleMensual(Asignaciones $asignacion, int $qCalendario, int $inasistencias = 0): array 
    {
        $qCalendario =
            max(0, $qCalendario);

        $qInasistencia =
            max(0, $inasistencias);

        $costo =
            (int) $asignacion->costo;

        /*
        * Los fijos mensuales no dependen del calendario zonal.
        */
        if (
            $this->esAsignacionFijoMensual(
                $asignacion
            )
        ) {
            return [
                'costo' =>
                    $costo,

                'q_calendario' =>
                    1,

                'q_inasistencia' =>
                    0,

                'cantidad' =>
                    1,

                'total' =>
                    $costo,
            ];
        }

        /*
        * OPV:
        *
        * días con despacho de la zona
        * menos inasistencias
        * multiplicado por puntos OPV.
        */
        if ($this->esAsignacionOPV($asignacion)) {
            $cantidadPuntos =
                $asignacion
                    ->opvPuntos
                    ->count();

            $cantidad =
                max(
                    0,
                    $qCalendario
                    - $qInasistencia
                )
                * $cantidadPuntos;

            return [
                'costo' =>
                    $costo,

                'q_calendario' =>
                    $qCalendario,

                'q_inasistencia' =>
                    $qInasistencia,

                'cantidad' =>
                    $cantidad,

                'total' =>
                    $costo * $cantidad,
            ];
        }

        /*
        * Ruta normal:
        *
        * días con despacho de la zona
        * menos inasistencias individuales.
        */
        $cantidad = max(
            0,
            $qCalendario
            - $qInasistencia
        );

        return [
            'costo' =>
                $costo,

            'q_calendario' =>
                $qCalendario,

            'q_inasistencia' =>
                $qInasistencia,

            'cantidad' =>
                $cantidad,

            'total' =>
                $costo * $cantidad,
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

    private function esAsignacionReposiciones(
        Asignaciones $asignacion
    ): bool {
        return mb_strtoupper(
            trim((string) $asignacion->codigo)
        ) === 'REPOSICIONES';
    }



}