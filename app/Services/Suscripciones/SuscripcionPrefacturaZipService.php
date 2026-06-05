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
        $tmpDir = storage_path('app/temp_prefacturas');

        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        $zipFileName = 'prefacturas_suscripciones_'
            . $anio
            . '_'
            . str_pad($mes, 2, '0', STR_PAD_LEFT)
            . '_'
            . now()->format('Ymd_His')
            . '.zip';

        $zipPath = $tmpDir . DIRECTORY_SEPARATOR . $zipFileName;

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('No se pudo crear el archivo ZIP.');
        }

        $meses = $this->meses();

        $detallesPorProveedor = $detallesBase
            ->filter(fn ($detalle) => $detalle->asignacion?->suscripcion_proveedor_id)
            ->groupBy(fn ($detalle) => $detalle->asignacion->suscripcion_proveedor_id);

        $generados = 0;

        foreach ($detallesPorProveedor as $detallesProveedor) {
            $detallesProveedor = $detallesProveedor
                ->sortBy('codigo')
                ->values();

            if ($detallesProveedor->isEmpty()) {
                continue;
            }

            $detalle = $detallesProveedor->first();

            $calculosDetalle = $this->resumenService->calcularPorDetalles($detallesProveedor);

            $proveedor = $detalle->asignacion?->suscripcionProveedor;
            $cobranzaCompra = $proveedor?->cobranzaCompra;

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

            $zip->addFromString(
                $this->nombreArchivoPdf($proveedor, $cobranzaCompra, $anio, $mes),
                $pdf->output()
            );

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
}