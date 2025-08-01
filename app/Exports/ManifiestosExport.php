<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ManifiestosExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting
{
    protected $rows;
    protected $headers;

    public function __construct(array $headers, array $rows)
    {
        $this->headers = $headers;
        $this->rows = collect($rows);
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function map($row): array
    {
        return [
            \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($row[0]), // ✅ Esto lo convierte a fecha real para Excel
            $row[1],
            $row[2],
            $row[3],
            $row[4],
            $row[5],
            $row[6],
            $row[7] ?? '',
            $row[8] ?? '',
            $row[9] ?? '',
        ];
    }


    public function headings(): array
    {
        return $this->headers;
    }

    public function columnFormats(): array
    {
        return [
            'A' => 'dd/mm/yyyy',
        ];
    }
}

