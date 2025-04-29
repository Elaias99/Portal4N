<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProveedoresFaltantesExport implements FromCollection, WithHeadings
{
    protected $faltantes;

    public function __construct(array $faltantes)
    {
        $this->faltantes = $faltantes;
    }

    public function collection()
    {
        return new Collection($this->faltantes);
    }

    public function headings(): array
    {
        // Usamos los mismos headings que la plantilla de proveedores
        return (new PlantillaProveedoresExport())->headings();
    }
}
