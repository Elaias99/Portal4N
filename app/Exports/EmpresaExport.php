<?php

namespace App\Exports;

use App\Models\Empresa;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EmpresaExport implements FromCollection, WithHeadings
{

    public function collection()
    {
        return Empresa::with('banco')->get()->map(function ($empresa) {
            return [
                'ID' => $empresa->id,
                
                'Nombre Empresa' => $empresa->Nombre,
                'Giro' => $empresa->giro,

                'Cta Corriente' => $empresa->cta_corriente,


                'Banco' => $empresa->banco ? $empresa->banco->nombre : '',
            ];
        });
    }


    public function headings(): array
    {
        return [
            'ID',
            'Nombre Empresa',

            'Giro',

            'Cta. Corriente',

            'Banco',
        ];
    }



}
