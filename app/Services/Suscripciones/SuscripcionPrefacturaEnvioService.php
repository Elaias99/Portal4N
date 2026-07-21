<?php

namespace App\Services\Suscripciones;

use App\Mail\SuscripcionPrefacturaPruebaMail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SuscripcionPrefacturaEnvioService
{
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




    public function enviarRealesDesdeDetalles(
        Collection $detallesBase
    ): array {
        @set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $copias = [
            'finanzas@4nlogistica.cl',
            'luisdelabarra@4nlogistica.cl',
            'proveedores@4nlogistica.cl',
        ];

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
                'No existen pre-facturas para realizar el envío.'
            );
        }

        $resumen = [
            'total' => $detallesPorPrefactura->count(),
            'enviados' => 0,
            'omitidos' => 0,
            'fallidos' => 0,
            'copias' => $copias,
            'resultados' => [],
        ];

        foreach ($detallesPorPrefactura as $detallesPrefactura) {
            $detallesPrefactura = $detallesPrefactura
                ->sortBy('codigo')
                ->values();

            if ($detallesPrefactura->isEmpty()) {
                continue;
            }

            $detalleRepresentativo = $detallesPrefactura->first();
            $prefactura = null;

            try {
                $prefactura = $this->pdfService
                    ->generarDesdeDetalle($detalleRepresentativo);

                $cobranzaCompra = $prefactura['cobranza_compra'];

                $nombreProveedor = (string) (
                    $cobranzaCompra?->razon_social
                    ?? 'Proveedor'
                );

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
                    $resumen['omitidos']++;

                    $resumen['resultados'][] = [
                        'estado' => 'omitido',
                        'proveedor' => $nombreProveedor,
                        'correo' => $correoDestino ?: null,
                        'motivo' => $correoDestino === ''
                            ? 'Proveedor sin correo registrado.'
                            : 'Correo inválido.',
                        'archivo' => $prefactura['nombre_archivo'],
                    ];

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
                * - CC: Finanzas y Luis de la Barra.
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

                $resumen['enviados']++;

                $resumen['resultados'][] = [
                    'estado' => 'enviado',
                    'proveedor' => $nombreProveedor,
                    'correo' => $correoDestino,
                    'copias' => $copiasEnvio,
                    'archivo' => $prefactura['nombre_archivo'],
                    'oc' => $prefactura['oc'],
                ];

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

        return $resumen;
    }









}