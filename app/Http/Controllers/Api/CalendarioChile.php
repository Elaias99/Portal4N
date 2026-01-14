<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Calendar\ChileCalendarService;
use Illuminate\Http\JsonResponse;

class CalendarioChile extends Controller
{
    public function index(ChileCalendarService $calendar): JsonResponse
    {
        return response()->json([
            'country' => 'Chile',
            'year' => 2026,
            'data' => $calendar->getHolidays2026(),
        ]);
    }
}
