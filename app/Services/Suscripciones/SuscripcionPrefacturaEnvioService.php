<?php

namespace App\Services\Suscripciones;

use App\Mail\SuscripcionPrefacturaPruebaMail;
use App\Models\SuscripcionPrefacturaEnvio;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SuscripcionPrefacturaEnvioService
{
    /*
    * Pre-facturas enviadas por petición.
    *
    * Producción corta cada petición a los 120 segundos y cada
    * correo tarda unos 2 segundos. Con 20 por tanda queda margen.
    */
    public const PREFACTURAS_POR_TANDA = 20;

    /*
    * Un registro "enviando" más reciente que esto pertenece
    * a una tanda que todavía está en curso.
    */
    private const MINUTOS_ENVIO_EN_CURSO = 3;

    public function __construct(
        private SuscripcionPrefacturaPdfService $pdfService,
        private SuscripcionPrefacturaAgrupacionService $agrupacionService,
        private SuscripcionAjusteMensualService $ajusteMensualService
    ) {
    }

    /**
     * Envía una copia de prueba de cada pre-factura al correo interno indicado.
     *
     * Durante esta etapa nunca utiliza como destinatario el correo real
     * almacenado en suscripcion_proveedores.correo.
     */


    public function prepararRevisionDesdeDetalles(
        Collection $detallesBase
    ): array {
        $this->ajusteMensualService->precargarParaDetalles($detallesBase);

        $detallesConProveedor = $detallesBase
            ->filter(function ($detalle) {
                return $this->ajusteMensualService
                    ->proveedorFacturacionParaDetalle($detalle)?->id;
            })
            ->values();

        /*
        * Una pre-factura corresponde a:
        * proveedor efectivo + año + mes + grupo.
        */
        $prefacturas = $detallesConProveedor
            ->groupBy(function ($detalle) {
                $proveedorEfectivo = $this->ajusteMensualService
                    ->proveedorFacturacionParaDetalle($detalle);

                $grupo = $this->agrupacionService->claveGrupo(
                    $this->agrupacionService->grupoDesdeDetalle($detalle)
                );

                return implode('_', [
                    $proveedorEfectivo?->id ?? 'sin_proveedor',
                    $detalle->anio,
                    $detalle->mes,
                    $grupo,
                ]);
            })
            ->map(function ($detallesPrefactura) {
                $detallesPrefactura = $detallesPrefactura
                    ->sortBy('codigo')
                    ->values();

                $detalle = $detallesPrefactura->first();

                $proveedor = $this->ajusteMensualService
                    ->proveedorFacturacionParaDetalle($detalle);

                $cobranzaCompra = $proveedor?->cobranzaCompra;

                $grupo = $this->agrupacionService
                    ->grupoDesdeDetalle($detalle);

                return [
                    'proveedor_id' => $proveedor?->id,

                    'proveedor' => $cobranzaCompra?->razon_social
                        ?? 'Proveedor desconocido',

                    'rut' => $cobranzaCompra?->rut_cliente ?? '—',

                    'correo' => trim(
                        (string) ($proveedor?->correo ?? '')
                    ),

                    'grupo' => $this->agrupacionService
                        ->etiquetaGrupo($grupo),

                    'detalle_id' => $detalle?->id,

                    'anio' => (int) $detalle->anio,
                    'mes' => (int) $detalle->mes,
                ];
            })
            ->values();

        /*
        * Consolidamos por proveedor para mostrar cuántos PDF
        * recibirá cada destinatario.
        */
        $proveedores = $prefacturas
            ->groupBy('proveedor_id')
            ->map(function ($items) {
                $primero = $items->first();
                $correo = trim((string) ($primero['correo'] ?? ''));

                if ($correo === '') {
                    $estado = 'sin_correo';
                    $estadoLabel = 'Sin correo';
                } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                    $estado = 'correo_invalido';
                    $estadoLabel = 'Correo inválido';
                } else {
                    $estado = 'listo';
                    $estadoLabel = 'Listo para enviar';
                }

                return [
                    'proveedor_id' => $primero['proveedor_id'],
                    'proveedor' => $primero['proveedor'],
                    'rut' => $primero['rut'],
                    'correo' => $correo,
                    'cantidad_pdfs' => $items->count(),

                    'grupos' => $items
                        ->pluck('grupo')
                        ->filter()
                        ->unique()
                        ->values(),

                    'estado' => $estado,
                    'estado_label' => $estadoLabel,
                ];
            })
            ->sortBy(function ($item) {
                return mb_strtoupper(
                    trim((string) $item['proveedor'])
                );
            })
            ->values();

        return [
            'proveedores' => $proveedores,

            'total_proveedores' => $proveedores->count(),
            'total_prefacturas' => $prefacturas->count(),

            'listos' => $proveedores
                ->where('estado', 'listo')
                ->count(),

            'sin_correo' => $proveedores
                ->where('estado', 'sin_correo')
                ->count(),

            'correos_invalidos' => $proveedores
                ->where('estado', 'correo_invalido')
                ->count(),
        ];
    }




    public function enviarPruebasDesdeDetalles(
        Collection $detallesBase,
        string $destinoPrueba
    ): array {
        @set_time_limit(0);
        ini_set('memory_limit', '1024M');

        if (!filter_var($destinoPrueba, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException(
                'El correo configurado para las pruebas no es válido.'
            );
        }

        $this->ajusteMensualService->precargarParaDetalles($detallesBase);

        /*
         * Se consideran únicamente detalles que tengan un proveedor
         * efectivo válido para el período.
         */
        $detallesConProveedor = $detallesBase
            ->filter(function ($detalle) {
                return $this->ajusteMensualService
                    ->proveedorFacturacionParaDetalle($detalle)?->id;
            })
            ->values();

        /*
         * La agrupación debe ser idéntica a la utilizada por el ZIP:
         *
         * proveedor efectivo
         * + año
         * + mes
         * + grupo de pre-factura
         */
        $detallesPorPrefactura = $detallesConProveedor
            ->groupBy(function ($detalle) {
                $proveedorEfectivo = $this->ajusteMensualService
                    ->proveedorFacturacionParaDetalle($detalle);

                $grupoPrefactura = $this->agrupacionService
                    ->claveGrupo(
                        $this->agrupacionService
                            ->grupoDesdeDetalle($detalle)
                    );

                return implode('_', [
                    $proveedorEfectivo?->id ?? 'sin_proveedor',
                    $detalle->anio,
                    $detalle->mes,
                    $grupoPrefactura,
                ]);
            });

        if ($detallesPorPrefactura->isEmpty()) {
            throw new \RuntimeException(
                'No existen pre-facturas para realizar el envío de prueba.'
            );
        }

        $resumen = [
            'total' => $detallesPorPrefactura->count(),
            'enviados' => 0,
            'fallidos' => 0,
            'destino_prueba' => $destinoPrueba,
            'resultados' => [],
        ];

        foreach ($detallesPorPrefactura as $detallesPrefactura) {
            $detallesPrefactura = $detallesPrefactura
                ->sortBy('codigo')
                ->values();

            if ($detallesPrefactura->isEmpty()) {
                continue;
            }

            /*
             * El primer detalle representa a la pre-factura completa.
             * El PdfService volverá a reunir todas las líneas del mismo
             * proveedor efectivo y grupo.
             */
            $detalleRepresentativo = $detallesPrefactura->first();

            try {
                $prefactura = $this->pdfService
                    ->generarDesdeDetalle($detalleRepresentativo);

                $cobranzaCompra = $prefactura['cobranza_compra'];

                Mail::to($destinoPrueba)->send(
                    new SuscripcionPrefacturaPruebaMail(
                        contenidoPdf: $prefactura['pdf']->output(),

                        nombreArchivo:
                            $prefactura['nombre_archivo'],

                        nombreProveedor: (string) (
                            $cobranzaCompra?->razon_social
                            ?? 'Proveedor'
                        ),

                        rutProveedor: (string) (
                            $cobranzaCompra?->rut_cliente
                            ?? '—'
                        ),

                        mesNombre:
                            $prefactura['mes_nombre'],

                        anio:
                            (int) $prefactura['anio'],

                        oc:
                            (string) $prefactura['oc'],

                        totalLiquido:
                            (float) $prefactura['total_liquido'],

                        correoProveedorReal:
                            $prefactura['correo_proveedor'] !== ''
                                ? $prefactura['correo_proveedor']
                                : null,

                        grupoPrefacturaLabel:
                            $prefactura['grupo_prefactura_label']
                    )
                );

                $resumen['enviados']++;

                $resumen['resultados'][] = [
                    'estado' => 'enviado',
                    'proveedor' => (
                        $cobranzaCompra?->razon_social
                        ?? 'Proveedor'
                    ),
                    'correo_real' => (
                        $prefactura['correo_proveedor']
                        ?: null
                    ),
                    'destino_utilizado' => $destinoPrueba,
                    'archivo' => $prefactura['nombre_archivo'],
                    'oc' => $prefactura['oc'],
                ];
            } catch (\Throwable $e) {
                $resumen['fallidos']++;

                $proveedorEfectivo = $this->ajusteMensualService
                    ->proveedorFacturacionParaDetalle(
                        $detalleRepresentativo
                    );

                $nombreProveedor = $proveedorEfectivo
                    ?->cobranzaCompra
                    ?->razon_social
                    ?? 'Proveedor desconocido';

                $resumen['resultados'][] = [
                    'estado' => 'fallido',
                    'proveedor' => $nombreProveedor,
                    'error' => $e->getMessage(),
                ];

                Log::error(
                    '[SUSCRIPCIONES] Falló envío de pre-factura de prueba',
                    [
                        'detalle_id' => $detalleRepresentativo->id,
                        'proveedor' => $nombreProveedor,
                        'destino_prueba' => $destinoPrueba,
                        'error' => $e->getMessage(),
                    ]
                );
            }

            /*
             * Libera memoria después de procesar cada PDF.
             */
            unset($prefactura);
            gc_collect_cycles();
        }

        return $resumen;
    }




    /**
     * Envía una tanda de pre-facturas reales.
     *
     * Cada pre-factura queda anotada en suscripcion_prefactura_envios.
     * Una corrida (identificada por $inicioCorrida) recorre todas las
     * pre-facturas pendientes en tandas de PREFACTURAS_POR_TANDA:
     *
     * - nunca reenvía una pre-factura ya enviada;
     * - no reintenta en la misma corrida una que ya falló u omitió;
     * - una corrida nueva vuelve a intentar sólo las no enviadas.
     */
    public function enviarRealesDesdeDetalles(
        Collection $detallesBase,
        int $anio,
        int $mes,
        CarbonInterface $inicioCorrida
    ): array {
        @set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $copias = [
            'finanzas@4nlogistica.cl',
            'luisdelabarra@4nlogistica.cl',
            'proveedores@4nlogistica.cl',
        ];

        $prefacturas = $this->prefacturasDesdeDetalles($detallesBase);

        if ($prefacturas->isEmpty()) {
            throw new \RuntimeException(
                'No existen pre-facturas para realizar el envío.'
            );
        }

        $procesadas = 0;

        foreach ($prefacturas as $item) {
            if ($procesadas >= self::PREFACTURAS_POR_TANDA) {
                break;
            }

            $registro = $this->reservarPrefactura(
                $anio,
                $mes,
                (int) $item['proveedor']->id,
                $item['grupo_clave'],
                $inicioCorrida
            );

            /*
            * Ya enviada, ya intentada en esta corrida
            * o en curso en otra tanda.
            */
            if (!$registro) {
                continue;
            }

            $procesadas++;

            $detalleRepresentativo = $item['detalle'];
            $nombreProveedor = (string) (
                $item['proveedor']->cobranzaCompra?->razon_social
                ?? 'Proveedor desconocido'
            );

            $prefactura = null;

            try {
                $prefactura = $this->pdfService
                    ->generarDesdeDetalle($detalleRepresentativo);

                $cobranzaCompra = $prefactura['cobranza_compra'];

                $correoDestino = trim(
                    (string) ($prefactura['correo_proveedor'] ?? '')
                );

                /*
                * Una pre-factura sin correo válido no debe detener
                * el envío del resto de los proveedores.
                */
                if (
                    $correoDestino === ''
                    || !filter_var($correoDestino, FILTER_VALIDATE_EMAIL)
                ) {
                    $registro->update([
                        'estado' => SuscripcionPrefacturaEnvio::ESTADO_OMITIDO,
                        'correo' => $correoDestino ?: null,
                        'archivo' => $prefactura['nombre_archivo'],
                        'oc' => (string) $prefactura['oc'],
                        'mensaje' => $correoDestino === ''
                            ? 'Proveedor sin correo registrado.'
                            : 'Correo inválido.',
                    ]);

                    Log::warning(
                        '[SUSCRIPCIONES] Pre-factura omitida por correo inválido',
                        [
                            'detalle_id' => $detalleRepresentativo->id,
                            'proveedor' => $nombreProveedor,
                            'correo' => $correoDestino,
                        ]
                    );

                    unset($prefactura);
                    gc_collect_cycles();

                    continue;
                }

                $copiasEnvio = collect($copias)
                    ->reject(function ($correoCopia) use ($correoDestino) {
                        return strcasecmp($correoCopia, $correoDestino) === 0;
                    })
                    ->values()
                    ->all();

                /*
                * Envío real:
                * - Para: correo del proveedor efectivo.
                * - CC: Finanzas, Luis de la Barra y proveedores@.
                */
                Mail::to($correoDestino)
                    ->cc($copiasEnvio)
                    ->send(
                        new SuscripcionPrefacturaPruebaMail(
                            contenidoPdf: $prefactura['pdf']->output(),

                            nombreArchivo:
                                $prefactura['nombre_archivo'],

                            nombreProveedor:
                                $nombreProveedor,

                            rutProveedor: (string) (
                                $cobranzaCompra?->rut_cliente
                                ?? '—'
                            ),

                            mesNombre:
                                $prefactura['mes_nombre'],

                            anio:
                                (int) $prefactura['anio'],

                            oc:
                                (string) $prefactura['oc'],

                            totalLiquido:
                                (float) $prefactura['total_liquido'],

                            correoProveedorReal:
                                $correoDestino,

                            grupoPrefacturaLabel:
                                $prefactura['grupo_prefactura_label']
                        )
                    );

                /*
                * Se anota inmediatamente después de enviar para que
                * un corte posterior no vuelva a mandar este correo.
                */
                $registro->update([
                    'estado' => SuscripcionPrefacturaEnvio::ESTADO_ENVIADO,
                    'correo' => $correoDestino,
                    'archivo' => $prefactura['nombre_archivo'],
                    'oc' => (string) $prefactura['oc'],
                    'mensaje' => null,
                    'enviado_at' => now(),
                ]);

                Log::info(
                    '[SUSCRIPCIONES] Pre-factura enviada al proveedor',
                    [
                        'detalle_id' => $detalleRepresentativo->id,
                        'proveedor' => $nombreProveedor,
                        'correo' => $correoDestino,
                        'copias' => $copiasEnvio,
                        'archivo' => $prefactura['nombre_archivo'],
                        'oc' => $prefactura['oc'],
                    ]
                );
            } catch (\Throwable $e) {
                $registro->update([
                    'estado' => SuscripcionPrefacturaEnvio::ESTADO_FALLIDO,
                    'mensaje' => mb_substr($e->getMessage(), 0, 1000),
                ]);

                Log::error(
                    '[SUSCRIPCIONES] Falló envío real de pre-factura',
                    [
                        'detalle_id' => $detalleRepresentativo->id,
                        'proveedor' => $nombreProveedor,
                        'error' => $e->getMessage(),
                    ]
                );
            }

            unset($prefactura);
            gc_collect_cycles();
        }

        return [
            'procesadas' => $procesadas,

            ...$this->estadoEnvio(
                $prefacturas,
                $anio,
                $mes,
                $inicioCorrida
            ),
        ];
    }

    /**
     * Estado del envío real de un conjunto de pre-facturas:
     * cuántas salieron y cuáles no, con su motivo.
     */
    public function estadoEnvio(
        Collection $prefacturas,
        int $anio,
        int $mes,
        CarbonInterface $inicioCorrida
    ): array {
        $registros = SuscripcionPrefacturaEnvio::query()
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->get()
            ->keyBy(fn (SuscripcionPrefacturaEnvio $registro) =>
                $registro->suscripcion_proveedor_id
                . '|'
                . $registro->grupo_prefactura
            );

        $enviadas = 0;
        $pendientesCorrida = 0;
        $noEnviadas = collect();

        foreach ($prefacturas as $item) {
            $proveedor = $item['proveedor'];

            $registro = $registros->get(
                $proveedor->id . '|' . $item['grupo_clave']
            );

            if ($registro?->estado === SuscripcionPrefacturaEnvio::ESTADO_ENVIADO) {
                $enviadas++;

                continue;
            }

            if (!$registro || $this->estaPendiente($registro, $inicioCorrida)) {
                $pendientesCorrida++;
            }

            $estado = $registro?->estado;

            $motivo = match ($estado) {
                SuscripcionPrefacturaEnvio::ESTADO_OMITIDO,
                SuscripcionPrefacturaEnvio::ESTADO_FALLIDO =>
                    $registro->mensaje,

                SuscripcionPrefacturaEnvio::ESTADO_ENVIANDO =>
                    'El envío se cortó con este correo en curso. Revisar en proveedores@4nlogistica.cl si llegó.',

                default =>
                    'Todavía no se envía.',
            };

            $noEnviadas->push([
                'proveedor' => $proveedor->cobranzaCompra?->razon_social
                    ?? 'Proveedor desconocido',

                'rut' => $proveedor->cobranzaCompra?->rut_cliente ?? '—',

                'correo' => trim((string) ($proveedor->correo ?? '')),

                'grupo' => $item['grupo_label'],

                'estado' => $estado ?? 'pendiente',

                'motivo' => $motivo,
            ]);
        }

        return [
            'total' => $prefacturas->count(),
            'enviadas' => $enviadas,
            'no_enviadas' => $noEnviadas->values(),
            'pendientes_corrida' => $pendientesCorrida,
        ];
    }

    /**
     * Una pre-factura por proveedor efectivo + grupo,
     * ordenadas por razón social para que las tandas
     * avancen siempre en el mismo orden.
     */
    public function prefacturasDesdeDetalles(
        Collection $detallesBase
    ): Collection {
        $this->ajusteMensualService->precargarParaDetalles($detallesBase);

        return $detallesBase
            ->filter(function ($detalle) {
                return $this->ajusteMensualService
                    ->proveedorFacturacionParaDetalle($detalle)?->id;
            })
            ->groupBy(function ($detalle) {
                $proveedorEfectivo = $this->ajusteMensualService
                    ->proveedorFacturacionParaDetalle($detalle);

                return implode('_', [
                    $proveedorEfectivo->id,
                    $detalle->anio,
                    $detalle->mes,
                    $this->agrupacionService->claveGrupo(
                        $this->agrupacionService->grupoDesdeDetalle($detalle)
                    ),
                ]);
            })
            ->map(function ($detallesPrefactura) {
                $detalle = $detallesPrefactura
                    ->sortBy('codigo')
                    ->first();

                $grupo = $this->agrupacionService
                    ->grupoDesdeDetalle($detalle);

                return [
                    'detalle' => $detalle,

                    'proveedor' => $this->ajusteMensualService
                        ->proveedorFacturacionParaDetalle($detalle),

                    'grupo_clave' => $this->agrupacionService
                        ->claveGrupo($grupo),

                    'grupo_label' => $this->agrupacionService
                        ->etiquetaGrupo($grupo),
                ];
            })
            ->sortBy(function (array $item) {
                return mb_strtoupper(trim((string) (
                    $item['proveedor']->cobranzaCompra?->razon_social ?? ''
                )))
                    . '|'
                    . $item['grupo_clave'];
            })
            ->values();
    }

    /**
     * Marca la pre-factura como "enviando" si le toca en esta corrida.
     *
     * Devuelve null cuando ya fue enviada, ya se intentó en esta
     * corrida o la está enviando otra tanda en este momento.
     */
    private function reservarPrefactura(
        int $anio,
        int $mes,
        int $proveedorId,
        string $grupoClave,
        CarbonInterface $inicioCorrida
    ): ?SuscripcionPrefacturaEnvio {
        try {
            return DB::transaction(function () use (
                $anio,
                $mes,
                $proveedorId,
                $grupoClave,
                $inicioCorrida
            ) {
                $registro = SuscripcionPrefacturaEnvio::query()
                    ->where('anio', $anio)
                    ->where('mes', $mes)
                    ->where('suscripcion_proveedor_id', $proveedorId)
                    ->where('grupo_prefactura', $grupoClave)
                    ->lockForUpdate()
                    ->first();

                if ($registro && !$this->estaPendiente($registro, $inicioCorrida)) {
                    return null;
                }

                $reserva = [
                    'estado' => SuscripcionPrefacturaEnvio::ESTADO_ENVIANDO,
                    'mensaje' => null,
                    'ultimo_intento_at' => now(),
                ];

                if (!$registro) {
                    return SuscripcionPrefacturaEnvio::create([
                        'anio' => $anio,
                        'mes' => $mes,
                        'suscripcion_proveedor_id' => $proveedorId,
                        'grupo_prefactura' => $grupoClave,
                        ...$reserva,
                    ]);
                }

                $registro->update($reserva);

                return $registro;
            });
        } catch (UniqueConstraintViolationException) {
            /*
            * Otra tanda creó el registro al mismo tiempo.
            */
            return null;
        }
    }

    private function estaPendiente(
        SuscripcionPrefacturaEnvio $registro,
        CarbonInterface $inicioCorrida
    ): bool {
        if ($registro->estado === SuscripcionPrefacturaEnvio::ESTADO_ENVIADO) {
            return false;
        }

        /*
        * Ya intentada en esta corrida.
        */
        if ($registro->ultimo_intento_at?->gte($inicioCorrida)) {
            return false;
        }

        /*
        * Otra tanda la está enviando ahora mismo.
        */
        if (
            $registro->estado === SuscripcionPrefacturaEnvio::ESTADO_ENVIANDO
            && $registro->ultimo_intento_at?->gt(
                now()->subMinutes(self::MINUTOS_ENVIO_EN_CURSO)
            )
        ) {
            return false;
        }

        return true;
    }
}
