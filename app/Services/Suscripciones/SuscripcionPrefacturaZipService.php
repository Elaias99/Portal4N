<?php

namespace App\Services\Suscripciones;

use App\Models\SuscripcionLiquidacionDetalle;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use ZipArchive;

class SuscripcionPrefacturaZipService
{
    public function __construct(
        private SuscripcionLiquidacionResumenService $resumenService
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

        $detallesPorProveedor = $detallesBase
            ->filter(fn ($detalle) => $detalle->asignacion?->suscripcion_proveedor_id)
            ->groupBy(fn ($detalle) => $detalle->asignacion->suscripcion_proveedor_id);

        if ($detallesPorProveedor->isEmpty()) {
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
                'generados' => $detallesPorProveedor->count(),
                'desde_cache' => true,
            ];
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('No se pudo crear el archivo ZIP.');
        }

        $meses = $this->meses();
        $generados = 0;

        foreach ($detallesPorProveedor as $detallesProveedor) {
            $detallesProveedor = $detallesProveedor
                ->sortBy('codigo')
                ->values();

            if ($detallesProveedor->isEmpty()) {
                continue;
            }

            $detalle = $detallesProveedor->first();

            $proveedor = $detalle->asignacion?->suscripcionProveedor;
            $cobranzaCompra = $proveedor?->cobranzaCompra;

            $nombrePdf = $this->nombreArchivoPdf($proveedor, $cobranzaCompra, $anio, $mes);

            $pdfCacheKey = $this->generarCacheKey($detallesProveedor, $anio, $mes);

            $pdfPath = $pdfDir . DIRECTORY_SEPARATOR . $pdfCacheKey . '.pdf';

            if (!file_exists($pdfPath) || filesize($pdfPath) === 0) {
                $calculosDetalle = $this->resumenService->calcularPorDetalles($detallesProveedor);

                $totalBruto = $detallesProveedor->sum('total');
                $totalImpuesto = $calculosDetalle->sum('total_impuesto');
                $totalLiquido = $calculosDetalle->sum('liquido');

                $pdf = Pdf::loadView('suscripciones.liquidacion_detalles.pdf', [
                    'detalle' => $detalle,
                    'detallesProveedor' => $detallesProveedor,
                    'calculosDetalle' => $calculosDetalle,
                    'proveedor' => $proveedor,
                    'cobranzaCompra' => $cobranzaCompra,
                    'totalBruto' => $totalBruto,
                    'totalImpuesto' => $totalImpuesto,
                    'totalLiquido' => $totalLiquido,
                    'meses' => $meses,
                ])->setPaper('letter', 'portrait');

                file_put_contents($pdfPath, $pdf->output());

                unset($pdf, $calculosDetalle);
                gc_collect_cycles();
            }

            $zip->addFile($pdfPath, $nombrePdf);

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

    private function nombreArchivoPdf($proveedor, $cobranzaCompra, int $anio, int $mes): string
    {
        $nombreProveedor = $cobranzaCompra?->razon_social ?? 'PROVEEDOR';
        $tipo = $proveedor?->tipo ?? 'DOC';

        $nombreLimpio = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nombreProveedor);
        $nombreLimpio = preg_replace('/_+/', '_', $nombreLimpio);
        $nombreLimpio = trim($nombreLimpio, '_');

        return 'Prefactura_Susc_'
            . $tipo
            . '_'
            . $nombreLimpio
            . '_'
            . $anio
            . '_'
            . str_pad($mes, 2, '0', STR_PAD_LEFT)
            . '.pdf';
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

        return sha1($anio . '|' . $mes . '|' . $base);
    }




}