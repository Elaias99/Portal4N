<?php

namespace App\Exports;

use App\Models\TipoCuenta;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TipoCuentaExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return TipoCuenta::select('id', 'nombre')->orderBy('id')->get();
    }

    public function headings(): array
    {
        return ['ID', 'Nombre'];
    }
}
