<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class BsaleGuiaService
{
    public function generar(array $datos): array
    {
        $token = config('services.bsale.production_token');

        if (blank($token)) {
            return [
                'estado' => 'error',
                'mensaje' => 'No se encontró el token de producción.',
            ];
        }

        // Configuración que ya utilizamos en la prueba de producción.
        $datos['documentTypeId'] = 7;
        $datos['declareSii'] = 0;
        $datos['sendEmail'] = 0;

        try {
            $respuesta = Http::withHeaders([
                'access_token' => $token,
            ])
                ->acceptJson()
                ->connectTimeout(10)
                ->timeout(60)
                ->post(
                    'https://api.bsale.io/v1/shippings.json',
                    $datos
                );
        } catch (ConnectionException $exception) {
            return [
                'estado' => 'incierta',
                'mensaje' => 'No se recibió una respuesta completa. '
                    . 'Comprueba en Bsale si la guía fue creada antes '
                    . 'de intentar otro envío.',
            ];
        }

        $contenido = $respuesta->json();

        if (! $respuesta->successful()) {
            return [
                'estado' => 'incierta',
                'estado_http' => $respuesta->status(),
                'mensaje' => 'Bsale no confirmó la creación. '
                    . 'Revisa la respuesta antes de repetir el envío.',
                'respuesta' => $contenido ?? $respuesta->body(),
            ];
        }

        $guia = is_array($contenido)
            ? ($contenido['guide'] ?? [])
            : [];

        if (empty($guia['id'])) {
            return [
                'estado' => 'incierta',
                'estado_http' => $respuesta->status(),
                'mensaje' => 'Bsale respondió, pero no devolvió el '
                    . 'identificador de la guía. Revisa antes de reenviar.',
                'respuesta' => $contenido ?? $respuesta->body(),
            ];
        }



        return [
            'estado' => 'generada',
            'estado_http' => $respuesta->status(),
            'shipping_id' => $contenido['id'] ?? null,
            'document_id' => $guia['id'],
            'numero' => $guia['number'] ?? null,
            'pdf' => $guia['urlPdf'] ?? null,
            'vista' => $guia['urlPublicView'] ?? null,
            'respuesta' => $contenido,
        ];




    }
}