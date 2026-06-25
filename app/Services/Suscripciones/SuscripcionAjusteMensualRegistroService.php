<?php

namespace App\Services\Suscripciones;

use App\Models\Asignaciones;
use App\Models\SuscripcionAjusteMensual;
use App\Models\SuscripcionProveedor;
use App\Models\SuscripcionConceptoPagoVariable;
use Illuminate\Support\Facades\DB;

class SuscripcionAjusteMensualRegistroService
{
    public function guardarDesdeFormulario(array $ajustes, int $anio, int $mes): array
    {
        return DB::transaction(function () use ($ajustes, $anio, $mes) {
            $resultado = [
                'recibidos' => count($ajustes),
                'creados' => 0,
                'actualizados' => 0,
                'omitidos' => 0,
                'asignaciones_creadas' => 0,
                'asignaciones_reutilizadas' => 0,
            ];

            foreach ($ajustes as $ajuste) {
                $tipo = $this->normalizarTipo($ajuste['tipo_ajuste'] ?? null);

                if ($tipo === '') {
                    $resultado['omitidos']++;
                    continue;
                }




                if ($tipo === 'INASISTENCIA') {
                    $estado = $this->guardarInasistencia($ajuste, $anio, $mes);
                } elseif ($tipo === 'FIJO_MENSUAL') {
                    $estado = $this->guardarFijoMensual($ajuste, $anio, $mes);
                } elseif ($tipo === 'FACTURACION') {
                    $estado = $this->guardarFacturacion($ajuste, $anio, $mes);
                } elseif ($this->esLineaAdicional($tipo)) {
                    $estado = $this->guardarLineaAdicional($ajuste, $anio, $mes);
                } else {
                    $resultado['omitidos']++;
                    continue;
                }







                if (($estado['ajuste'] ?? null) === 'creado') {
                    $resultado['creados']++;
                }

                if (($estado['ajuste'] ?? null) === 'actualizado') {
                    $resultado['actualizados']++;
                }

                if (($estado['asignacion'] ?? null) === 'creada') {
                    $resultado['asignaciones_creadas']++;
                }

                if (($estado['asignacion'] ?? null) === 'reutilizada') {
                    $resultado['asignaciones_reutilizadas']++;
                }
            }

            return $resultado;
        });
    }

    private function guardarInasistencia(array $data, int $anio, int $mes): array
    {
        $asignacion = Asignaciones::findOrFail((int) $data['suscripcion_asignacion_id']);

        $payload = [
            'tipo_ajuste' => 'INASISTENCIA',

            'punto_1' => $data['punto_1'] ?? $asignacion->punto_1,
            'origen_gasto' => $data['origen_gasto'] ?? $asignacion->origen_gasto,
            'punto_2' => $data['punto_2'] ?? $asignacion->punto_2,
            'codigo' => $data['codigo'] ?? $asignacion->codigo,
            'servicio' => $data['servicio'] ?? $asignacion->servicio,

            'suscripcion_transportista_override_id' => $data['suscripcion_transportista_override_id'] ?? null,
            'suscripcion_proveedor_facturacion_id' => $data['suscripcion_proveedor_facturacion_id'] ?? null,

            'tipo_documento' => $data['tipo_documento'] ?? null,
            'detalle_documento' => $data['detalle_documento'] ?? null,
            'detalle_impuesto' => $data['detalle_impuesto'] ?? null,
            'final' => $data['final'] ?? null,

            'grupo_prefactura' => $data['grupo_prefactura'] ?? $asignacion->grupo_prefactura,

            'costo' => $this->entero($data['costo'] ?? $asignacion->costo),
            'q_calendario' => $this->entero($data['q_calendario'] ?? null),
            'q_inasistencia' => $this->entero($data['q_inasistencia'] ?? 0),
            'cantidad' => $this->entero($data['cantidad'] ?? null),
            'total' => $this->entero($data['total'] ?? null),

            'observacion' => $data['observacion'] ?? null,
            'activo' => true,
        ];

        return [
            'ajuste' => $this->guardarOActualizarAjuste(
                $asignacion->id,
                $anio,
                $mes,
                'INASISTENCIA',
                $payload
            ),
        ];
    }



