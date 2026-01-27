<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class LabelsTemplateExport implements FromArray
{
    public function array(): array
    {
        return [
            ['QR', 'Atencion', 'Direccion', 'Comuna'],
        ];
    }
}
