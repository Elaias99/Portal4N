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
            'empresa',
            'cobranzaCompra.banco',
        ]);

        $cobranza = $honorario->cobranzaCompra;

        // =========================
        // CUENTA ORIGEN
        // =========================
        $cuentaOrigen = $honorario?->empresa?->cta_corriente ?? '0';
        $moneda       = 'CLP';

        // =========================
        // CUENTA DESTINO
        // =========================
        $cuentaDestino = $cobranza?->numero_cuenta ?? '';

        // =========================
        // BANCO
        // =========================
        $codigoBanco = $cobranza?->banco_id
            ? str_pad($cobranza->banco_id, 2, '0', STR_PAD_LEFT)
            : '';

        // =========================
        // BENEFICIARIO
        // =========================
        $rutBeneficiario = $cobranza?->rut_cliente
            ? preg_replace('/[^0-9kK]/', '', $cobranza->rut_cliente)
            : '';

        $nombreBeneficiario = $cobranza?->razon_social ?? '';

        // =========================
        // MONTO
        // =========================
        $monto = (int) $honorario->monto_pagado;

        // =========================
        // GLOSA (ÚNICA FUENTE)
        // =========================
        $glosa = "Pago honorario {$rutBeneficiario} folio {$honorario->folio}";

        return [
            $cuentaOrigen,        // Cuenta origen
            $moneda,              // Moneda origen
            $cuentaDestino,       // Cuenta destino
            $moneda,              // Moneda destino
            $codigoBanco,         // Código banco destino
            $rutBeneficiario,     // RUT beneficiario
            $nombreBeneficiario,  // Nombre beneficiario
            $monto,               // Monto transferencia
            $glosa,               // Glosa personalizada transferencia
            null,                 // Correo beneficiario
            null,                 // Mensaje correo beneficiario
            $glosa,               // Glosa cartola originador
            $glosa,               // Glosa cartola beneficiario (MISMA glosa)
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
