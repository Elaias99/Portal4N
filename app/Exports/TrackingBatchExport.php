<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Hyperlink;
use Maatwebsite\Excel\Concerns\WithEvents;



class TrackingBatchExport implements FromArray, WithHeadings, WithEvents
{
    protected array $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function array(): array
    {
        return array_map(function ($row) {
            return [
                $row['tracking'],
                $this->translateState($row['state']),
                $row['url'],
            ];
        }, $this->rows);
    }


    public function headings(): array
    {
        return [
            'Tracking',
            'Estado',
            'URL',
        ];
    }

    protected function translateState(string $state): string
    {
        return match ($state) {
            'pending' => 'Pendiente',
            'in_transit' => 'En tránsito',
            'out_for_delivery' => 'En reparto',
            'delivered' => 'Entregado',
            'cancelled' => 'Cancelado',
            default => $state,
        };
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                foreach ($this->rows as $index => $row) {
                    // +2 porque:
                    // fila 1 = headers
                    // Excel empieza en 1
                    $rowNumber = $index + 2;

                    $event->sheet
                        ->getCell("C{$rowNumber}")
                        ->setHyperlink(new Hyperlink($row['url']));
                }
            },
        ];
    }

}