    private function guardarFijoMensual(array $data, int $anio, int $mes): array
    {
        $asignacion = Asignaciones::findOrFail((int) $data['suscripcion_asignacion_id']);

        $costoMensual = $this->entero($data['costo'] ?? $data['total'] ?? $asignacion->costo ?? 0) ?? 0;

        $payload = [
            'tipo_ajuste' => 'FIJO_MENSUAL',

            'punto_1' => $data['punto_1'] ?? $asignacion->punto_1,
            'origen_gasto' => $data['origen_gasto'] ?? $asignacion->origen_gasto,
            'punto_2' => $data['punto_2'] ?? $asignacion->punto_2,
            'codigo' => $data['codigo'] ?? $asignacion->codigo,
            'servicio' => $data['servicio'] ?? $asignacion->servicio,

            'suscripcion_transportista_override_id' => $data['suscripcion_transportista_override_id'] ?? null,
            'suscripcion_proveedor_facturacion_id' => $data['suscripcion_proveedor_facturacion_id'] ?? null,

            'tipo_documento' => $data['tipo_documento'] ?? null,
            'detalle_documento' => $data['detalle_documento'] ?? null,
            'detalle_impuesto' => $data['detalle_impuesto'] ?? null,
            'final' => $data['final'] ?? null,

            'grupo_prefactura' => $data['grupo_prefactura'] ?? $asignacion->grupo_prefactura,

            'costo' => $costoMensual,
            'q_calendario' => 1,
            'q_inasistencia' => 0,
            'cantidad' => 1,
            'total' => $costoMensual,

            'observacion' => $data['observacion'] ?? null,
            'activo' => true,
        ];

        return [
            'ajuste' => $this->guardarOActualizarAjuste(
                $asignacion->id,
                $anio,
                $mes,
                'FIJO_MENSUAL',
                $payload
            ),
        ];
    }





    private function guardarFacturacion(array $data, int $anio, int $mes): array
    {
        $asignacion = Asignaciones::findOrFail((int) $data['suscripcion_asignacion_id']);

        $proveedorFacturacion = null;

        if (!empty($data['suscripcion_proveedor_facturacion_id'])) {
            $proveedorFacturacion = SuscripcionProveedor::findOrFail(
                (int) $data['suscripcion_proveedor_facturacion_id']
            );
        }

        $payload = [
            'tipo_ajuste' => 'FACTURACION',

            'punto_1' => $data['punto_1'] ?? $asignacion->punto_1,
            'origen_gasto' => $data['origen_gasto'] ?? $asignacion->origen_gasto,
            'punto_2' => $data['punto_2'] ?? $asignacion->punto_2,
            'codigo' => $data['codigo'] ?? $asignacion->codigo,
            'servicio' => $data['servicio'] ?? $asignacion->servicio,

            'suscripcion_transportista_override_id' => $data['suscripcion_transportista_override_id'] ?? null,
            'suscripcion_proveedor_facturacion_id' => $data['suscripcion_proveedor_facturacion_id'] ?? null,

            'tipo_documento' => $data['tipo_documento'] ?? $proveedorFacturacion?->tipo,
            'detalle_documento' => $data['detalle_documento'] ?? $proveedorFacturacion?->detalle_documento,
            'detalle_impuesto' => $data['detalle_impuesto'] ?? $proveedorFacturacion?->detalle_impuesto,
            'final' => $data['final'] ?? $proveedorFacturacion?->final,

            'grupo_prefactura' => $data['grupo_prefactura'] ?? $asignacion->grupo_prefactura,

            'costo' => $this->entero($data['costo'] ?? null),
            'q_calendario' => $this->entero($data['q_calendario'] ?? null),
            'q_inasistencia' => $this->entero($data['q_inasistencia'] ?? null),
            'cantidad' => $this->entero($data['cantidad'] ?? null),
            'total' => $this->entero($data['total'] ?? null),

            'observacion' => $data['observacion'] ?? null,
            'activo' => true,
        ];

        return [
            'ajuste' => $this->guardarOActualizarAjuste(
                $asignacion->id,
                $anio,
                $mes,
                'FACTURACION',
                $payload
            ),
        ];
    }







