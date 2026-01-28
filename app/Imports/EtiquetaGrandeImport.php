<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EtiquetaGrandeImport implements ToCollection, WithHeadingRow
{
    public Collection $rows;

    public function collection(Collection $collection)
    {
        // Guardamos todas las filas tal como vienen del Excel
        $this->rows = $collection;
    }
}
