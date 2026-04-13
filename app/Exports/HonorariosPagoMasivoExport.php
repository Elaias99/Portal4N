<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HonorariosPagoMasivoExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithStyles
{
    protected Collection $honorarios;

    public function __construct(Collection $honorarios)
    {
        $this->honorarios = $honorarios;
    }

    public function collection(): Collection
    {
        return $this->honorarios;
    }

    public function map($honorario): array
    {
        $honorario->loadMissing([
            'empresa',
            'cobranzaCompra.banco',
            'pagos',
        ]);

        $cobranza = $honorario->cobranzaCompra;

        $cuentaOrigen = $honorario?->empresa?->cta_corriente ?? '0';
        $moneda = 'CLP';

        $cuentaDestino = $cobranza?->numero_cuenta ?? '';

        $codigoBanco = $cobranza?->banco_id
            ? str_pad($cobranza->banco_id, 2, '0', STR_PAD_LEFT)
            : '';

        $rutBeneficiario = $honorario->rut_emisor
            ? preg_replace('/[^0-9kK]/', '', $honorario->rut_emisor)
            : '';

        $nombreBeneficiario = $cobranza?->razon_social ?? '';

        $monto = (int) $honorario->monto_pagado;

        $correoBeneficiario = $this->resolverCorreoBeneficiario(
            $cobranza?->responsable
        );

        $fechaPago = $honorario->pagos
            ->sortByDesc('fecha_pago')
            ->first()?->fecha_pago;

        $fechaPagoFormateada = $fechaPago
            ? Carbon::parse($fechaPago)->format('dmY')
            : now()->format('dmY');

        $glosaPago = "Pago Honorarios {$fechaPagoFormateada}";
        $glosaFolio = "Pago Folio N {$honorario->folio}";

        return [
            $cuentaOrigen,         // A Cuenta origen
            $moneda,               // B Moneda origen
            $cuentaDestino,        // C Cuenta destino
            $moneda,               // D Moneda destino
            $codigoBanco,          // E Código banco destino
            $rutBeneficiario,      // F RUT beneficiario
            $nombreBeneficiario,   // G Nombre beneficiario
            $monto,                // H Monto transferencia
            $glosaPago,            // I Glosa personalizada transferencia
            $correoBeneficiario,   // J Correo beneficiario
            $glosaFolio,           // K Mensaje correo beneficiario
            $glosaPago,            // L Glosa cartola originador
            $glosaFolio,           // M Glosa cartola beneficiario
        ];
    }

    private function resolverCorreoBeneficiario(?string $responsable): ?string
    {
        if (!$responsable) {
            return null;
        }

        $responsableNormalizado = mb_strtolower(
            preg_replace('/\s+/', ' ', trim($responsable))
        );

        return match ($responsableNormalizado) {
            'hans de la barra' => 'proveedores@4nlogistica.cl',
            'luis de la barra' => 'proveedores@4nlogistica.cl',
            'natalia leyton' => 'finanzas@4nlogistica.cl',
            default => null,
        };
    }

    public function headings(): array
    {
        return [
            'Cuenta origen',
            'Moneda origen',
            'Cuenta destino',
            'Moneda destino',
            'Código banco destino',
            'RUT beneficiario',
            'Nombre beneficiario',
            'Monto transferencia',
            'Glosa personalizada transferencia',
            'Correo beneficiario',
            'Mensaje correo beneficiario',
            'Glosa cartola originador',
            'Glosa cartola beneficiario',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => '1F2937'],
                ],
            ],
            'A:Z' => [
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center',
                ],
            ],
        ];
    }
}