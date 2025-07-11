<?php

namespace App\Exports;

use App\Models\CentroCosto;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CentroCostoExport implements FromCollection, WithHeadings
{
    
    public function collection()
    {
        return CentroCosto::select('id', 'nombre')->orderBy('id')->get();
    }


    public function headings(): array
    {
        return ['ID', 'Nombre'];
    }






}
