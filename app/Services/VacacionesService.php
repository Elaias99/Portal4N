<?php

namespace App\Services;

use App\Models\Trabajador;
use App\Models\Vacacion;
use App\Models\HistorialVacacion;
use Carbon\Carbon;

class VacacionesService
{
    public function calcularDiasDisponibles(Trabajador $trabajador)
    {
        if (!$trabajador->fecha_inicio_trabajo) {
            return 0;
        }

        // === Cálculo base de días proporcionales ===
        $fechaInicio = Carbon::parse($trabajador->fecha_inicio_trabajo);
        $fechaActual = Carbon::now();

        $diasMesInicio = $fechaInicio->daysInMonth;
        $diasTrabajadosMesInicio = $diasMesInicio - $fechaInicio->day + 1;
        $proporcionMesInicio = $diasTrabajadosMesInicio / $diasMesInicio;

        $mesesCompletos = $fechaInicio->diffInMonths($fechaActual) - 1;

        // 15 días al año → 1.25 días por mes
        $diasGenerados = (15 / 12) * ($mesesCompletos + $proporcionMesInicio);

        // === Días tomados en solicitudes aprobadas ===
        $diasTomadosVacaciones = Vacacion::whereHas('solicitud', function ($q) {
                $q->where('estado', 'aprobado')
                  ->where('tipo_dia', 'vacaciones');
            })
            ->where('trabajador_id', $trabajador->id)
            ->sum('dias');

        // === Días tomados históricos ===
        $diasTomadosHistoricos = HistorialVacacion::where('trabajador_id', $trabajador->id)
            ->where('tipo_dia', 'vacaciones')
            ->sum('dias_laborales');

        // === Saldo final ===
        $diasDisponibles = $diasGenerados - ($diasTomadosVacaciones + $diasTomadosHistoricos);

        return max(round($diasDisponibles, 2), 0);
    }
}
