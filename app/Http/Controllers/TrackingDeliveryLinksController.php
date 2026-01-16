<?php

namespace App\Http\Controllers;

use App\Models\SystemEvents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Exports\TrackingPhotosExport;
use App\Exports\TrackingBatchExport;
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


    public function searchBatch(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | 1️⃣ Ver qué llega desde la vista
        |--------------------------------------------------------------------------
        */
        Log::info('Tracking batch raw input', [
            'payload' => $request->all(),
        ]);

        $request->validate([
            'trackings' => 'required|string',
        ]);

        $rawInput = $request->input('trackings');

        /*
        |--------------------------------------------------------------------------
        | 2️⃣ Normalizar input (split por línea)
        |--------------------------------------------------------------------------
        */
        $lines = preg_split('/\r\n|\r|\n/', $rawInput);

        Log::info('Tracking batch lines (raw)', [
            'lines' => $lines,
            'count' => count($lines),
        ]);

        /*
        |--------------------------------------------------------------------------
        | 3️⃣ Limpieza: trim, quitar vacíos, unique
        |--------------------------------------------------------------------------
        */
        $trackings = collect($lines)
            ->map(fn ($line) => trim($line))
            ->filter()
            ->unique()
            ->values();

        Log::info('Tracking batch normalized', [
            'trackings' => $trackings->toArray(),
            'count'     => $trackings->count(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | ⚠️ DEPURACIÓN DURA (opcional)
        |--------------------------------------------------------------------------
        | Descomenta SOLO si quieres detener ejecución y ver datos
        */
        // dd($trackings->toArray());

        /*
        |--------------------------------------------------------------------------
        | 4️⃣ Procesar cada tracking
        |--------------------------------------------------------------------------
        */
        $results = [];

        foreach ($trackings as $tracking) {

            Log::info('Processing tracking', [
                'tracking' => $tracking,
            ]);

            try {
                $url = 'https://4nlogistica.cl/tracking-proxy.php?tracking=' . urlencode($tracking);

                Log::info('Calling tracking proxy', [
                    'tracking' => $tracking,
                    'url'      => $url,
                ]);

                $response = file_get_contents($url);

                if (!$response) {
                    Log::warning('Empty response from proxy', [
                        'tracking' => $tracking,
                    ]);

                    $results[] = [
                        'tracking' => $tracking,
                        'success'  => false,
                        'message'  => 'Sin respuesta del servicio',
                        'delivery_state' => null,
                        'photos'   => [],
                    ];
                    continue;
                }

                $json = json_decode($response, true);

                Log::info('Proxy response decoded', [
                    'tracking' => $tracking,
                    'success'  => $json['success'] ?? null,
                ]);

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
                    ->pluck('url')
                    ->filter()
                    ->values()
                    ->toArray();

                Log::info('Tracking processed successfully', [
                    'tracking' => $tracking,
                    'state'    => $data['delivery_state'] ?? null,
                    'photos'   => count($photos),
                ]);

                $results[] = [
                    'tracking' => $tracking,
                    'success'  => true,
                    'delivery_state' => $data['delivery_state'] ?? null,
                    'photos'   => $photos,
                ];

            } catch (\Throwable $e) {

                Log::error('Error processing tracking', [
                    'tracking' => $tracking,
                    'error'    => $e->getMessage(),
                ]);

                $results[] = [
                    'tracking' => $tracking,
                    'success'  => false,
                    'message'  => 'Error interno al consultar tracking',
                    'delivery_state' => null,
                    'photos'   => [],
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 5️⃣ Respuesta final
        |--------------------------------------------------------------------------
        */
        Log::info('Tracking batch completed', [
            'total' => $trackings->count(),
            'ok'    => collect($results)->where('success', true)->count(),
            'fail'  => collect($results)->where('success', false)->count(),
        ]);

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




    // Exportación masiva
    public function exportBatch(Request $request)
    {
        Log::info('Tracking batch export requested (by photo)', [
            'payload' => $request->all(),
        ]);

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.tracking' => 'required|string',
            'items.*.state'    => 'nullable|string',
            'items.*.url'      => 'required|url',
        ]);

        $rows = [];

        foreach ($request->items as $item) {
            $rows[] = [
                'tracking' => $item['tracking'],
                'state'    => $item['state'] ?? '-',
                'url'      => $item['url'],
            ];
        }

        if (empty($rows)) {
            abort(404, 'No existen imágenes seleccionadas para exportar');
        }

        Log::info('Tracking batch export ready (by photo)', [
            'rows' => count($rows),
        ]);

        return Excel::download(
            new TrackingBatchExport($rows),
            'tracking_batch_pod.xlsx'
        );
    }








}
