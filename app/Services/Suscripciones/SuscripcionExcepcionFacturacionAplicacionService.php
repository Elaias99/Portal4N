<?php

namespace App\Services\Suscripciones;

use App\Models\Asignaciones;
use App\Models\SuscripcionExcepcionFacturacion;
use App\Models\SuscripcionLiquidacionDetalle;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SuscripcionExcepcionFacturacionAplicacionService
{
    private const TIPO_ASIGNACION_TECNICA =
        'EXCEPCION_FACTURACION';

    /**
     * Aplica todas las excepciones de facturación por fecha
     * correspondientes al período indicado.
     *
     * Responsabilidad:
     *
     * 1. Detectar las ejecuciones que salen de la ruta original.
     * 2. Recalcular la cantidad pagable del detalle original.
     * 3. Crear/reutilizar una asignación técnica para el receptor.
     * 4. Crear/actualizar el detalle mensual del receptor.
     * 5. Eliminar detalles técnicos que ya no correspondan.
     *
     * Importante:
     *
     * La operación es idempotente.
     * Ejecutar nuevamente el período no vuelve a restar
     * cantidades sobre el resultado anterior.
     */
    public function aplicarPeriodo(
        int $anio,
        int $mes
    ): array {
        return DB::transaction(function () use (
            $anio,
            $mes
        ) {
            $resultado = [
                'excepciones_procesadas' => 0,

                'detalles_origen_actualizados' => 0,
                'detalles_receptor_creados' => 0,
                'detalles_receptor_actualizados' => 0,
                'detalles_receptor_eliminados' => 0,

                'asignaciones_tecnicas_creadas' => 0,
                'asignaciones_tecnicas_reutilizadas' => 0,

                'sin_detalle_origen' => 0,
            ];

            $inicioMes = CarbonImmutable::create(
                $anio,
                $mes,
                1
            )->startOfDay();

            $finMes = $inicioMes
                ->endOfMonth()
                ->endOfDay();

            /*
             * Cargamos también excepciones inactivas.
             *
             * Esto es importante porque, si una excepción estaba
             * aplicada y posteriormente fue desactivada, debemos
             * poder restaurar la cantidad del detalle original.
             */
            $excepcionesPeriodo =
                SuscripcionExcepcionFacturacion::query()
                    ->with([
                        'asignacion.transportista',
                        'proveedorFacturacion.cobranzaCompra',
                        'transportistaOverride',
                    ])
                    ->whereBetween(
                        'fecha',
                        [
                            $inicioMes->toDateString(),
                            $finMes->toDateString(),
                        ]
                    )
                    ->orderBy('fecha')
                    ->orderBy('suscripcion_asignacion_id')
                    ->lockForUpdate()
                    ->get();

            /*
             * Si nunca han existido excepciones en el período,
             * igualmente limpiamos posibles detalles técnicos
             * huérfanos del período.
             */
            if ($excepcionesPeriodo->isEmpty()) {
                $resultado['detalles_receptor_eliminados'] =
                    $this->eliminarDetallesTecnicosNoUtilizados(
                        $anio,
                        $mes,
                        collect()
                    );

                return $resultado;
            }

            $excepcionesActivas =
                $excepcionesPeriodo
                    ->filter(
                        fn (
                            SuscripcionExcepcionFacturacion $excepcion
                        ) => (bool) $excepcion->activo
                    )
                    ->values();

            $resultado['excepciones_procesadas'] =
                $excepcionesActivas->count();

            /*
             * Primero recalculamos las rutas originales.
             *
             * Guardaremos sus detalles en este mapa porque después
             * necesitaremos conocer el costo efectivo del detalle
             * para construir la contraparte del proveedor receptor.
             */
            $detallesOrigen = collect();

            $idsAsignacionesAfectadas =
                $excepcionesPeriodo
                    ->pluck('suscripcion_asignacion_id')
                    ->filter()
                    ->unique()
                    ->values();

            foreach ($idsAsignacionesAfectadas as $asignacionId) {
                $detalleOrigen =
                    SuscripcionLiquidacionDetalle::query()
                        ->where(
                            'suscripcion_asignacion_id',
                            $asignacionId
                        )
                        ->where('anio', $anio)
                        ->where('mes', $mes)
                        ->lockForUpdate()
                        ->first();

                if (!$detalleOrigen) {
                    /*
                     * Puede ocurrir si la ruta no logró generar
                     * detalle mensual por otra condición previa.
                     */
                    $resultado['sin_detalle_origen']++;

                    continue;
                }

                $excepcionesActivasAsignacion =
                    $excepcionesActivas
                        ->where(
                            'suscripcion_asignacion_id',
                            $asignacionId
                        )
                        ->values();

                $cantidadReasignada =
                    $excepcionesActivasAsignacion->count();

                /*
                 * NO utilizamos la cantidad actual como base.
                 *
                 * Si utilizáramos:
                 *
                 * cantidad_actual - excepciones
                 *
                 * volver a ejecutar el proceso produciría:
                 *
                 * 8 -> 7 -> 6 -> 5...
                 *
                 * En cambio reconstruimos siempre desde:
                 *
                 * q_calendario
                 * - q_inasistencia
                 * - excepciones activas
                 */
                $qCalendario =
                    max(
                        0,
                        (int) $detalleOrigen->q_calendario
                    );

                $qInasistencia =
                    max(
                        0,
                        (int) $detalleOrigen->q_inasistencia
                    );

                $cantidadAntesReasignacion =
                    max(
                        0,
                        $qCalendario - $qInasistencia
                    );

                /*
                 * No permitimos trasladar más ejecuciones
                 * que las realmente disponibles.
                 */
                if (
                    $cantidadReasignada
                    > $cantidadAntesReasignacion
                ) {
                    $asignacion =
                        Asignaciones::find($asignacionId);

                    $codigo =
                        $asignacion?->codigo
                        ?? $detalleOrigen->codigo
                        ?? "#{$asignacionId}";

                    throw ValidationException::withMessages([
                        'excepciones_facturacion' =>
                            "La asignación {$codigo} tiene "
                            . "{$cantidadReasignada} excepción(es) "
                            . 'de facturación, pero sólo dispone de '
                            . "{$cantidadAntesReasignacion} "
                            . 'ejecución(es) pagables en el período.',
                    ]);
                }

                $cantidadNueva =
                    $cantidadAntesReasignacion
                    - $cantidadReasignada;

                $costoOrigen =
                    (int) $detalleOrigen->costo;

                $totalNuevo =
                    $costoOrigen
                    * $cantidadNueva;

                $cambio =
                    (int) $detalleOrigen->cantidad
                        !== $cantidadNueva
                    || (int) $detalleOrigen->total
                        !== $totalNuevo;

                if ($cambio) {
                    $detalleOrigen->update([
                        'cantidad' =>
                            $cantidadNueva,

                        'total' =>
                            $totalNuevo,
                    ]);

                    $resultado[
                        'detalles_origen_actualizados'
                    ]++;
                }

                /*
                 * Conservamos el detalle recalculado para utilizar
                 * su costo como costo efectivo cuando la excepción
                 * no trae una tarifa específica.
                 */
                $detallesOrigen->put(
                    (string) $asignacionId,
                    $detalleOrigen->fresh()
                );
            }

            /*
             * Sólo podemos materializar excepciones que tengan
             * un detalle mensual original.
             */
            $excepcionesAplicables =
                $excepcionesActivas
                    ->filter(
                        function (
                            SuscripcionExcepcionFacturacion $excepcion
                        ) use ($detallesOrigen) {
                            return $detallesOrigen->has(
                                (string)
                                $excepcion
                                    ->suscripcion_asignacion_id
                            );
                        }
                    )
                    ->values();

            /*
             * Agrupamos las excepciones que pueden compartir
             * una misma línea receptora.
             *
             * Ejemplo:
             *
             * BH.01:
             * 05-07 -> Sanrey / Claudia
             * 25-07 -> Sanrey / Claudia
             *
             * Se pueden representar como:
             *
             * BH.01
             * cantidad = 2
             *
             * Siempre que proveedor, transportista y costo efectivo
             * sean iguales.
             */
            $gruposReceptores =
                $this->agruparExcepcionesReceptoras(
                    $excepcionesAplicables,
                    $detallesOrigen
                );

            /*
             * IDs de asignaciones técnicas que efectivamente
             * tienen que existir en este período.
             *
             * Posteriormente se usarán para limpiar líneas antiguas.
             */
            $asignacionesTecnicasUtilizadas =
                collect();

            foreach ($gruposReceptores as $grupo) {
                /** @var SuscripcionExcepcionFacturacion $primera */
                $primera =
                    $grupo['excepciones']->first();

                $asignacionOrigen =
                    $primera->asignacion;

                if (!$asignacionOrigen) {
                    continue;
                }

                $proveedorFacturacionId =
                    (int)
                    $primera
                        ->suscripcion_proveedor_facturacion_id;

                /*
                 * Si no hay override de transportista,
                 * mantenemos el transportista de la ruta original.
                 */
                $transportistaEfectivoId =
                    $primera
                        ->suscripcion_transportista_override_id
                    ?: $asignacionOrigen
                        ->suscripcion_transportista_id;

                $costoEfectivo =
                    (int) $grupo['costo'];

                /*
                 * Código INTERNO de la asignación técnica.
                 *
                 * No será el código mostrado en el detalle mensual.
                 *
                 * El detalle conservará BH.01, BH.02, etc.
                 */
                $codigoTecnico =
                    $this->codigoAsignacionTecnica(
                        (int) $asignacionOrigen->id,
                        $proveedorFacturacionId,
                        $transportistaEfectivoId
                            ? (int) $transportistaEfectivoId
                            : null,
                        $costoEfectivo
                    );

                $asignacionTecnica =
                    Asignaciones::query()
                        ->where(
                            'tipo_asignacion',
                            self::TIPO_ASIGNACION_TECNICA
                        )
                        ->where(
                            'codigo',
                            $codigoTecnico
                        )
                        ->lockForUpdate()
                        ->first();

                $payloadAsignacion = [
                    /*
                     * La asignación técnica ya pertenece directamente
                     * al proveedor que debe cobrar la ejecución.
                     */
                    'suscripcion_proveedor_id' =>
                        $proveedorFacturacionId,

                    'suscripcion_transportista_id' =>
                        $transportistaEfectivoId,

                    /*
                     * No participa del calendario automático.
                     */
                    'suscripcion_zona_id' =>
                        null,

                    /*
                     * Conservamos la fotografía operacional
                     * de la ruta original.
                     */
                    'punto_1' =>
                        $asignacionOrigen->punto_1,

                    'origen_gasto' =>
                        $asignacionOrigen->origen_gasto
                        ?: 'Suscripciones',

                    'punto_2' =>
                        $asignacionOrigen->punto_2,

                    /*
                     * El código interno identifica la asignación
                     * técnica de forma estable.
                     */
                    'codigo' =>
                        $codigoTecnico,

                    'servicio' =>
                        $asignacionOrigen->servicio,

                    'costo' =>
                        $costoEfectivo,

                    /*
                     * Conservamos el mismo grupo de prefactura.
                     */
                    'grupo_prefactura' =>
                        $asignacionOrigen
                            ->grupo_prefactura,

                    /*
                     * Nunca debe entrar en generación automática.
                     */
                    'generar_automaticamente' =>
                        0,

                    'tipo_asignacion' =>
                        self::TIPO_ASIGNACION_TECNICA,
                ];

                if (!$asignacionTecnica) {
                    $asignacionTecnica =
                        Asignaciones::create(
                            $payloadAsignacion
                        );

                    $resultado[
                        'asignaciones_tecnicas_creadas'
                    ]++;
                } else {
                    $asignacionTecnica->fill(
                        $payloadAsignacion
                    );

                    if ($asignacionTecnica->isDirty()) {
                        $asignacionTecnica->save();
                    }

                    $resultado[
                        'asignaciones_tecnicas_reutilizadas'
                    ]++;
                }

                $asignacionesTecnicasUtilizadas->push(
                    (int) $asignacionTecnica->id
                );

                $cantidadReceptora =
                    $grupo['excepciones']->count();

                $totalReceptor =
                    $costoEfectivo
                    * $cantidadReceptora;

                /*
                 * El código visible del detalle sigue siendo
                 * el código real de la ruta:
                 *
                 * BH.01
                 *
                 * y no el código técnico EXF-...
                 */
                $codigoDetalle =
                    $asignacionOrigen->codigo;

                $detalleReceptor =
                    SuscripcionLiquidacionDetalle::query()
                        ->where(
                            'suscripcion_asignacion_id',
                            $asignacionTecnica->id
                        )
                        ->where('anio', $anio)
                        ->where('mes', $mes)
                        ->lockForUpdate()
                        ->first();

                if (!$detalleReceptor) {
                    SuscripcionLiquidacionDetalle::create([
                        'suscripcion_asignacion_id' =>
                            $asignacionTecnica->id,

                        'anio' =>
                            $anio,

                        'mes' =>
                            $mes,

                        'codigo' =>
                            $codigoDetalle,

                        'costo' =>
                            $costoEfectivo,

                        /*
                         * Para esta línea técnica,
                         * q_calendario representa las ejecuciones
                         * puntuales trasladadas.
                         */
                        'q_calendario' =>
                            $cantidadReceptora,

                        'q_inasistencia' =>
                            0,

                        'cantidad' =>
                            $cantidadReceptora,

                        'total' =>
                            $totalReceptor,
                    ]);

                    $resultado[
                        'detalles_receptor_creados'
                    ]++;

                    continue;
                }

                $detalleReceptor->fill([
                    'codigo' =>
                        $codigoDetalle,

                    'costo' =>
                        $costoEfectivo,

                    'q_calendario' =>
                        $cantidadReceptora,

                    'q_inasistencia' =>
                        0,

                    'cantidad' =>
                        $cantidadReceptora,

                    'total' =>
                        $totalReceptor,
                ]);

                if ($detalleReceptor->isDirty()) {
                    $detalleReceptor->save();

                    $resultado[
                        'detalles_receptor_actualizados'
                    ]++;
                }
            }

            /*
             * Eliminar detalles técnicos del período que ya no
             * correspondan a ninguna excepción activa.
             *
             * Ejemplo:
             *
             * se registró BH.01 -> Sanrey,
             * luego la excepción se desactiva.
             *
             * Benito vuelve a recuperar su cantidad y la línea
             * técnica de Sanrey debe desaparecer.
             */
            $resultado['detalles_receptor_eliminados'] =
                $this->eliminarDetallesTecnicosNoUtilizados(
                    $anio,
                    $mes,
                    $asignacionesTecnicasUtilizadas
                        ->unique()
                        ->values()
                );

            return $resultado;
        });
    }

    /**
     * Agrupa excepciones que pueden convertirse en una sola
     * línea de liquidación receptora.
     */
    private function agruparExcepcionesReceptoras(
        Collection $excepciones,
        Collection $detallesOrigen
    ): Collection {
        return $excepciones
            ->groupBy(
                function (
                    SuscripcionExcepcionFacturacion $excepcion
                ) use ($detallesOrigen) {
                    $asignacion =
                        $excepcion->asignacion;

                    $detalleOrigen =
                        $detallesOrigen->get(
                            (string)
                            $excepcion
                                ->suscripcion_asignacion_id
                        );

                    /*
                     * Si la excepción no informa costo,
                     * trasladamos la ejecución utilizando el costo
                     * efectivo que actualmente tiene el detalle
                     * mensual de origen.
                     *
                     * Esto conserva el monto que habría recibido
                     * el proveedor original.
                     */
                    $costo =
                        $excepcion->costo !== null
                            ? (int) $excepcion->costo
                            : (int) (
                                $detalleOrigen?->costo
                                ?? $asignacion?->costo
                                ?? 0
                            );

                    $transportista =
                        $excepcion
                            ->suscripcion_transportista_override_id
                        ?: $asignacion
                            ?->suscripcion_transportista_id;

                    return implode('|', [
                        $excepcion
                            ->suscripcion_asignacion_id,

                        $excepcion
                            ->suscripcion_proveedor_facturacion_id,

                        $transportista ?: 0,

                        $costo,
                    ]);
                }
            )
            ->map(
                function (Collection $grupo) use (
                    $detallesOrigen
                ) {
                    /** @var SuscripcionExcepcionFacturacion $primera */
                    $primera =
                        $grupo->first();

                    $detalleOrigen =
                        $detallesOrigen->get(
                            (string)
                            $primera
                                ->suscripcion_asignacion_id
                        );

                    $costo =
                        $primera->costo !== null
                            ? (int) $primera->costo
                            : (int) (
                                $detalleOrigen?->costo
                                ?? $primera
                                    ->asignacion
                                    ?->costo
                                ?? 0
                            );

                    return [
                        'costo' =>
                            $costo,

                        'excepciones' =>
                            $grupo->values(),
                    ];
                }
            )
            ->values();
    }

    /**
     * Genera un código técnico estable para identificar
     * la asignación contenedora de las ejecuciones reasignadas.
     *
     * Ejemplo:
     *
     * EXF-123-45-67-31000
     *
     * 123   = asignación original
     * 45    = proveedor receptor
     * 67    = transportista efectivo
     * 31000 = costo efectivo
     */
    private function codigoAsignacionTecnica(
        int $asignacionOrigenId,
        int $proveedorId,
        ?int $transportistaId,
        int $costo
    ): string {
        return implode('-', [
            'EXF',
            $asignacionOrigenId,
            $proveedorId,
            $transportistaId ?: 0,
            $costo,
        ]);
    }

    /**
     * Elimina únicamente detalles mensuales técnicos que ya
     * no forman parte del conjunto activo de excepciones.
     *
     * No elimina las asignaciones técnicas.
     *
     * Dejarlas disponibles permite reutilizarlas si la excepción
     * se reactiva o vuelve a aparecer en otro período.
     */
    private function eliminarDetallesTecnicosNoUtilizados(
        int $anio,
        int $mes,
        Collection $idsUtilizados
    ): int {
        $query =
            SuscripcionLiquidacionDetalle::query()
                ->where('anio', $anio)
                ->where('mes', $mes)
                ->whereHas(
                    'asignacion',
                    function ($query) {
                        $query->where(
                            'tipo_asignacion',
                            self::TIPO_ASIGNACION_TECNICA
                        );
                    }
                );

        if ($idsUtilizados->isNotEmpty()) {
            $query->whereNotIn(
                'suscripcion_asignacion_id',
                $idsUtilizados->all()
            );
        }

        $detalles =
            $query
                ->lockForUpdate()
                ->get();

        $eliminados =
            $detalles->count();

        foreach ($detalles as $detalle) {
            $detalle->delete();
        }

        return $eliminados;
    }
}