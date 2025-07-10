<?php

namespace App\Exports;

use App\Models\TipoDocumento;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TipoDocumentoExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return TipoDocumento::select('id', 'nombre')->orderBy('id')->get();
    }

    public function headings(): array
    {
        return ['ID', 'Nombre'];
    }
}
