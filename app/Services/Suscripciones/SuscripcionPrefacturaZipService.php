<?php

namespace App\Services\Suscripciones;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use ZipArchive;

class SuscripcionPrefacturaZipService
{
    public function __construct(
        private SuscripcionLiquidacionResumenService $resumenService,
        private SuscripcionPrefacturaAgrupacionService $agrupacionService,
        private SuscripcionPrefacturaOcService $ocService,
        private SuscripcionAjusteMensualService $ajusteMensualService
    ) {}

    public function generarDesdeDetalles(Collection $detallesBase, int $anio, int $mes): array
    {
        @set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $prefacturas = $this->planificar($detallesBase, $anio, $mes);

        /*
         * La clave del ZIP sale de las claves de sus PDFs, que incluyen la OC:
         * si cambia la OC de cualquier pre-factura, el ZIP se vuelve a armar.
         */
        $zipCacheKey = sha1(
            'zip_v5|' . $anio . '|' . $mes . '|'
            . $prefacturas->pluck('cache_key')->implode('|')
        );

        $zipFileName = 'prefacturas_suscripciones_'
            . $anio
            . '_'
            . str_pad($mes, 2, '0', STR_PAD_LEFT)
            . '_'
            . $zipCacheKey
            . '.zip';

        $zipPath = $this->zipDir() . DIRECTORY_SEPARATOR . $zipFileName;

        if (file_exists($zipPath) && filesize($zipPath) > 0) {
            return [
                'zip_path' => $zipPath,
                'zip_file_name' => $zipFileName,
                'generados' => $prefacturas->count(),
                'desde_cache' => true,
            ];
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('No se pudo crear el archivo ZIP.');
        }

        $generados = 0;

        foreach ($prefacturas as $prefactura) {
            $this->asegurarPdf($prefactura);

            if (!$zip->addFile($prefactura['pdf_path'], $prefactura['nombre_pdf'])) {
                $zip->close();

                if (file_exists($zipPath)) {
                    unlink($zipPath);
                }

                throw new \RuntimeException('No se pudo agregar una pre-factura al ZIP.');
            }

            $generados++;
        }

        $zip->close();

        if ($generados === 0) {
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }

            throw new \RuntimeException('No se generó ningún PDF.');
        }

