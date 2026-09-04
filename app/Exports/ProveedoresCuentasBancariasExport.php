<?php

namespace App\Exports;

use App\Models\CobranzaCompra;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProveedoresCuentasBancariasExport implements FromCollection, WithHeadings, WithMapping, WithEvents, WithTitle
{
    /**
     * Nombre de la hoja dentro del archivo Excel.
     */
    public function title(): string
    {
        return 'Proveedores';
    }

    /**
     * TODOS los proveedores (cobranza_compras), sin filtrar por lo que
     * se vea en pantalla, ordenados alfabéticamente por razón social.
     * Se carga la relación 'banco' para poder leer bancos.nombre sin
     * romper si banco_id es NULL o no matchea con ningún registro.
     */
    public function collection()
    {
        return CobranzaCompra::with('banco')
            ->orderBy('razon_social')
            ->get();
    }

    /**
     * Encabezados de columna, en el orden exacto pedido.
     */
    public function headings(): array
    {
        return [
            'Nombre Proveedor',
            'RUT Proveedor',
            'RUT a quien se paga',
            'Nombre a quien se paga',
            'Numero de Cuenta',
            'Banco',
            'Correo',
        ];
    }

    /**
     * Mapeo fila por fila. No se limpia ni normaliza ningún valor:
     * "Sin registro", "0", etc. se muestran tal cual vienen de la BD.
     */
    public function map($proveedor): array
    {
        return [
            $proveedor->razon_social,
            $proveedor->rut_cliente,
            $proveedor->rut_cuenta,
            $proveedor->nombre_cuenta,
            $proveedor->numero_cuenta,
            $proveedor->banco->nombre ?? '', // banco_id NULL o sin match -> celda vacía, sin error
            $proveedor->correo_suscripciones,
        ];
    }

    /**
     * Título superior + estilos, igual a la plantilla de referencia:
     * Fila 1 = título (fondo azul oscuro), Fila 2 = espacio,
     * Fila 3 = encabezados (fondo azul claro), datos desde Fila 4.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $ultimaColumna = 'G';

                // Empuja lo que Laravel Excel ya escribió (encabezados en fila 1,
                // datos desde fila 2) dos filas hacia abajo: encabezados -> fila 3,
                // datos -> fila 4+. Deja libres las filas 1 y 2 para el título.
                $sheet->insertNewRowBefore(1, 2);

                // === Fila 1: Título ===
                $sheet->mergeCells("A1:{$ultimaColumna}1");
                $sheet->setCellValue('A1', 'Reporte de Proveedores y Cuentas Bancarias — Cuentas por Pagar');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'name' => 'Arial',
                        'bold' => true,
                        'size' => 14,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1F4E78'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(26);
                $sheet->getRowDimension(2)->setRowHeight(6);

                // === Fila 3: Encabezados ===
                $sheet->getStyle("A3:{$ultimaColumna}3")->applyFromArray([
                    'font' => [
                        'name' => 'Arial',
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '2E75B6'],
                    ],
                ]);

                // === Fuente de las filas de datos ===
                $ultimaFila = $sheet->getHighestRow();
                if ($ultimaFila >= 4) {
                    $sheet->getStyle("A4:{$ultimaColumna}{$ultimaFila}")
                        ->getFont()->setName('Arial')->setSize(10);
                }

                // === Autofiltro y panel congelado (como el ejemplo) ===
                $sheet->setAutoFilter("A3:{$ultimaColumna}{$ultimaFila}");
                $sheet->freezePane('A4');

                // === Anchos de columna ===
                $sheet->getColumnDimension('A')->setWidth(42);
                $sheet->getColumnDimension('B')->setWidth(16);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(38);
                $sheet->getColumnDimension('E')->setWidth(20);
                $sheet->getColumnDimension('F')->setWidth(20);
                $sheet->getColumnDimension('G')->setWidth(32);
            },
        ];
    }
}