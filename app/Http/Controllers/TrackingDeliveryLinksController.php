<?php

namespace App\Http\Controllers;

use App\Exports\TrackingBatchExport;
use App\Exports\TrackingPhotosExport;
use App\Models\SystemEvents;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
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

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'tracking' => 'required|string|max:100',
        ]);

        $tracking = trim((string) $request->tracking);

        if ($tracking === '') {
            return response()->json([
                'success' => false,
                'message' => 'Número de seguimiento inválido.',
            ], 422);
        }

        $result = $this->fetchTracking($tracking);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'source'  => 'api',
            'data'    => [
                'delivery_state' => $result['data']['delivery_state'] ?? null,
                'photos' => collect($result['data']['photos'] ?? [])
                    ->pluck('url')
                    ->filter()
                    ->values()
                    ->all(),
            ],
        ]);
    }

    public function searchBatch(Request $request): JsonResponse
    {
        $request->validate([
            'trackings' => 'required|string',
        ]);

        $rawInput = (string) $request->input('trackings');

        $trackings = collect(preg_split('/\r\n|\r|\n/', $rawInput))
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->unique()
            ->values();

        if ($trackings->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Debes ingresar al menos un tracking.',
            ], 422);
        }

        $config = $this->getTrackingConfig();

        if (!$config['configured']) {
            return response()->json([
                'success' => false,
                'message' => 'La integración de tracking no está configurada.',
            ], 500);
        }

        $responses = Http::pool(function ($pool) use ($trackings, $config) {
            foreach ($trackings as $tracking) {
                $pool->as($tracking)
                    ->acceptJson()
                    ->withToken($config['token'])
                    ->timeout($config['timeout'])
                    ->get($config['base_url'] . '/' . urlencode($tracking));
            }
        });

        $results = $trackings->map(function ($tracking) use ($responses) {
            $response = $responses[$tracking] ?? null;

            if (!$response instanceof Response) {
                return [
                    'tracking' => $tracking,
                    'success' => false,
                    'message' => 'Error al consultar el servicio',
                    'delivery_state' => null,
                    'photos' => [],
                ];
            }

            $result = $this->parseTrackingResponse($tracking, $response);

            if (!$result['success']) {
                return [
                    'tracking' => $tracking,
                    'success' => false,
                    'message' => $result['message'],
                    'delivery_state' => null,
                    'photos' => [],
                ];
            }

            return [
                'tracking' => $tracking,
                'success' => true,
                'delivery_state' => $result['data']['delivery_state'] ?? null,
                'photos' => $result['data']['photos'] ?? [],
            ];
        })->values()->all();

        session([
            'tracking' => $results,
        ]);

        return response()->json([
            'success' => true,
            'count' => $trackings->count(),
            'results' => $results,
        ]);
    }

    public function export(Request $request)
    {
        $request->validate([
            'tracking' => 'required|string|max:100',
        ]);

        $tracking = trim((string) $request->tracking);

        $result = $this->fetchTracking($tracking);

        if (!$result['success']) {
            abort($result['status'], $result['message']);
        }

        $state = $result['data']['delivery_state'] ?? '-';

        $photos = collect($result['data']['photos'] ?? [])
            ->pluck('url')
            ->filter()
            ->values()
            ->all();

        if (empty($photos)) {
            abort(404, 'No existen fotos de entrega');
        }

        return Excel::download(
            new TrackingPhotosExport($tracking, $state, $photos),
            "tracking_{$tracking}.xlsx"
        );
    }

    public function exportBatch(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.tracking' => 'required|string',
            'items.*.state' => 'nullable|string',
            'items.*.url' => 'required|url',
        ]);

        $rows = collect($request->items)
            ->map(function ($item) {
                return [
                    'tracking' => trim((string) $item['tracking']),
                    'state' => $item['state'] ?? '-',
                    'url' => trim((string) $item['url']),
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
            'tracking_pruebas_entrega_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    private function fetchTracking(string $tracking): array
    {
        $config = $this->getTrackingConfig();

        if (!$config['configured']) {
            return [
                'success' => false,
                'message' => 'La integración de tracking no está configurada.',
                'status' => 500,
                'data' => null,
            ];
        }

        try {
            $response = Http::acceptJson()
                ->withToken($config['token'])
                ->timeout($config['timeout'])
                ->get($config['base_url'] . '/' . urlencode($tracking));

            return $this->parseTrackingResponse($tracking, $response);
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Ocurrió un error al consultar el tracking.',
                'status' => 500,
                'data' => null,
            ];
        }
    }

    private function parseTrackingResponse(string $tracking, Response $response): array
    {
        $json = $response->json();

        if ($response->failed()) {
            return [
                'success' => false,
                'message' => is_array($json)
                    ? ($json['message'] ?? 'No fue posible consultar el tracking en este momento.')
                    : 'No fue posible consultar el tracking en este momento.',
                'status' => $response->status() ?: 502,
                'data' => null,
            ];
        }

        if (!is_array($json) || empty($json['success'])) {
            return [
                'success' => false,
                'message' => is_array($json)
                    ? ($json['message'] ?? 'Tracking no encontrado.')
                    : 'Tracking no encontrado.',
                'status' => 404,
                'data' => null,
            ];
        }

        return [
            'success' => true,
            'message' => null,
            'status' => 200,
            'data' => $this->normalizeTrackingPayload($tracking, $json),
        ];
    }

    private function normalizeTrackingPayload(string $tracking, array $payload): array
    {
        $data = $payload['data'] ?? [];
        $proof = is_array($data['delivery_proof'] ?? null)
            ? $data['delivery_proof']
            : [];

        $photos = collect($proof['photos'] ?? [])
            ->map(function ($photo) {
                if (is_string($photo)) {
                    return [
                        'url' => $photo,
                        'preview_url' => $photo,
                    ];
                }

                return [
                    'url' => $photo['url'] ?? null,
                    'preview_url' => $photo['preview_url'] ?? ($photo['url'] ?? null),
                ];
            })
            ->filter(fn ($photo) => !empty($photo['url']))
            ->values()
            ->all();

        return [
            'tracking' => $tracking,
            'delivery_state' => $data['delivery_state'] ?? $data['status'] ?? null,
            'delivered_at' =>
                $data['delivery_date']
                ?? $proof['created_at']
                ?? $proof['delivered_at']
                ?? $proof['date']
                ?? $proof['datetime']
                ?? $data['delivered_at']
                ?? $data['updated_at']
                ?? null,
            'received_by' =>
                $proof['recipient_name']
                ?? $proof['received_by']
                ?? $proof['receiver_name']
                ?? $data['received_by']
                ?? null,
            'photos' => $photos,
            'has_pod' => count($photos) > 0,
        ];
    }

    private function getTrackingConfig(): array
    {
        $baseUrl = rtrim((string) config('services.tracking.base_url'), '/');
        $token = (string) config('services.tracking.token');
        $timeout = (int) config('services.tracking.timeout', 15);

        return [
            'base_url' => $baseUrl,
            'token' => $token,
            'timeout' => $timeout > 0 ? $timeout : 15,
            'configured' => $baseUrl !== '' && $token !== '',
        ];
    }
}