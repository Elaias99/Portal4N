<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class CalendarioChileService
{
    public function obtenerFeriadosPorAnio(int $anio): array
    {
        $ruta = app_path("Data/Calendars/chile_{$anio}.php");

        if (!file_exists($ruta)) {
            return [];
        }

        return require $ruta;
    }

    public function obtenerFeriadosEntre(string|Carbon $fechaInicio, string|Carbon $fechaFin): array
    {
        $inicio = $fechaInicio instanceof Carbon ? $fechaInicio->copy()->startOfDay() : Carbon::parse($fechaInicio)->startOfDay();
        $fin = $fechaFin instanceof Carbon ? $fechaFin->copy()->startOfDay() : Carbon::parse($fechaFin)->startOfDay();

        $anios = range($inicio->year, $fin->year);
        $feriados = [];

        foreach ($anios as $anio) {
            $feriados = array_merge($feriados, $this->obtenerFeriadosPorAnio($anio));
        }

        return collect($feriados)
            ->filter(function ($feriado) use ($inicio, $fin) {
                $fecha = Carbon::parse($feriado['date'])->startOfDay();
                return $fecha->betweenIncluded($inicio, $fin);
            })
            ->values()
            ->all();
    }

    public function esFeriado(string|Carbon $fecha): bool
    {
        $fechaCarbon = $fecha instanceof Carbon ? $fecha->copy()->startOfDay() : Carbon::parse($fecha)->startOfDay();

        $feriados = $this->obtenerFeriadosPorAnio($fechaCarbon->year);

        return collect($feriados)->contains(function ($feriado) use ($fechaCarbon) {
            return $feriado['date'] === $fechaCarbon->toDateString();
        });
    }

    public function calcularDiasHabiles(string|Carbon $fechaInicio, string|Carbon $fechaFin): array
    {
        $inicio = $fechaInicio instanceof Carbon ? $fechaInicio->copy()->startOfDay() : Carbon::parse($fechaInicio)->startOfDay();
        $fin = $fechaFin instanceof Carbon ? $fechaFin->copy()->startOfDay() : Carbon::parse($fechaFin)->startOfDay();

        $periodo = CarbonPeriod::create($inicio, $fin);

        $diasHabiles = 0;
        $diasExcluidos = [];
        $feriadosEncontrados = $this->obtenerFeriadosEntre($inicio, $fin);

        $feriadosMap = collect($feriadosEncontrados)->keyBy('date');

        foreach ($periodo as $fecha) {
            $fechaTexto = $fecha->toDateString();

            if ($fecha->isWeekend()) {
                $diasExcluidos[] = [
                    'date' => $fechaTexto,
                    'reason' => 'fin_de_semana',
                    'name' => $fecha->isSaturday() ? 'Sábado' : 'Domingo',
                ];
                continue;
            }

            if ($feriadosMap->has($fechaTexto)) {
                $feriado = $feriadosMap->get($fechaTexto);

                $diasExcluidos[] = [
                    'date' => $fechaTexto,
                    'reason' => 'feriado',
                    'name' => $feriado['name'] ?? 'Feriado',
                    'type' => $feriado['type'] ?? null,
                    'irrenunciable' => $feriado['irrenunciable'] ?? false,
                ];
                continue;
            }

            $diasHabiles++;
        }

        return [
            'dias_habiles' => $diasHabiles,
            'feriados' => array_values($feriadosEncontrados),
            'dias_excluidos' => $diasExcluidos,
        ];
    }
}