<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class HermanosExport implements FromArray, WithHeadings, WithTitle
{
    protected $hermanos;

    public function __construct(array $hermanos)
    {
        $this->hermanos = $hermanos;
    }

    public function array(): array
    {
        return $this->hermanos;
    }

    public function headings(): array
    {
        return ['Código', 'Cantidad de Hermanos'];
    }

    public function title(): string
    {
        return 'Hermanos';
    }
}