    private function guardarLineaAdicional(array $data, int $anio, int $mes): array
    {
        $proveedorId = (int) $data['suscripcion_proveedor_id'];
        $transportistaId = !empty($data['suscripcion_transportista_id'])
            ? (int) $data['suscripcion_transportista_id']
            : null;

        $tipoAjuste = $this->normalizarTipo($data['tipo_ajuste'] ?? 'LINEA_ADICIONAL');

        $conceptoPagoVariable = $this->resolverConceptoPagoVariable($data);
        $esPagoVariable = $tipoAjuste === 'PAGO_VARIABLE';

        $codigo = $this->texto($data['codigo'] ?? null);
        $servicio = $this->texto($data['servicio'] ?? null);

        if ($esPagoVariable) {
            $nombreConcepto = $conceptoPagoVariable['snapshot'] ?? 'Pago variable';
            $codigoConcepto = $conceptoPagoVariable['codigo'] ?? $this->codigoDesdeTexto($nombreConcepto);

            $codigo = $codigo ?? 'PV-' . $codigoConcepto;
            $servicio = $servicio ?? 'Pago variable - ' . $nombreConcepto;
        } else {
            $codigo = $codigo ?? 'LINEA_ADICIONAL';
            $servicio = $servicio ?? 'Pago adicional';
        }

        $punto1 = $this->texto($data['punto_1'] ?? null);
        $origenGasto = $this->texto($data['origen_gasto'] ?? null) ?? 'Suscripciones';
        $punto2 = $this->texto($data['punto_2'] ?? null);

        $grupoPrefactura = $this->texto($data['grupo_prefactura'] ?? null);

        if ($grupoPrefactura === null && $transportistaId !== null) {
            $grupoPrefactura = Asignaciones::query()
                ->where('suscripcion_proveedor_id', $proveedorId)
                ->where('suscripcion_transportista_id', $transportistaId)
                ->whereNotIn('tipo_asignacion', ['COMISION', 'CONTENEDOR_AJUSTE'])
                ->whereNotNull('grupo_prefactura')
                ->whereRaw("TRIM(grupo_prefactura) <> ''")
                ->orderBy('id')
                ->value('grupo_prefactura');
        }

        $asignacion = Asignaciones::query()
            ->where('suscripcion_proveedor_id', $proveedorId)
            ->where('suscripcion_transportista_id', $transportistaId)
            ->where('generar_automaticamente', 0)
            ->where('tipo_asignacion', 'CONTENEDOR_AJUSTE')
            ->whereRaw('UPPER(TRIM(codigo)) = ?', [mb_strtoupper($codigo)])
            ->whereRaw('COALESCE(TRIM(punto_1), "") = ?', [$punto1 ?? ''])
            ->whereRaw('COALESCE(TRIM(punto_2), "") = ?', [$punto2 ?? ''])
            ->whereRaw('COALESCE(TRIM(origen_gasto), "") = ?', [$origenGasto])
            ->first();

        $estadoAsignacion = 'reutilizada';

        if (!$asignacion) {
            $asignacion = Asignaciones::create([
                'suscripcion_proveedor_id' => $proveedorId,
                'suscripcion_transportista_id' => $transportistaId,
                'punto_1' => $punto1,
                'origen_gasto' => $origenGasto,
                'punto_2' => $punto2,
                'codigo' => $codigo,
                'servicio' => $servicio,
                'costo' => $this->entero($data['costo'] ?? 0),
                'grupo_prefactura' => $grupoPrefactura,
                'generar_automaticamente' => 0,
                'tipo_asignacion' => 'CONTENEDOR_AJUSTE',
            ]);

            $estadoAsignacion = 'creada';
        }

        $proveedorFacturacion = SuscripcionProveedor::findOrFail($proveedorId);

        $payload = [
            'tipo_ajuste' => $tipoAjuste,

            'concepto_pago_variable_id' => $esPagoVariable
                ? $conceptoPagoVariable['id']
                : null,

            'concepto_pago_variable_manual' => $esPagoVariable
                ? $conceptoPagoVariable['manual']
                : null,

            'concepto_pago_variable_snapshot' => $esPagoVariable
                ? $conceptoPagoVariable['snapshot']
                : null,

            'punto_1' => $punto1,
            'origen_gasto' => $origenGasto,
            'punto_2' => $punto2,
            'codigo' => $codigo,
            'servicio' => $servicio,

            'suscripcion_transportista_override_id' => $transportistaId,
            'suscripcion_proveedor_facturacion_id' => $proveedorId,

            'tipo_documento' => $data['tipo_documento'] ?? $proveedorFacturacion->tipo,
            'detalle_documento' => $data['detalle_documento'] ?? $proveedorFacturacion->detalle_documento,
            'detalle_impuesto' => $data['detalle_impuesto'] ?? $proveedorFacturacion->detalle_impuesto,
            'final' => $data['final'] ?? $proveedorFacturacion->final,

            'grupo_prefactura' => $grupoPrefactura,

            'costo' => $this->entero($data['costo'] ?? 0),
            'q_calendario' => $this->entero($data['q_calendario'] ?? 1),
            'q_inasistencia' => $this->entero($data['q_inasistencia'] ?? 0),
            'cantidad' => $this->entero($data['cantidad'] ?? 1),
            'total' => $this->entero($data['total'] ?? null),

            'observacion' => $data['observacion'] ?? null,
            'activo' => true,
        ];

        if ($payload['total'] === null) {
            $payload['total'] = (int) $payload['costo'] * (int) $payload['cantidad'];
        }

        return [
            'asignacion' => $estadoAsignacion,
            'ajuste' => $this->guardarOActualizarAjuste(
                $asignacion->id,
                $anio,
                $mes,
                $tipoAjuste,
                $payload
            ),
        ];
    }













