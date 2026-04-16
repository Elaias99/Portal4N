<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrackingAlmacenado;
use Illuminate\Http\Request;

class LatamTrackingApiController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'prefijo' => $request->input('prefijo'),
            'codigo_tracking' => $request->input('codigo_tracking'),
            'destino' => $request->input('destino'),
            'fecha_proceso' => $request->input('fecha_proceso'),
        ];

        $hasFilters = filled($filters['prefijo'])
            || filled($filters['codigo_tracking'])
            || filled($filters['destino'])
            || filled($filters['fecha_proceso']);

        if (!$hasFilters) {
            $filters['fecha_proceso'] = now()->toDateString();
        }

        $query = TrackingAlmacenado::query()
            ->with('estadoActual')
            ->orderByDesc('id');

        $prefijo = trim((string) ($filters['prefijo'] ?? ''));
        $codigoTracking = trim((string) ($filters['codigo_tracking'] ?? ''));
        $destino = trim((string) ($filters['destino'] ?? ''));
        $fechaProceso = $filters['fecha_proceso'] ?? null;

        if ($prefijo !== '') {
            $query->where('prefijo', 'like', '%' . $prefijo . '%');
        }

        if ($codigoTracking !== '') {
            $query->where('codigo_tracking', 'like', '%' . $codigoTracking . '%');
        }

        if ($destino !== '') {
            $query->where('destino', 'like', '%' . $destino . '%');
        }

        if (!empty($fechaProceso)) {
            $query->whereDate('fecha_proceso', $fechaProceso);
        }

        $rows = $query->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'prefijo' => $item->prefijo,
                'codigo_tracking' => $item->codigo_tracking,
                'destino' => $item->destino,
                'fecha_proceso' => $item->fecha_proceso,
                'estado_actual' => $item->estadoActual ? [
                    'id' => $item->estadoActual->id,
                    'tiene_estado_valido' => (bool) $item->estadoActual->tiene_estado_valido,
                    'estado_resumen' => $item->estadoActual->estado_resumen,
                    'origen' => $item->estadoActual->origen,
                    'destino_latam' => $item->estadoActual->destino_latam,
                    'pieces' => $item->estadoActual->pieces,
                    'weight' => $item->estadoActual->weight,
                    'latest_event_code' => $item->estadoActual->latest_event_code,
                    'latest_event_description' => $item->estadoActual->latest_event_description,
                    'latest_event_station' => $item->estadoActual->latest_event_station,
                    'latest_event_time_raw' => $item->estadoActual->latest_event_time_raw,
                    'latest_leg_flight' => $item->estadoActual->latest_leg_flight,
                    'latest_leg_etd_raw' => $item->estadoActual->latest_leg_etd_raw,
                    'latest_leg_eta_raw' => $item->estadoActual->latest_leg_eta_raw,
                    'ultima_consulta_at' => optional($item->estadoActual->ultima_consulta_at)?->toISOString(),
                    'ultima_consulta_exitosa_at' => optional($item->estadoActual->ultima_consulta_exitosa_at)?->toISOString(),
                    'ultimo_cambio_at' => optional($item->estadoActual->ultimo_cambio_at)?->toISOString(),
                ] : null,
            ];
        });

        return response()->json([
            'filters' => $filters,
            'count' => $rows->count(),
            'data' => $rows,
        ]);
    }
}