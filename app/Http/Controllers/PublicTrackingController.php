<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class PublicTrackingController extends Controller
{
    public function show(string $tracking)
    {
        return view('tracking-public', [
            'tracking' => trim($tracking),
        ]);
    }

    public function data(string $tracking): JsonResponse
    {
        $tracking = trim($tracking);

        if ($tracking === '' || mb_strlen($tracking) > 100) {
            return response()->json([
                'success' => false,
                'message' => 'Número de seguimiento inválido.',
            ], 422);
        }

        try {
            $response = Http::acceptJson()
                ->timeout(20)
                ->get('https://4nlogistica.cl/tracking-proxy.php', [
                    'tracking' => $tracking,
                ]);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No fue posible consultar el tracking en este momento.',
                ], 502);
            }

            $json = $response->json();

            if (!is_array($json) || empty($json['success'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tracking no encontrado.',
                ], 404);
            }



            return response()->json([
                'success' => true,
                'data' => $this->transformProxyResponse($tracking, $json),
            ]);


            







        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al consultar el tracking.',
            ], 500);
        }
    }

    private function transformProxyResponse(string $tracking, array $payload): array
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


        $deliveredTimeline = collect($data['timeline'] ?? [])
            ->firstWhere('state', 'delivered');

        return [
            'tracking' => $tracking,
            'status' => $data['delivery_state'] ?? $data['status'] ?? null,
            'delivered_at' =>
                $data['delivery_date']
                ?? $proof['created_at']
                ?? ($deliveredTimeline['timestamp'] ?? null)
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
}