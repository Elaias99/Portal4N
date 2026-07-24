<?php

namespace App\Services\Suscripciones;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SuscripcionOneDriveService
{
    private string $tenantId;
    private string $clientId;
    private string $clientSecret;
    private string $driveId;
    private string $folderId;

    public function __construct()
    {
        $this->tenantId = (string) config('services.onedrive.tenant_id');
        $this->clientId = (string) config('services.onedrive.client_id');
        $this->clientSecret = (string) config('services.onedrive.client_secret');
        $this->driveId = (string) config('services.onedrive.drive_id');
        $this->folderId = (string) config('services.onedrive.folder_id');

        $this->validarConfiguracion();
    }

    /**
     * Sube un ZIP a la carpeta de OneDrive configurada.
     *
     * @return array{
     *     id: string|null,
     *     name: string|null,
     *     size: int,
     *     web_url: string|null
     * }
     */
    public function subirZip(string $zipPath, string $zipFileName): array
    {
        if (!is_file($zipPath)) {
            throw new RuntimeException(
                'No se encontró el archivo ZIP que se intentará subir a OneDrive.'
            );
        }

        $tamano = filesize($zipPath);

        if ($tamano === false || $tamano <= 0) {
            throw new RuntimeException(
                'El archivo ZIP está vacío o no se pudo determinar su tamaño.'
            );
        }

        /*
         * La carga directa de Microsoft Graph admite archivos
         * de hasta 250 MB.
         */
        $limiteCargaDirecta = 250 * 1024 * 1024;

        if ($tamano > $limiteCargaDirecta) {
            throw new RuntimeException(
                'El ZIP supera los 250 MB. Debe utilizarse una sesión de carga por fragmentos.'
            );
        }

        $token = $this->obtenerAccessToken();

        $nombreCodificado = rawurlencode($zipFileName);

        $url = sprintf(
            'https://graph.microsoft.com/v1.0/drives/%s/items/%s:/%s:/content',
            $this->driveId,
            $this->folderId,
            $nombreCodificado
        );

        $stream = fopen($zipPath, 'rb');

        if ($stream === false) {
            throw new RuntimeException(
                'No se pudo abrir el archivo ZIP para enviarlo a OneDrive.'
            );
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->withHeaders([
                    'Content-Type' => 'application/zip',
                ])
                ->timeout(300)
                ->connectTimeout(30)
                ->send('PUT', $url, [
                    'body' => $stream,
                ]);
        } finally {
            fclose($stream);
        }

        if (!$response->successful()) {
            throw new RuntimeException(
                'Microsoft Graph rechazó la carga del ZIP. '
                . 'HTTP ' . $response->status()
                . ': ' . $response->body()
            );
        }

        $archivo = $response->json();

        return [
            'id' => $archivo['id'] ?? null,
            'name' => $archivo['name'] ?? null,
            'size' => (int) ($archivo['size'] ?? $tamano),
            'web_url' => $archivo['webUrl'] ?? null,
        ];
    }

    private function obtenerAccessToken(): string
    {
        $cacheKey = 'microsoft_onedrive_access_token_' . sha1($this->clientId);

        return Cache::remember($cacheKey, now()->addMinutes(50), function () {
            $url = sprintf(
                'https://login.microsoftonline.com/%s/oauth2/v2.0/token',
                $this->tenantId
            );

            $response = Http::asForm()
                ->acceptJson()
                ->timeout(30)
                ->post($url, [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope' => 'https://graph.microsoft.com/.default',
                    'grant_type' => 'client_credentials',
                ]);

            if (!$response->successful()) {
                throw new RuntimeException(
                    'No se pudo obtener el token de Microsoft Graph. '
                    . 'HTTP ' . $response->status()
                    . ': ' . $response->body()
                );
            }

            $token = $response->json('access_token');

            if (!is_string($token) || $token === '') {
                throw new RuntimeException(
                    'Microsoft no devolvió un access token válido.'
                );
            }

            return $token;
        });
    }

    private function validarConfiguracion(): void
    {
        $configuracion = [
            'MICROSOFT_ONEDRIVE_TENANT_ID' => $this->tenantId,
            'MICROSOFT_ONEDRIVE_CLIENT_ID' => $this->clientId,
            'MICROSOFT_ONEDRIVE_CLIENT_SECRET' => $this->clientSecret,
            'MICROSOFT_ONEDRIVE_DRIVE_ID' => $this->driveId,
            'MICROSOFT_ONEDRIVE_FOLDER_ID' => $this->folderId,
        ];

        $faltantes = array_keys(array_filter(
            $configuracion,
            fn (string $valor) => trim($valor) === ''
        ));

        if ($faltantes !== []) {
            throw new RuntimeException(
                'Faltan variables de configuración de OneDrive: '
                . implode(', ', $faltantes)
            );
        }
    }
}