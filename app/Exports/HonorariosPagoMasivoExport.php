<?php

namespace App\Exports;

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

    /**
     * Recibe los honorarios ya pagados
     */
    public function __construct(Collection $honorarios)
    {
        $this->honorarios = $honorarios;
    }

    /**
     * Colección base del export
     */
    public function collection(): Collection
    {
        return $this->honorarios;
    }

    /**
     * Mapeo: 1 fila = 1 honorario
     */
    public function map($honorario): array
    {
        $honorario->loadMissing([
            'cobranzaCompra.banco',
        ]);

        $cobranza = $honorario->cobranzaCompra;

        // Cuenta origen
        $cuentaOrigen = '0';
        $moneda = 'CLP';

        // Cuenta destino
        $cuentaDestino = $cobranza->numero_cuenta ?? '';

        // Código banco (2 dígitos)
        $codigoBanco = $cobranza?->banco_id
            ? str_pad($cobranza->banco_id, 2, '0', STR_PAD_LEFT)
            : '';

        // Beneficiario
        $rutBeneficiario = $cobranza?->rut_cliente
            ? preg_replace('/[^0-9kK]/', '', $cobranza->rut_cliente)
            : '';

        $nombreBeneficiario = $cobranza->razon_social ?? '';

        // Monto
        $monto = (int) $honorario->monto_pagado;

        // Glosa
        $glosa = "Pago honorario folio {$honorario->folio}";

        return [
            $cuentaOrigen,
            $moneda,
            $cuentaDestino,
            $moneda,
            $codigoBanco,
            $rutBeneficiario,
            $nombreBeneficiario,
            $monto,
            $glosa,
            null,
            null,
            $glosa,
            null,
        ];
    }

    /**
     * Encabezados (idénticos al archivo del otro sistema)
     */
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

    /**
     * Estilos
     */
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
