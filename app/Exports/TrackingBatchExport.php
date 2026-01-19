<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Hyperlink;

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
                $row['url'], // URL visible completa
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
                    $rowNumber = $index + 2;

                    $event->sheet
                        ->getCell("C{$rowNumber}")
                        ->setHyperlink(new Hyperlink($row['url']));
                }
            },
        ];
    }
}


