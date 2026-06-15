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
        private SuscripcionPrefacturaOcService $ocService
    ) {}

    public function generarDesdeDetalles(Collection $detallesBase, int $anio, int $mes): array
    {
        @set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $baseDir = storage_path('app/temp_prefacturas');
        $zipDir = $baseDir . DIRECTORY_SEPARATOR . 'zips';
        $pdfDir = $baseDir . DIRECTORY_SEPARATOR . 'pdfs';

        foreach ([$baseDir, $zipDir, $pdfDir] as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
        }

        $detallesConProveedor = $detallesBase
            ->filter(fn ($detalle) => $detalle->asignacion?->suscripcion_proveedor_id)
            ->values();

        $detallesPorPrefactura = $this->agrupacionService
            ->agruparPorPrefactura($detallesConProveedor);

        if ($detallesPorPrefactura->isEmpty()) {
            throw new \RuntimeException('No se generó ningún PDF.');
        }

        $zipCacheKey = $this->generarCacheKey($detallesBase, $anio, $mes);

        $zipFileName = 'prefacturas_suscripciones_'
            . $anio
            . '_'
            . str_pad($mes, 2, '0', STR_PAD_LEFT)
            . '_'
            . $zipCacheKey
            . '.zip';

        $zipPath = $zipDir . DIRECTORY_SEPARATOR . $zipFileName;

        if (file_exists($zipPath) && filesize($zipPath) > 0) {
            return [
                'zip_path' => $zipPath,
                'zip_file_name' => $zipFileName,
                'generados' => $detallesPorPrefactura->count(),
                'desde_cache' => true,
            ];
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('No se pudo crear el archivo ZIP.');
        }

        $meses = $this->meses();
        $generados = 0;
        $nombresPdfUsados = [];

        foreach ($detallesPorPrefactura as $detallesPrefactura) {
            $detallesPrefactura = $detallesPrefactura
                ->sortBy('codigo')
                ->values();

            if ($detallesPrefactura->isEmpty()) {
                continue;
            }

            $detalle = $detallesPrefactura->first();

            $proveedor = $detalle->asignacion?->suscripcionProveedor;
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

            $pdfCacheKey = $this->generarCacheKey($detallesPrefactura, $anio, $mes);

            $pdfPath = $pdfDir . DIRECTORY_SEPARATOR . $pdfCacheKey . '.pdf';

            if (!file_exists($pdfPath) || filesize($pdfPath) === 0) {
                $calculosDetalle = $this->resumenService->calcularPorDetalles($detallesPrefactura);

                $totalBruto = $detallesPrefactura->sum('total');
                $totalImpuesto = $calculosDetalle->sum('total_impuesto');
                $totalLiquido = $calculosDetalle->sum('liquido');

                $pdf = Pdf::loadView('suscripciones.liquidacion_detalles.pdf', [
                    'detalle' => $detalle,
                    'detallesProveedor' => $detallesPrefactura,
                    'calculosDetalle' => $calculosDetalle,
                    'proveedor' => $proveedor,
                    'cobranzaCompra' => $cobranzaCompra,
                    'totalBruto' => $totalBruto,
                    'totalImpuesto' => $totalImpuesto,
                    'totalLiquido' => $totalLiquido,
                    'meses' => $meses,
                    'grupoPrefactura' => $grupoPrefactura,
                    'grupoPrefacturaLabel' => $grupoPrefacturaLabel,
                    'ocPrefactura' => $ocPrefactura,
                ])->setPaper('letter', 'portrait');

                file_put_contents($pdfPath, $pdf->output());

                unset($pdf, $calculosDetalle);
                gc_collect_cycles();
            }

            if (!$zip->addFile($pdfPath, $nombrePdf)) {
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

                return implode('|', [
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

        return sha1('prefactura_grupo_v3_oc|' . $anio . '|' . $mes . '|' . $base);
    }
}