<?php

namespace App\Http\Controllers;

use App\Models\SystemEvents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Exports\TrackingPhotosExport;
use Maatwebsite\Excel\Facades\Excel;

class TrackingDeliveryLinksController extends Controller
{
    public function index()
    {
        $events = SystemEvents::query()
            ->where('event_type', 'tracking.found')
            ->whereNotNull('payload->photos')
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('reference_id');

        $trackings = $events->map(function ($event) {
            $photos = $event->payload['photos'] ?? [];

            return [
                'id'       => $event->id,
                'tracking' => $event->reference_id,
                'state'    => $event->payload['delivery_state'] ?? '-',
                'photos'   => count($photos),
                'date'     => $event->created_at->format('d-m-Y H:i'),
                'link'     => $photos[0] ?? null,
            ];
        });

        return view('reports.delivery-links', compact('trackings'));
    }

    /**
     * Buscar tracking manualmente (input)
     */
    public function search(Request $request)
    {
        $request->validate([
            'tracking' => 'required|string|max:100',
        ]);

        $tracking = trim($request->tracking);

        try {
            $response = file_get_contents(
                'https://4nlogistica.cl/tracking-proxy.php?tracking=' . urlencode($tracking)
            );

            if (!$response) {
                throw new \Exception('Sin respuesta del proxy');
            }

            $data = json_decode($response, true);

            if (!$data || !$data['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tracking no encontrado',
                ]);
            }

            return response()->json([
                'success' => true,
                'source'  => 'proxy',
                'data'    => [
                    'delivery_state' => $data['data']['delivery_state'] ?? null,
                    'photos' => collect($data['data']['delivery_proof']['photos'] ?? [])
                        ->pluck('url')
                        ->toArray(), // 👈 AQUÍ VIENEN LAS 3
                ],
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar el servicio',
            ], 500);
        }
    }



    public function export(Request $request)
    {
        $request->validate([
            'tracking' => 'required|string|max:100',
        ]);

        $tracking = trim($request->tracking);

        // 👉 reutilizamos el MISMO flujo que search()
        $response = file_get_contents(
            'https://4nlogistica.cl/tracking-proxy.php?tracking=' . urlencode($tracking)
        );

        $data = json_decode($response, true);

        if (!$data || !$data['success']) {
            abort(404, 'Tracking no encontrado');
        }

        $state = $data['data']['delivery_state'] ?? '-';

        $photos = collect($data['data']['delivery_proof']['photos'] ?? [])
            ->pluck('url')
            ->toArray();

        if (empty($photos)) {
            abort(404, 'No existen fotos de entrega');
        }

        return Excel::download(
            new TrackingPhotosExport($tracking, $state, $photos),
            "tracking_{$tracking}.xlsx"
        );
    }




}
