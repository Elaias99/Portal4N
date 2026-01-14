<?php

namespace App\Services\Calendar;

class ChileCalendarService
{
    /**
     * Retorna los feriados de Chile para 2026
     */
    public function getHolidays2026(): array
    {
        return $this->loadCalendar(2026);
    }

    /**
     * Carga el archivo de calendario según el año
     */
    private function loadCalendar(int $year): array
    {
        $path = app_path("Data/Calendars/chile_{$year}.php");

        if (!file_exists($path)) {
            return [];
        }

        return require $path;
    }
}
