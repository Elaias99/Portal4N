<?php

namespace App\Services\Suscripciones;

use App\Models\Asignaciones;
use App\Models\SuscripcionExcepcionFacturacion;
use App\Models\SuscripcionProveedor;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SuscripcionExcepcionFacturacionRegistroService
{
    /**
     * Registra excepciones de facturación por fecha para un período.
     *
     * Cada elemento representa una ejecución puntual de una ruta que,
     * en una fecha específica, será pagada/facturada a otro proveedor.
     *
     * Ejemplo:
     *
     * BH.01
     * 2026-07-25
     * proveedor efectivo: Sanrey
     * transportista efectivo: Claudia
     *
     * La asignación original NO se modifica.
     */
    public function guardarDesdeFormulario(
        array $excepciones,
        int $anio,
        int $mes
    ): array {
        return DB::transaction(function () use (
            $excepciones,
            $anio,
            $mes
        ) {
            $resultado = [
                'recibidas' => count($excepciones),
                'creadas' => 0,
                'actualizadas' => 0,
                'sin_cambios' => 0,
                'omitidas' => 0,
            ];

            foreach ($excepciones as $indice => $datos) {
                if (!is_array($datos)) {
                    $resultado['omitidas']++;
                    continue;
                }

                $estado = $this->guardarExcepcion(
                    $datos,
                    $anio,
                    $mes,
                    $indice
                );

                if ($estado === 'creada') {
                    $resultado['creadas']++;
                    continue;
                }

                if ($estado === 'actualizada') {
                    $resultado['actualizadas']++;
                    continue;
                }

                if ($estado === 'sin_cambios') {
                    $resultado['sin_cambios']++;
                    continue;
                }

                $resultado['omitidas']++;
            }

            return $resultado;
        });
    }

    /**
     * Guarda una excepción individual.
     */
    private function guardarExcepcion(
        array $datos,
        int $anio,
        int $mes,
        int $indice
    ): string {
        $asignacionId = $this->entero(
            $datos['suscripcion_asignacion_id'] ?? null
        );

        $proveedorFacturacionId = $this->entero(
            $datos['suscripcion_proveedor_facturacion_id'] ?? null
        );

        $transportistaOverrideId = $this->entero(
            $datos['suscripcion_transportista_override_id'] ?? null
        );

        $fechaTexto = $this->texto(
            $datos['fecha'] ?? null
        );

        /*
         * Estos campos son obligatorios para que exista
         * una excepción de facturación por fecha.
         */
        if (!$asignacionId) {
            $this->error(
                $indice,
                'suscripcion_asignacion_id',
                'Debes seleccionar una asignación para la excepción de facturación.'
            );
        }

        if (!$fechaTexto) {
            $this->error(
                $indice,
                'fecha',
                'Debes indicar la fecha efectiva de la excepción de facturación.'
            );
        }

        if (!$proveedorFacturacionId) {
            $this->error(
                $indice,
                'suscripcion_proveedor_facturacion_id',
                'Debes seleccionar el proveedor facturador efectivo.'
            );
        }

        $fecha = $this->resolverFecha(
            $fechaTexto,
            $anio,
            $mes,
            $indice
        );

        /*
         * Bloqueamos la asignación durante el registro para mantener
         * consistente la operación dentro de la transacción.
         */
        $asignacion = Asignaciones::query()
            ->with([
                'suscripcionProveedor.cobranzaCompra',
                'transportista',
            ])
            ->lockForUpdate()
            ->find($asignacionId);

        if (!$asignacion) {
            $this->error(
                $indice,
                'suscripcion_asignacion_id',
                'La asignación seleccionada no existe.'
            );
        }

        /*
         * Primera versión:
         *
         * las excepciones por fecha sólo se permiten sobre rutas
         * que representan ejecuciones diarias del calendario.
         */
        if (
            mb_strtoupper(
                trim((string) $asignacion->tipo_asignacion)
            ) !== 'RUTA'
        ) {
            $this->error(
                $indice,
                'suscripcion_asignacion_id',
                'Las excepciones de facturación por fecha sólo pueden aplicarse a asignaciones de tipo RUTA.'
            );
        }

        $proveedorFacturacion = SuscripcionProveedor::query()
            ->with('cobranzaCompra')
            ->find($proveedorFacturacionId);

        if (!$proveedorFacturacion) {
            $this->error(
                $indice,
                'suscripcion_proveedor_facturacion_id',
                'El proveedor facturador efectivo seleccionado no existe.'
            );
        }

        /*
         * Si no se informa costo, guardamos NULL.
         *
         * NULL significa:
         * "utilizar el costo habitual de la asignación".
         */
        $costo = $this->enteroNullable(
            $datos['costo'] ?? null
        );

        /*
         * Los datos documentales pueden venir del formulario.
         *
         * Si llegan vacíos, usamos como snapshot la configuración
         * actual del proveedor efectivo.
         */
        $tipoDocumento = $this->texto(
            $datos['tipo_documento'] ?? null
        ) ?? $this->texto($proveedorFacturacion->tipo);

        $detalleDocumento = $this->texto(
            $datos['detalle_documento'] ?? null
        ) ?? $this->texto(
            $proveedorFacturacion->detalle_documento
        );

        $detalleImpuesto = $this->texto(
            $datos['detalle_impuesto'] ?? null
        ) ?? $this->texto(
            $proveedorFacturacion->detalle_impuesto
        );

        $final = $this->texto(
            $datos['final'] ?? null
        ) ?? $this->texto(
            $proveedorFacturacion->final
        );

        $payload = [
            'suscripcion_proveedor_facturacion_id'
                => $proveedorFacturacion->id,

            'suscripcion_transportista_override_id'
                => $transportistaOverrideId,

            'costo'
                => $costo,

            'tipo_documento'
                => $tipoDocumento,

            'detalle_documento'
                => $detalleDocumento,

            'detalle_impuesto'
                => $detalleImpuesto,

            'final'
                => $final,

            'observacion'
                => $this->texto(
                    $datos['observacion'] ?? null
                ),

            'activo'
                => true,
        ];

        /*
         * La identidad de negocio de una excepción es:
         *
         * asignación + fecha
         *
         * Esto coincide con el UNIQUE definido en la migración.
         */
        $existente = SuscripcionExcepcionFacturacion::query()
            ->where(
                'suscripcion_asignacion_id',
                $asignacion->id
            )
            ->whereDate(
                'fecha',
                $fecha->toDateString()
            )
            ->lockForUpdate()
            ->first();

        if (!$existente) {
            SuscripcionExcepcionFacturacion::create([
                'suscripcion_asignacion_id'
                    => $asignacion->id,

                'fecha'
                    => $fecha->toDateString(),

                ...$payload,
            ]);

            return 'creada';
        }

        /*
         * Si la excepción ya existía para esa ruta/fecha,
         * actualizamos su información.
         *
         * Esto permite que la jefa corrija:
         * - proveedor
         * - transportista
         * - costo
         * - documento
         * - observación
         *
         * sin crear un segundo registro.
         */
        $existente->fill($payload);

        if (!$existente->isDirty()) {
            return 'sin_cambios';
        }

        $existente->save();

        return 'actualizada';
    }

    /**
     * Convierte y valida la fecha enviada.
     *
     * La fecha debe pertenecer exactamente al período
     * que se está preparando.
     */
    private function resolverFecha(
        string $fecha,
        int $anio,
        int $mes,
        int $indice
    ): CarbonImmutable {
        try {
            $fechaCarbon = CarbonImmutable::createFromFormat(
                'Y-m-d',
                $fecha
            )->startOfDay();
        } catch (\Throwable) {
            $this->error(
                $indice,
                'fecha',
                'La fecha indicada para la excepción de facturación no es válida.'
            );
        }

        if (
            (int) $fechaCarbon->year !== $anio
            || (int) $fechaCarbon->month !== $mes
        ) {
            $this->error(
                $indice,
                'fecha',
                'La fecha de la excepción debe pertenecer al período que se está generando.'
            );
        }

        return $fechaCarbon;
    }

    /**
     * Texto normalizado.
     *
     * Los strings vacíos quedan como NULL.
     */
    private function texto(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $valor = trim((string) $valor);

        return $valor === ''
            ? null
            : $valor;
    }

    /**
     * Entero obligatorio / identificador.
     *
     * Valores vacíos o inválidos retornan NULL.
     */
    private function entero(mixed $valor): ?int
    {
        if (
            $valor === null
            || $valor === ''
        ) {
            return null;
        }

        if (!is_numeric($valor)) {
            return null;
        }

        $numero = (int) $valor;

        return $numero > 0
            ? $numero
            : null;
    }

    /**
     * Entero opcional.
     *
     * A diferencia de entero(), acá 0 sí puede ser válido.
     *
     * En costo:
     * NULL = mantener tarifa habitual.
     */
    private function enteroNullable(mixed $valor): ?int
    {
        if (
            $valor === null
            || $valor === ''
        ) {
            return null;
        }

        if (!is_numeric($valor)) {
            return null;
        }

        return (int) $valor;
    }

    /**
     * Lanza un error de validación asociado exactamente
     * al elemento del arreglo enviado por el formulario.
     */
    private function error(
        int $indice,
        string $campo,
        string $mensaje
    ): never {
        throw ValidationException::withMessages([
            "excepciones_facturacion.{$indice}.{$campo}"
                => $mensaje,
        ]);
    }
}