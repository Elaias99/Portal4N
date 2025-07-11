<?php

namespace App\Exports;

use App\Models\PlazoPago;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PlazPagoExport implements FromCollection, WithHeadings
{

    public function collection()
    {
        return PlazoPago::select('id', 'nombre')->orderBy('id')->get();
    }


    public function headings(): array
    {
        return ['ID', 'Nombre'];
    }

}
