<?php

namespace App\Exports;

use App\Models\Comuna;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ComunasExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Comuna::with('region')->get()->map(function ($comuna) {
            return [
                'ID' => $comuna->id,
                'Nombre' => $comuna->Nombre,
                'Region' => $comuna->region ? $comuna->region->Nombre : '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Región',
        ];
    }
}
