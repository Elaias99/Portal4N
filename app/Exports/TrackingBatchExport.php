<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TrackingBatchExport implements FromArray, WithHeadings
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
}

