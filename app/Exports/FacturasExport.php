<?php

namespace App\Exports;

use App\Models\Factura;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FacturasExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function collection()
    {
        return Factura::with('proveedor', 'empresa')->get()->map(function ($factura) {
            return [
                'ID' => $factura->id,
                'Proveedor ID' => $factura->proveedor->id ?? 'N/A',
                'Proveedor Nombre' => $factura->proveedor->razon_social ?? 'N/A',
                'Empresa ID' => $factura->empresa->id ?? 'N/A',
                'Empresa Nombre' => $factura->empresa->Nombre ?? 'N/A',
                'Centro de Costo' => $factura->centro_costo,
                'Glosa' => $factura->glosa,
                'Comentario' => $factura->comentario,
                'Pagador' => $factura->pagador,
                'Tipo Documento' => $factura->tipo_documento,
                'Estado' => $factura->status,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Proveedor ID',
            'Proveedor Nombre',
            'Empresa ID',
            'Empresa Nombre',
            'Centro de Costo',
            'Glosa',
            'Comentario',
            'Pagador',
            'Tipo Documento',
            'Estado',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para los encabezados (primera fila)
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => '4CAF50'], // Verde
                ],
            ],
            // Centrar las celdas
            'A:Z' => [
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center',
                ],
            ],
        ];
    }
}
