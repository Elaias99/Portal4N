<?php

namespace App\Exports;

use App\Models\FormaPago;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FormaPagoExport implements FromCollection, WithHeadings
{

    public function collection()
    {
        return FormaPago::select('id', 'nombre')->orderBy('id')->get();
    }


    public function headings(): array
    {
        return ['ID', 'Nombre'];
    }

}
