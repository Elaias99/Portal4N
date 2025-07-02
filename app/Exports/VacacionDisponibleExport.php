<?php

namespace App\Exports;

use App\Models\Trabajador;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Carbon\Carbon;

class VacacionDisponibleExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Trabajador::with(['empresa', 'cargo', 'sistemaTrabajo', 'situacion'])
            ->whereHas('sistemaTrabajo', function ($q) {
                $q->where('nombre', '!=', 'Desvinculado');
            })
            ->whereHas('situacion', function ($q) {
                $q->where('Nombre', '!=', 'Desvinculado');
            })
            ->get()
            ->map(function ($trabajador) {
                return [
                    'Empresa' => $trabajador->empresa->Nombre ?? '',
                    'Nombre' => "{$trabajador->Nombre} {$trabajador->ApellidoPaterno} {$trabajador->ApellidoMaterno}",
                    'Rut' => $trabajador->Rut,
                    'Fecha Ingreso' => $trabajador->fecha_inicio_trabajo ? $trabajador->fecha_inicio_trabajo->format('Y-m-d') : '',
                    'Cargo' => $trabajador->cargo->Nombre ?? '',
                    'Días Disponibles' => $trabajador->calcularDiasProporcionales(),
                ];
            });
    }


    public function headings(): array
    {
        return ['Empresa', 'Nombre', 'Rut', 'Fecha Ingreso', 'Cargo', 'Días Disponibles'];
    }
}
