<?php

namespace App\Http\Controllers;

use App\Models\SystemEvents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Exports\TrackingPhotosExport;
use App\Exports\TrackingBatchExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Http;


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
                        ->toArray(),
                ],
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar el servicio',
            ], 500);
        }
    }


    public function searchBatch(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        |Ver qué llega desde la vista
        |--------------------------------------------------------------------------
        */
        $request->validate([
            'trackings' => 'required|string',
        ]);

        $rawInput = $request->input('trackings');

        /*
        |--------------------------------------------------------------------------
        |Normalizar input (split por línea)
        |--------------------------------------------------------------------------
        */
        $lines = preg_split('/\r\n|\r|\n/', $rawInput);
        /*
        |--------------------------------------------------------------------------
        |Limpieza: trim, quitar vacíos, unique
        |--------------------------------------------------------------------------
        */
        $trackings = collect($lines)
            ->map(fn ($line) => trim($line))
            ->filter()
            ->unique()
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Procesar cada tracking (PARALELO)
        |--------------------------------------------------------------------------
        */
        $results = [];

        /**
         * Ejecutamos todas las consultas en paralelo
         */
        $responses = Http::pool(function ($pool) use ($trackings) {
            foreach ($trackings as $tracking) {
                $pool->get('https://4nlogistica.cl/tracking-proxy.php', [
                    'tracking' => $tracking,
                ]);
            }
        });

        /**
         * Procesamos las respuestas manteniendo el orden
         */
        foreach ($responses as $index => $response) {

            $tracking = $trackings[$index];

            if ($response->failed()) {

                $results[] = [
                    'tracking' => $tracking,
                    'success'  => false,
                    'message'  => 'Error al consultar el servicio',
                    'delivery_state' => null,
                    'photos'   => [],
                ];
                continue;
            }

            $json = $response->json();

            if (empty($json['success'])) {
                $results[] = [
                    'tracking' => $tracking,
                    'success'  => false,
                    'message'  => $json['message'] ?? 'Tracking no encontrado',
                    'delivery_state' => null,
                    'photos'   => [],
                ];
                continue;
            }

            $data = $json['data'] ?? [];

            $photos = collect($data['delivery_proof']['photos'] ?? [])
                ->map(function ($photo) {
                    return [
                        'url' => $photo['url'] ?? null,
                        'preview_url' => $photo['preview_url'] ?? null,
                    ];
                })
                ->filter(fn ($photo) => !empty($photo['url']))
                ->values()
                ->toArray();

            $results[] = [
                'tracking' => $tracking,
                'success'  => true,
                'delivery_state' => $data['delivery_state'] ?? null,
                'photos'   => $photos,
            ];
        }





        session([
            'tracking_batch_results' => $results
        ]);

        return response()->json([
            'success' => true,
            'count'   => $trackings->count(),
            'results' => $results,
        ]);
    }



    public function export(Request $request)
    {
        $request->validate([
            'tracking' => 'required|string|max:100',
        ]);

        $tracking = trim($request->tracking);

        //reutilizamos el MISMO flujo que search()
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




    // Exportación masiva
    public function exportBatch(Request $request)
    {

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.tracking' => 'required|string',
            'items.*.state'    => 'nullable|string',
            'items.*.url'      => 'required|url',
        ]);

        // Normalizar y limpiar filas
        $rows = collect($request->items)
            ->map(function ($item) {
                return [
                    'tracking' => trim($item['tracking']),
                    'state'    => $item['state'] ?? '-',
                    'url'      => trim($item['url']),
                ];
            })
            ->filter(fn ($row) => !empty($row['url']))
            ->values()
            ->toArray();

        if (empty($rows)) {
            abort(404, 'No existen imágenes seleccionadas para exportar');
        }

        return Excel::download(
            new TrackingBatchExport($rows),
            'tracking_batch_pod_' . now()->format('Ymd_His') . '.xlsx'
        );
    }








}
