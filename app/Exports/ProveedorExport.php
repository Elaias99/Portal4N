<?php

namespace App\Exports;

use App\Models\Proveedor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProveedorExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithMapping
{
    public function collection()
    {
        // Cargamos también relaciones necesarias
        return Proveedor::with(['compras.tipoPago', 'banco'])->get();
    }

    public function map($proveedor): array
    {
        $filas = [];

        foreach ($proveedor->compras as $compra) {
            $nombreBanco = $proveedor->banco->nombre ?? 'N/A';
            $nombreTipoPago = $compra->tipoPago->nombre ?? 'N/A';

            $glosa = "{$compra->pago_total} + {$nombreTipoPago} + {$compra->numero_documento}";

            $filas[] = [
                $proveedor->nro_cuenta,
                $nombreBanco,
                $proveedor->rut,
                $proveedor->razon_social,
                $proveedor->correo_banco,
                $compra->pago_total,
                $glosa,
                $glosa,
                $glosa,
                $glosa,
            ];
        }

        if (empty($filas)) {
            $nombreBanco = $proveedor->banco->nombre ?? 'N/A';

            $filas[] = [
                $proveedor->nro_cuenta,
                $nombreBanco,
                $proveedor->rut,
                $proveedor->razon_social,
                $proveedor->correo_banco,
                'N/A',
                'N/A',
                'N/A',
                'N/A',
                'N/A',
            ];
            
        }

        return $filas[0];
    }

    public function headings(): array
    {
        return [
            'Número de Cuenta',
            'Banco',
            'RUT',
            'Razón Social',
            'Correo Banco',
            'Pago Total',
            'Glosa Transferencia',
            'Glosa Correo Beneficiario',
            'Glosa Cartola Cliente',
            'Glosa Cartola Beneficiario',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => '4CAF50'],
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