    private function guardarOActualizarAjuste(
        int $suscripcionAsignacionId,
        int $anio,
        int $mes,
        string $tipoAjuste,
        array $payload
    ): string {
        $ajuste = SuscripcionAjusteMensual::query()
            ->where('suscripcion_asignacion_id', $suscripcionAsignacionId)
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->where('tipo_ajuste', $tipoAjuste)
            ->first();

        if (!$ajuste) {
            SuscripcionAjusteMensual::create(array_merge($payload, [
                'suscripcion_asignacion_id' => $suscripcionAsignacionId,
                'anio' => $anio,
                'mes' => $mes,
            ]));

            return 'creado';
        }

        $ajuste->fill($payload);

        if ($ajuste->isDirty()) {
            $ajuste->save();

            return 'actualizado';
        }

        return 'sin_cambios';
    }



    private function esLineaAdicional(string $tipo): bool
    {
        return in_array($tipo, [
            'LINEA_ADICIONAL',
            'PAGO_VARIABLE',
            'PAGO_ADICIONAL', // compatibilidad temporal
            'REEMPLAZO',
        ], true);
    }





    private function normalizarTipo(?string $tipo): string
    {
        $tipo = mb_strtoupper(trim((string) $tipo));
        $tipo = str_replace([' ', '-'], '_', $tipo);

        return $tipo;
    }

    private function texto(mixed $valor): ?string
    {
        $valor = trim((string) ($valor ?? ''));

        return $valor === '' ? null : $valor;
    }

    private function entero(mixed $valor): ?int
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return (int) $valor;
    }


    private function resolverConceptoPagoVariable(array $data): array
    {
        $conceptoId = !empty($data['concepto_pago_variable_id'])
            ? (int) $data['concepto_pago_variable_id']
            : null;

        $conceptoManual = $this->texto($data['concepto_pago_variable_manual'] ?? null);

        $concepto = null;

        if ($conceptoId !== null) {
            $concepto = SuscripcionConceptoPagoVariable::query()
                ->where('activo', true)
                ->find($conceptoId);
        }

        $snapshot = $concepto?->nombre ?? $conceptoManual;

        return [
            'id' => $concepto?->id,
            'manual' => $concepto ? null : $conceptoManual,
            'snapshot' => $snapshot,
            'codigo' => $concepto?->codigo ?? (
                $snapshot ? $this->codigoDesdeTexto($snapshot) : null
            ),
        ];
    }

    private function codigoDesdeTexto(string $texto): string
    {
        $codigo = mb_strtoupper(trim($texto));

        $codigo = str_replace(
            ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ü', 'Ñ'],
            ['A', 'E', 'I', 'O', 'U', 'U', 'N'],
            $codigo
        );

        $codigo = preg_replace('/[^A-Z0-9]+/', '_', $codigo);
        $codigo = trim((string) $codigo, '_');

        return $codigo !== '' ? $codigo : 'PAGO_VARIABLE';
    }









}