        return [
            'zip_path' => $zipPath,
            'zip_file_name' => $zipFileName,
            'generados' => $generados,
            'desde_cache' => false,
        ];
    }

    /**
     * Ruta de un ZIP ya armado, o null si no existe.
     * Sólo acepta nombres generados por este servicio.
     */
    public function rutaZipGuardado(string $zipFileName): ?string
    {
        if (!preg_match('/^prefacturas_suscripciones_\d{4}_\d{2}_[a-f0-9]{40}\.zip$/', $zipFileName)) {
            return null;
        }

        $zipPath = $this->zipDir() . DIRECTORY_SEPARATOR . $zipFileName;

        return is_file($zipPath) ? $zipPath : null;
    }

    /**
     * Genera a lo más $maximo PDFs que todavía no estén guardados.
     *
     * Permite armar el ZIP por tandas: producción corta cada petición
     * a los 120 s y el mes completo tarda más que eso.
     *
     * @return array{total: int, listos: int, generados_ahora: int}
     */
    public function prepararPdfs(Collection $detallesBase, int $anio, int $mes, int $maximo): array
    {
        @set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $prefacturas = $this->planificar($detallesBase, $anio, $mes);

        $generadosAhora = 0;

        foreach ($prefacturas as $prefactura) {
            if ($this->pdfListo($prefactura['pdf_path'])) {
                continue;
            }

            if ($generadosAhora >= $maximo) {
                break;
            }

            $this->asegurarPdf($prefactura);
            $generadosAhora++;
        }

        return [
            'total' => $prefacturas->count(),
            'listos' => $prefacturas
                ->filter(fn (array $prefactura) => $this->pdfListo($prefactura['pdf_path']))
                ->count(),
            'generados_ahora' => $generadosAhora,
        ];
    }

    /**
     * Arma la lista de pre-facturas (una por proveedor efectivo y grupo)
     * con su OC, nombre de archivo y ruta del PDF guardado.
     */
    private function planificar(Collection $detallesBase, int $anio, int $mes): Collection
    {
        $this->ajusteMensualService->precargarParaDetalles($detallesBase);

        $pdfDir = $this->pdfDir();

        $detallesConProveedor = $detallesBase
            ->filter(function ($detalle) {
                return $this->ajusteMensualService->proveedorFacturacionParaDetalle($detalle)?->id;
            })
            ->values();

        $detallesPorPrefactura = $detallesConProveedor
            ->groupBy(function ($detalle) {
                $proveedorEfectivo = $this->ajusteMensualService->proveedorFacturacionParaDetalle($detalle);
                $grupoPrefactura = $this->agrupacionService->claveGrupo(
                    $this->agrupacionService->grupoDesdeDetalle($detalle)
                );

                return implode('_', [
                    $proveedorEfectivo?->id ?? 'sin_proveedor',
                    $detalle->anio,
                    $detalle->mes,
                    $grupoPrefactura,
                ]);
            });

        if ($detallesPorPrefactura->isEmpty()) {
            throw new \RuntimeException('No se generó ningún PDF.');
        }

        $plan = collect();
        $nombresPdfUsados = [];

        foreach ($detallesPorPrefactura as $detallesPrefactura) {
            $detallesPrefactura = $detallesPrefactura
                ->sortBy('codigo')
                ->values();

            if ($detallesPrefactura->isEmpty()) {
                continue;
            }

            $detalle = $detallesPrefactura->first();

            $proveedor = $this->ajusteMensualService->proveedorFacturacionParaDetalle($detalle);
            $cobranzaCompra = $proveedor?->cobranzaCompra;

            $ocPrefactura = $proveedor
                ? $this->ocService->generarOC(
                    (int) $anio,
                    (int) $mes,
                    (int) $proveedor->id
                )
                : '—';

            $grupoPrefactura = $this->agrupacionService->grupoDesdeDetalle($detalle);
            $grupoPrefacturaLabel = $this->agrupacionService->etiquetaGrupo($grupoPrefactura);

            $nombrePdf = $this->nombreArchivoPdf(
                $proveedor,
                $cobranzaCompra,
                $anio,
                $mes,
                $grupoPrefacturaLabel
            );

            $nombrePdf = $this->resolverNombreUnicoPdf($nombrePdf, $nombresPdfUsados);

            /*
             * La OC entra en la clave del PDF guardado: depende del orden
             * de todos los proveedores del mes y puede cambiar aunque las
             * líneas de este proveedor sigan iguales.
             */
            $pdfCacheKey = sha1(
                $this->generarCacheKey($detallesPrefactura, $anio, $mes)
                . '|oc:' . $ocPrefactura
            );

            $plan->push([
                'detalles' => $detallesPrefactura,
                'detalle' => $detalle,
                'proveedor' => $proveedor,
                'cobranza_compra' => $cobranzaCompra,
                'oc' => $ocPrefactura,
                'grupo' => $grupoPrefactura,
                'grupo_label' => $grupoPrefacturaLabel,
                'nombre_pdf' => $nombrePdf,
                'cache_key' => $pdfCacheKey,
                'pdf_path' => $pdfDir . DIRECTORY_SEPARATOR . $pdfCacheKey . '.pdf',
            ]);
        }

        return $plan;
    }

    /**
     * Genera el PDF de una pre-factura si todavía no está guardado.
     */
    private function asegurarPdf(array $prefactura): void
    {
        if ($this->pdfListo($prefactura['pdf_path'])) {
            return;
        }

        $detallesPrefactura = $prefactura['detalles'];

        $calculosDetalle = $this->resumenService->calcularPorDetalles($detallesPrefactura);

        $pdf = Pdf::loadView('suscripciones.liquidacion_detalles.pdf', [
            'detalle' => $prefactura['detalle'],
            'detallesProveedor' => $detallesPrefactura,
            'calculosDetalle' => $calculosDetalle,
            'proveedor' => $prefactura['proveedor'],
            'cobranzaCompra' => $prefactura['cobranza_compra'],
            'totalBruto' => $detallesPrefactura->sum('total'),
            'totalImpuesto' => $calculosDetalle->sum('total_impuesto'),
            'totalLiquido' => $calculosDetalle->sum('liquido'),
            'meses' => $this->meses(),
            'grupoPrefactura' => $prefactura['grupo'],
            'grupoPrefacturaLabel' => $prefactura['grupo_label'],
            'ocPrefactura' => $prefactura['oc'],
        ])->setPaper('letter', 'portrait');

        /*
         * Se escribe en un temporal y luego se renombra: si la petición
         * se corta a mitad, no queda un PDF incompleto que parezca listo.
         */
        $temporal = $prefactura['pdf_path'] . '.tmp';

        file_put_contents($temporal, $pdf->output());
        rename($temporal, $prefactura['pdf_path']);

        unset($pdf, $calculosDetalle);
        gc_collect_cycles();
    }

    private function pdfListo(string $pdfPath): bool
    {
        return file_exists($pdfPath) && filesize($pdfPath) > 0;
    }

    private function pdfDir(): string
    {
        return $this->directorio('pdfs');
    }

    private function zipDir(): string
    {
        return $this->directorio('zips');
    }

    private function directorio(string $nombre): string
    {
        $dir = storage_path('app/temp_prefacturas') . DIRECTORY_SEPARATOR . $nombre;

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }

    private function nombreArchivoPdf($proveedor, $cobranzaCompra, int $anio, int $mes, ?string $grupoPrefacturaLabel = null): string
    {
        $nombreProveedor = $cobranzaCompra?->razon_social ?? 'PROVEEDOR';
        $tipo = $proveedor?->tipo ?? 'DOC';

        $nombreLimpio = $this->limpiarNombreArchivo($nombreProveedor);

        $grupoLimpio = '';
        $grupoNormalizado = mb_strtoupper(trim((string) $grupoPrefacturaLabel));

        if (
            $grupoNormalizado !== ''
            && $grupoNormalizado !== SuscripcionPrefacturaAgrupacionService::GRUPO_GENERAL
        ) {
            $grupoLimpio = '_' . $this->limpiarNombreArchivo($grupoPrefacturaLabel);
        }

        return 'Prefactura_Susc_'
            . $tipo
            . '_'
            . $nombreLimpio
            . $grupoLimpio
            . '_'
            . $anio
            . '_'
            . str_pad($mes, 2, '0', STR_PAD_LEFT)
            . '.pdf';
    }

    private function resolverNombreUnicoPdf(string $nombrePdf, array &$nombresPdfUsados): string
    {
        if (!in_array($nombrePdf, $nombresPdfUsados, true)) {
            $nombresPdfUsados[] = $nombrePdf;

            return $nombrePdf;
        }

        $info = pathinfo($nombrePdf);
        $base = $info['filename'] ?? 'prefactura';
        $extension = isset($info['extension']) ? '.' . $info['extension'] : '';

        $contador = 2;

        do {
            $nombreUnico = $base . '_' . $contador . $extension;
            $contador++;
        } while (in_array($nombreUnico, $nombresPdfUsados, true));

        $nombresPdfUsados[] = $nombreUnico;

        return $nombreUnico;
    }

    private function limpiarNombreArchivo(?string $valor): string
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return 'SIN_NOMBRE';
        }

        $valor = preg_replace('/[^A-Za-z0-9_\-]/', '_', $valor);
        $valor = preg_replace('/_+/', '_', $valor);
        $valor = trim($valor, '_');

        return $valor !== '' ? $valor : 'SIN_NOMBRE';
    }

    private function meses(): array
    {
        return [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];
    }

    private function generarCacheKey(Collection $detalles, int $anio, int $mes): string
    {
        $base = $detalles
            ->sortBy('id')
            ->map(function ($detalle) {
                $asignacion = $detalle->asignacion;
                $proveedor = $asignacion?->suscripcionProveedor;
                $cobranzaCompra = $proveedor?->cobranzaCompra;

                $grupoPrefactura = $asignacion?->grupo_prefactura;
                $grupoPrefacturaClave = $this->agrupacionService->claveGrupo($grupoPrefactura);

                $ajuste = $this->ajusteMensualService->resolverParaDetalle($detalle);
                $proveedorEfectivo = $this->ajusteMensualService->proveedorFacturacionParaDetalle($detalle);
                $cobranzaEfectiva = $proveedorEfectivo?->cobranzaCompra;

                return implode('|', [

                    $ajuste?->id,
                    $ajuste?->tipo_ajuste,
                    $ajuste?->suscripcion_proveedor_facturacion_id,
                    $ajuste?->tipo_documento,
                    $ajuste?->detalle_documento,
                    $ajuste?->detalle_impuesto,
                    $ajuste?->final,
                    optional($ajuste?->updated_at)->timestamp,

                    $proveedorEfectivo?->id,
                    $proveedorEfectivo?->tipo,
                    $proveedorEfectivo?->detalle_documento,
                    $proveedorEfectivo?->detalle_impuesto,
                    $proveedorEfectivo?->final,

                    $cobranzaEfectiva?->id,
                    $cobranzaEfectiva?->rut_cliente,
                    $cobranzaEfectiva?->razon_social,


                    $detalle->id,
                    $detalle->suscripcion_asignacion_id,
                    $detalle->anio,
                    $detalle->mes,
                    $detalle->codigo,
                    $detalle->costo,
                    $detalle->q_calendario,
                    $detalle->q_inasistencia,
                    $detalle->cantidad,
                    $detalle->total,

                    $asignacion?->id,
                    $asignacion?->codigo,
                    $asignacion?->servicio,
                    $asignacion?->costo,
                    $asignacion?->grupo_prefactura,
                    $grupoPrefacturaClave,

                    $proveedor?->id,
                    $proveedor?->tipo,
                    $proveedor?->detalle_documento,
                    $proveedor?->detalle_impuesto,
                    $proveedor?->final,

                    $cobranzaCompra?->id,
                    $cobranzaCompra?->rut_cliente,
                    $cobranzaCompra?->razon_social,
                    $cobranzaCompra?->nombre_cuenta,
                    $cobranzaCompra?->rut_cuenta,
                    $cobranzaCompra?->numero_cuenta,
                    $cobranzaCompra?->banco_id,
                    $cobranzaCompra?->tipo_cuenta_id,

                    optional($detalle->updated_at)->timestamp,
                    optional($asignacion?->updated_at)->timestamp,
                    optional($proveedor?->updated_at)->timestamp,
                    optional($cobranzaCompra?->updated_at)->timestamp,
                ]);
            })
            ->implode('||');

        return sha1('prefactura_grupo_v4_proveedor_efectivo|' . $anio . '|' . $mes . '|' . $base);
    }
}
