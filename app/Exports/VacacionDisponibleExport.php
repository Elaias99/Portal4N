<?php

namespace App\Exports;

use App\Models\Trabajador;
use App\Services\VacacionesService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class VacacionDisponibleExport implements FromCollection, WithHeadings, WithMapping
{
    protected $service;

    public function __construct()
    {
        $this->service = new VacacionesService();
    }

    public function collection()
    {
        // Solo trabajadores contratados (vinculados)
        return Trabajador::where('situacion_id', 1)
            ->whereNull('deleted_at')
            ->get();
    }

    public function map($t): array
    {
        $diasDisponibles = $this->service->calcularDiasDisponibles($t);

        return [
            $t->Rut,
            trim("{$t->Nombre} {$t->ApellidoPaterno} {$t->ApellidoMaterno}"),
            $diasDisponibles
        ];
    }

    public function headings(): array
    {
        return [
            'RUT',
            'Nombre Completo',
            'Días Disponibles'
        ];
    }
}
