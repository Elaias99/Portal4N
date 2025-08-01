<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ManifiestosExport implements FromArray, WithHeadings
{
    protected $rows;
    protected $headers;

    public function __construct(array $headers, array $rows)
    {
        $this->headers = $headers;
        $this->rows = $rows;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headers;
    }
}



