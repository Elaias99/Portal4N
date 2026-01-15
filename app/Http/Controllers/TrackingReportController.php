<?php

namespace App\Http\Controllers;

use App\Models\SystemEvents;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class TrackingReportController extends Controller
{
    public function monthly(int $year, int $month)
    {
        // Fechas del mes
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end   = Carbon::create($year, $month, 1)->endOfMonth();

        // Eventos del mes
        $events = SystemEvents::whereBetween('created_at', [$start, $end])
            ->where('source', 'wordpress')
            ->get();

        // Métricas básicas
        $totalSearches = $events->where('event_type', 'tracking.search')->count();

        $uniqueTrackings = $events
            ->where('event_type', 'tracking.search')
            ->pluck('reference_id')
            ->unique()
            ->count();

        $foundEvents = $events->where('event_type', 'tracking.found');

        $deliveredCount = $foundEvents
            ->where('payload.delivery_state', 'delivered')
            ->count();

        $pendingCount = $foundEvents
            ->where('payload.delivery_state', 'pending')
            ->count();

        $withProof = $foundEvents
            ->where('payload.has_delivery_proof', true)
            ->count();

        // Datos que se enviarán al PDF
        $data = [
            'year'            => $year,
            'month'           => $month,
            'totalSearches'   => $totalSearches,
            'uniqueTrackings' => $uniqueTrackings,
            'deliveredCount'  => $deliveredCount,
            'pendingCount'    => $pendingCount,
            'withProof'       => $withProof,
        ];

        // Generar PDF
        $pdf = Pdf::loadView('reports.tracking-monthly', $data);

        return $pdf->download("informe-tracking-{$year}-{$month}.pdf");
    }
}
