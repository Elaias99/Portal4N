<?php

namespace App\Services\Courier\Geo;

use Illuminate\Support\Facades\Log;
use Throwable;

class GeoliceCaptureLog
{
    /** @param array<string, mixed> $context */
    public static function write(string $event, array $context = [], string $level = 'info'): void
    {
        try {
            Log::channel('courier_geo')->log($level, 'Geo captura: '.$event, $context);
        } catch (Throwable) {
            error_log('Geo captura: no se pudo escribir el archivo de diagnóstico. Revisa la configuración y los permisos de storage/logs.');
        }
    }
}
