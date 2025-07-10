<?php

namespace App\Exports;

use App\Models\Banco;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BancosExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Banco::select('id', 'nombre')->orderBy('id')->get();
    }

    public function headings(): array
    {
        return ['ID', 'Nombre'];
    }
}
