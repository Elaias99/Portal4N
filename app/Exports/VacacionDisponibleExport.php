<?php

namespace App\Exports;

use App\Models\HistorialVacacion;
use App\Models\Solicitud;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VacacionDisponibleExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        $registros = collect();

        // 1. Históricos / manuales que descuentan vacaciones
        $historicos = HistorialVacacion::with([
                'trabajador.situacion',
                'trabajador.sistemaTrabajo',
            ])
            ->where('tipo_dia', 'vacaciones')
            ->whereHas('trabajador', function ($q) {
                $q->whereNull('deleted_at')
                    ->whereHas('situacion', function ($sub) {
                        $sub->where('Nombre', '!=', 'Desvinculado');
                    })
                    ->whereHas('sistemaTrabajo', function ($sub) {
                        $sub->where('nombre', '!=', 'Desvinculado');
                    });
            })
            ->orderBy('fecha_inicio', 'asc')
            ->get();

        foreach ($historicos as $historico) {
            $trabajador = $historico->trabajador;

            if (!$trabajador) {
                continue;
            }

            $registros->push([
                'rut' => $trabajador->Rut ?? '',
                'trabajador' => trim(
                    ($trabajador->Nombre ?? '') . ' ' .
                    ($trabajador->ApellidoPaterno ?? '') . ' ' .
                    ($trabajador->ApellidoMaterno ?? '')
                ),
                'fecha_contratacion' => $trabajador->fecha_inicio_trabajo
                    ? $trabajador->fecha_inicio_trabajo->format('Y-m-d')
                    : '',
                'fecha_inicio' => $historico->fecha_inicio
                    ? $historico->fecha_inicio->format('Y-m-d')
                    : '',
                'fecha_fin' => $historico->fecha_fin
                    ? $historico->fecha_fin->format('Y-m-d')
                    : '',
                'dias_tomados' => $historico->dias_laborales ?? 0,
                'origen' => 'Histórico',
            ]);
        }

        // 2. Solicitudes aprobadas del sistema que descuentan vacaciones
        $solicitudesAprobadas = Solicitud::with([
                'trabajador.situacion',
                'trabajador.sistemaTrabajo',
                'vacacion',
            ])
            ->where('campo', 'Vacaciones')
            ->where('estado', 'aprobado')
            ->where('tipo_dia', 'vacaciones')
            ->whereHas('vacacion')
            ->whereHas('trabajador', function ($q) {
                $q->whereNull('deleted_at')
                    ->whereHas('situacion', function ($sub) {
                        $sub->where('Nombre', '!=', 'Desvinculado');
                    })
                    ->whereHas('sistemaTrabajo', function ($sub) {
                        $sub->where('nombre', '!=', 'Desvinculado');
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($solicitudesAprobadas as $solicitud) {
            $trabajador = $solicitud->trabajador;
            $vacacion = $solicitud->vacacion;

            if (!$trabajador || !$vacacion) {
                continue;
            }

            $registros->push([
                'rut' => $trabajador->Rut ?? '',
                'trabajador' => trim(
                    ($trabajador->Nombre ?? '') . ' ' .
                    ($trabajador->ApellidoPaterno ?? '') . ' ' .
                    ($trabajador->ApellidoMaterno ?? '')
                ),
                'fecha_contratacion' => $trabajador->fecha_inicio_trabajo
                    ? $trabajador->fecha_inicio_trabajo->format('Y-m-d')
                    : '',
                'fecha_inicio' => $vacacion->fecha_inicio
                    ? $vacacion->fecha_inicio->format('Y-m-d')
                    : '',
                'fecha_fin' => $vacacion->fecha_fin
                    ? $vacacion->fecha_fin->format('Y-m-d')
                    : '',
                'dias_tomados' => $vacacion->dias ?? 0,
                'origen' => 'Solicitud Aprobada',
            ]);
        }

        return $registros
            ->sortBy([
                ['trabajador', 'asc'],
                ['fecha_inicio', 'asc'],
            ])
            ->values();
    }

    public function headings(): array
    {
        return [
            'RUT',
            'Trabajador',
            'Fecha Contratación',
            'Fecha Inicio',
            'Fecha Fin',
            'Días Tomados',
            'Origen',
        ];
    }
}