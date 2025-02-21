<?php

namespace App\Exports;

use App\Models\Proveedor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProveedorExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    /**
     * Selecciona todos los campos necesarios para crear un proveedor.
     */
    public function collection()
    {
        return Proveedor::all([
            'id',
            'razon_social',
            'rut',
            'banco',
            'tipo_cuenta',
            'nro_cuenta',
            'tipo_pago',
            'nombre_contacto',
            'rut_contacto',
        ]);
    }

    /**
     * Define los encabezados personalizados.
     */
    public function headings(): array
    {
        return [
            'ID', // Identificador único del proveedor
            'Razón Social', // Nombre del proveedor
            'RUT', // RUT del proveedor
            'Banco', // Banco del proveedor
            'Tipo de Cuenta', // Tipo de cuenta bancaria
            'Número de Cuenta', // Número de cuenta bancaria
            'Tipo de Pago', // Condiciones de pago
            'Nombre del Contacto', // Nombre del contacto principal
            'RUT del Contacto', // RUT del contacto principal
        ];
    }

    /**
     * Aplica estilos a los encabezados y datos.
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Encabezados en negrita con fondo verde
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => '4CAF50'], // Fondo verde
                ],
            ],
            // Centrado del contenido
            'A:Z' => [
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center',
                ],
            ],
        ];
    }
}
