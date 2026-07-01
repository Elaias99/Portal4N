<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class DocumentosVencimientosExport implements FromView, WithColumnFormatting, WithEvents
{
    protected $ventas;
    protected $compras;
    protected $inicio;
    protected $fin;

    public function __construct($ventas, $compras, $inicio, $fin)
    {
        $this->ventas = $ventas;
        $this->compras = $compras;
        $this->inicio = $inicio;
        $this->fin = $fin;
    }

    public function view(): View
    {
        return view('exports.documentos_vencimientos_excel', [
            'ventas' => $this->ventas,
            'compras' => $this->compras,
            'inicio' => $this->inicio,
            'fin' => $this->fin,
        ]);
    }

    public function columnFormats(): array
    {
        return [
            'C' => 'dd/mm/yyyy',
            'D' => '$ #,##0',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                for ($row = 1; $row <= $highestRow; $row++) {
                    $fechaCell = 'C' . $row;
                    $montoCell = 'D' . $row;

                    $fecha = $this->convertirFechaExcel($sheet->getCell($fechaCell)->getValue());
                    if ($fecha !== null) {
                        $sheet->setCellValue($fechaCell, $fecha);
                    }

                    $monto = $this->convertirMontoExcel($sheet->getCell($montoCell)->getValue());
                    if ($monto !== null) {
                        $sheet->setCellValue($montoCell, $monto);
                    }
                }

                $sheet->getStyle("C1:C{$highestRow}")
                    ->getNumberFormat()
                    ->setFormatCode('dd/mm/yyyy');

                $sheet->getStyle("D1:D{$highestRow}")
                    ->getNumberFormat()
                    ->setFormatCode('$ #,##0');
            },
        ];
    }

    private function convertirFechaExcel($valor): ?float
    {
        if ($valor instanceof \DateTimeInterface) {
            return Date::dateTimeToExcel($valor);
        }

        if (is_numeric($valor)) {
            return (float) $valor;
        }

        $valor = trim((string) $valor);

        if ($valor === '') {
            return null;
        }

        $formatos = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'Y/m/d'];

        foreach ($formatos as $formato) {
            try {
                $fecha = Carbon::createFromFormat($formato, $valor);

                if ($fecha !== false) {
                    return Date::dateTimeToExcel($fecha->startOfDay());
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    private function convertirMontoExcel($valor): ?int
    {
        if (is_numeric($valor)) {
            return (int) $valor;
        }

        $valor = trim((string) $valor);

        if ($valor === '' || !preg_match('/\d/', $valor)) {
            return null;
        }

        $numero = preg_replace('/[^\d\-]/', '', $valor);

        if ($numero === '' || $numero === '-') {
            return null;
        }

        return (int) $numero;
    }
}