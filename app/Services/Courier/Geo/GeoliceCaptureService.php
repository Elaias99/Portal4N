<?php

namespace App\Services\Courier\Geo;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use Illuminate\Support\Facades\Http;
use Throwable;

class GeoliceCaptureService
{
    /** @var array{capture_id: string, user_id: int} */
    private array $logContext = [];

    private string $stage = 'inicio';

    private float $startedAt = 0;

    private bool $browserStarted = false;

    public function __construct(
        private readonly GeoliceCaptureStore $store,
        private readonly GeoliceBrowser $browser,
        private readonly GeolicePackageSummary $summary,
    ) {}

    public function capture(int $userId, string $id): void
    {
        $this->logContext = ['capture_id' => $id, 'user_id' => $userId];
        $this->stage = 'inicio';
        $this->startedAt = microtime(true);
        $this->browserStarted = false;
        $capture = $this->store->claim($userId, $id);
        if ($capture === null) {
            $this->record('solicitud_omitida');
            return;
        }

        $this->record('captura_iniciada', ['from' => $capture['from'], 'to' => $capture['to'], 'payment_period' => $capture['payment_period']]);

        $accountLock = null;
        $temporary = $this->store->filePath($userId, $id, true);
        try {
            $this->stage = 'leer_cuenta';
            $account = $this->store->account($userId)
                ?? throw new GeoliceCaptureException('Conecta nuevamente tu cuenta de Geo antes de solicitar los paquetes.');
            $this->record('cuenta_recuperada', ['saved_cookie_count' => count($account['cookies'])]);
            $this->stage = 'bloquear_cuenta';
            $accountLock = $this->store->lockAccount($account['email']);
            $this->stage = 'iniciar_navegador';
            $this->record('navegador_iniciando');
            $this->browser->start();
            $this->browserStarted = true;
            $this->record('navegador_iniciado');
            $baseUrl = rtrim(config('courier_geo.base_url'), '/');
            $this->stage = 'abrir_ordenes';
            $this->browser->navigate($baseUrl.'/orders');
            $this->recordBrowser('pagina_inicial');
            if ($account['cookies'] !== []) {
                $this->stage = 'restaurar_sesion';
                $this->browser->restoreCookies($account['cookies']);
                $this->record('sesion_restaurada', ['cookie_counts' => $this->browser->restoredCookieCounts()]);
                $this->browser->navigate($baseUrl.'/orders');
                $this->recordBrowser('pagina_con_sesion_restaurada');
            }
            $ordersRetried = $this->retryOrdersFromHome($baseUrl);
            $this->stage = 'detectar_acceso';
            $loginDetected = $this->isLogin();
            $this->record('acceso_detectado', ['login_url_detected' => $loginDetected]);
            if ($loginDetected) {
                $this->stage = 'iniciar_sesion';
                $this->recordBrowser('login_antes_de_enviar');
                $loggedIn = $this->script('login', ['email' => $account['email'], 'password' => $account['password']]);
                $this->record('login_formulario_programado', ['form_found' => (bool) $loggedIn]);
                if (! $loggedIn || ! $this->waitForLogin()) {
                    throw new GeoliceCaptureException('No se pudo iniciar sesión en Geo. Revisa el usuario y la contraseña. Si Geo exige una verificación adicional, esta captura no puede continuar automáticamente.');
                }
                $ordersRetried = false;
                $this->stage = 'abrir_ordenes_tras_acceso';
                $this->browser->navigate($baseUrl.'/orders');
                $this->recordBrowser('pagina_tras_acceso');
            }
            $this->stage = 'esperar_ordenes';
            $this->waitForOrders($baseUrl, $ordersRetried);
            $this->stage = 'guardar_sesion';
            $cookies = $this->browser->cookies();
            $this->store->saveCookies($userId, $cookies);
            $this->record('sesion_guardada', ['saved_cookie_count' => count($cookies)]);
            $this->stage = 'cargar_notificaciones';
            $baseline = $this->script('observe');
            $this->record('notificaciones_iniciales', [
                'notifications_loaded' => (bool) ($baseline['notificationsLoaded'] ?? false),
                'previous_export_count' => count($baseline['links'] ?? []),
            ]);
            if (! ($baseline['notificationsLoaded'] ?? false)) {
                throw new GeoliceCaptureException('Geo no cargó las notificaciones. No se solicitó ninguna exportación.');
            }
            $previousExports = array_keys($baseline['links']);
            $this->store->update($userId, $id, ['state' => 'preparando', 'message' => 'Solicitando los paquetes por fecha de creación…']);
            $this->stage = 'solicitar_exportacion';
            $this->record('exportacion_solicitando');
            $started = $this->script('export', ['from' => $capture['from'], 'to' => $capture['to']]);
            $this->record('exportacion_respuesta', [
                'started' => (bool) ($started['started'] ?? false),
                'submitted' => (bool) ($started['submitted'] ?? false),
                'validation_error_count' => $started['validationErrorCount'] ?? 0,
                'response_observed' => (bool) ($started['responseObserved'] ?? false),
                'response_http_status' => $started['responseHttpStatus'] ?? null,
                'observer_error_count' => $started['observerErrorCount'] ?? 0,
                'expected_rows' => $started['expectedRows'] ?? null,
                'export_id' => $started['exportId'] ?? null,
            ]);
            if (($started['validationErrorCount'] ?? 0) > 0) {
                throw new GeoliceCaptureException('Geo mostró errores de validación en el formulario de exportación. Revisa las fechas; Geo permite como máximo dos meses por solicitud.');
            }
            if (($started['responseHttpStatus'] ?? 200) >= 400) {
                throw new GeoliceCaptureException('Geo devolvió un error al solicitar la exportación. Revisa sus notificaciones antes de volver a solicitarla.');
            }
            if (! ($started['submitted'] ?? false)) {
                throw new GeoliceCaptureException('No se completó el envío de la solicitud de exportación a Geo.');
            }
            if (! ($started['started'] ?? false)) {
                $this->record('exportacion_enviada_sin_texto_de_inicio', [], 'warning');
            }
            $expectedRows = $started['expectedRows'] ?? null;
            $this->store->update($userId, $id, [
                'state' => 'esperando',
                'expected_rows' => $expectedRows,
                'message' => ($started['started'] ?? false)
                    ? 'Geo está preparando el archivo. Puede tardar varios minutos.'
                    : 'Solicitud enviada a Geo. Esperando la confirmación o el archivo de esta exportación.',
            ]);
            $this->stage = 'esperar_exportacion';
            [$exportId, $link] = $this->waitForExport($userId, $id, $previousExports, $started['exportId'] ?? null);
            $this->record('exportacion_disponible', ['export_id' => $exportId]);
            $this->store->update($userId, $id, ['state' => 'descargando', 'export_id' => $exportId, 'message' => 'El archivo está listo. Descargando los paquetes…']);
            $this->stage = 'descargar_archivo';
            $this->record('descarga_iniciada');
            $this->download($baseUrl, $link, $temporary);
            $this->record('descarga_completada', ['file_bytes' => filesize($temporary)]);
            $final = $this->store->filePath($userId, $id);
            if (! rename($temporary, $final)) {
                throw new GeoliceCaptureException('No se pudo guardar el archivo descargado.');
            }
            $fileName = 'geolice_'.$capture['from'].'_'.$capture['to'].'_creacion_export'.$exportId.'.csv';
            $this->store->update($userId, $id, ['state' => 'leyendo', 'file_name' => $fileName, 'message' => 'Archivo guardado. Preparando el resumen de paquetes…']);
            $this->stage = 'leer_archivo';
            $result = $this->summary->read($final);
            $this->record('archivo_leido', ['rows' => $result['rows'], 'packages' => $result['packages'], 'expected_rows' => $expectedRows]);
            if ($expectedRows !== null && $result['rows'] !== (int) $expectedRows) {
                throw new GeoliceCaptureException('Las filas del archivo no coinciden con las anunciadas por esta exportación. El archivo se conservó, pero no se muestra como una captura confirmada.');
            }
            $this->store->update($userId, $id, [
                'state' => 'listo',
                'message' => 'Captura completada.',
                'summary' => $result,
                'file_bytes' => filesize($final),
                'finished_at' => now()->toIso8601String(),
            ]);
            $this->stage = 'listo';
            $this->record('captura_completada');
        } catch (Throwable $exception) {
            $message = $exception instanceof GeoliceCaptureException ? $exception->getMessage() : 'La captura se interrumpió. El archivo obtenido, si existe, se conserva para su revisión.';
            $this->recordBrowser('captura_error', [
                'message' => $message,
                'exception_type' => $exception::class,
                'exception_file' => basename($exception->getFile()),
                'exception_line' => $exception->getLine(),
            ], 'error');
            $this->store->update($userId, $id, ['state' => 'error', 'message' => $message, 'finished_at' => now()->toIso8601String()]);
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
            $this->browser->close();
            $this->browserStarted = false;
            if (is_resource($accountLock)) {
                flock($accountLock, LOCK_UN);
                fclose($accountLock);
            }
            $this->record('recursos_liberados');
        }
    }

    /** @param array<string, mixed> $details */
    private function record(string $event, array $details = [], string $level = 'info'): void
    {
        GeoliceCaptureLog::write($event, [
            ...$this->logContext,
            'stage' => $this->stage,
            'elapsed_seconds' => round(microtime(true) - $this->startedAt, 2),
            ...$details,
        ], $level);
    }

    /** @param array<string, mixed> $details */
    private function recordBrowser(string $event, array $details = [], string $level = 'info'): void
    {
        $details['last_browser_failure'] = $this->browser->lastFailure();
        if ($this->browserStarted) {
            try {
                $details['browser'] = $this->script('diagnostics');
            } catch (Throwable $exception) {
                $details['diagnostics_available'] = false;
                $details['diagnostics_exception_type'] = $exception::class;
            }
        } else {
            $details['browser_started'] = false;
        }
        $this->record($event, $details, $level);
    }

    /** @param array<string, mixed> $input */
    private function script(string $action, array $input = []): mixed
    {
        try {
            return $this->browser->executeAsync(file_get_contents(resource_path('js/geolice-capture.js')), [$action, $input]);
        } catch (Throwable $exception) {
            $this->record('script_error', ['action' => $action, 'exception_type' => $exception::class, 'browser_failure' => $this->browser->lastFailure()], 'warning');
            throw $exception;
        }
    }

    private function isLogin(): bool
    {
        return str_contains((string) parse_url($this->browser->url(), PHP_URL_PATH), '/login');
    }

    private function waitForLogin(): bool
    {
        $deadline = microtime(true) + 40;
        $lastDiagnostic = 0.0;
        do {
            usleep(500000);
            if (! $this->isLogin()) {
                $this->recordBrowser('login_cambio_de_pagina');
                return true;
            }
            if (microtime(true) - $lastDiagnostic >= 10) {
                $this->recordBrowser('login_esperando');
                $lastDiagnostic = microtime(true);
            }
        } while (microtime(true) < $deadline);

        $this->recordBrowser('login_tiempo_agotado', [], 'warning');
        return false;
    }

    private function retryOrdersFromHome(string $baseUrl): bool
    {
        $path = rtrim((string) parse_url($this->browser->url(), PHP_URL_PATH), '/');
        if ($path !== '/home') {
            return false;
        }

        $this->stage = 'reintentar_ordenes';
        $this->recordBrowser('ordenes_reintento_desde_home');
        $this->browser->navigate($baseUrl.'/orders');
        $this->recordBrowser('ordenes_tras_reintento');

        return true;
    }

    private function waitForOrders(string $baseUrl, bool $ordersRetried): void
    {
        $deadline = microtime(true) + 60;
        $lastDiagnostic = 0.0;
        $redirectPath = null;
        $redirectSince = null;
        do {
            $path = rtrim((string) parse_url($this->browser->url(), PHP_URL_PATH), '/');
            if ($path === '/orders' && $this->script('ready')) {
                $this->recordBrowser('ordenes_detectadas');
                return;
            }
            $redirected = $path === '/home' || str_contains($path, '/login');
            if ($redirected) {
                if ($redirectPath !== $path) {
                    $redirectPath = $path;
                    $redirectSince = microtime(true);
                }
                if (microtime(true) - $redirectSince >= 3) {
                    if ($path === '/home' && ! $ordersRetried) {
                        $ordersRetried = $this->retryOrdersFromHome($baseUrl);
                        $this->stage = 'esperar_ordenes';
                        $redirectPath = null;
                        $redirectSince = null;

                        continue;
                    }
                    if ($path === '/home') {
                        $this->recordBrowser('ordenes_redirigidas_a_home', [], 'warning');
                        throw new GeoliceCaptureException('Geo volvió a Home después de reintentar el acceso a Órdenes. No se solicitó la exportación. Revisa el registro de esta captura para identificar la redirección.');
                    }
                    $this->recordBrowser('ordenes_redirigidas_al_acceso', [], 'warning');
                    throw new GeoliceCaptureException('Geo volvió a la pantalla de acceso al abrir Órdenes. No se solicitó la exportación. Desconecta la cuenta y vuelve a guardarla para probar con una sesión nueva.');
                }
            } else {
                $redirectPath = null;
                $redirectSince = null;
            }
            if (microtime(true) - $lastDiagnostic >= 10) {
                $this->recordBrowser('ordenes_esperando');
                $lastDiagnostic = microtime(true);
            }
            usleep(500000);
        } while (microtime(true) < $deadline);

        $this->recordBrowser('ordenes_tiempo_agotado', [], 'warning');
        throw new GeoliceCaptureException('Geo no cargó la pantalla de órdenes con esta cuenta. No se solicitó la exportación.');
    }

    /**
     * @param list<int|string> $previousExports
     * @return array{string, string}
     */
    private function waitForExport(int $userId, string $id, array $previousExports, ?string $expectedExport): array
    {
        $deadline = microtime(true) + (int) config('courier_geo.export_timeout');
        $lastDiagnostic = microtime(true);
        do {
            $state = $this->script('poll');
            $newLinks = array_diff_key($state['links'], array_flip($previousExports));
            if ($expectedExport !== null && isset($newLinks[$expectedExport])) {
                return [$expectedExport, $newLinks[$expectedExport]];
            }
            if ($expectedExport === null && count($newLinks) > 1) {
                throw new GeoliceCaptureException('Geo notificó varias exportaciones nuevas. No se eligió un archivo para evitar mezclar solicitudes. Revisa las notificaciones de Geo.');
            }
            if ($expectedExport === null && count($newLinks) === 1) {
                $exportId = (string) array_key_first($newLinks);

                return [$exportId, $newLinks[$exportId]];
            }
            $this->store->update($userId, $id, ['message' => 'Geo sigue preparando el archivo. Esta pantalla se actualiza automáticamente.']);
            if (microtime(true) - $lastDiagnostic >= 60) {
                $this->record('exportacion_esperando', ['new_export_count' => count($newLinks), 'expected_export_id' => $expectedExport]);
                $lastDiagnostic = microtime(true);
            }
            sleep(5);
        } while (microtime(true) < $deadline);

        throw new GeoliceCaptureException('Geo no terminó la exportación en 30 minutos. Revisa sus notificaciones antes de volver a solicitarla; puede seguir preparándola.');
    }

    private function download(string $baseUrl, string $link, string $destination): void
    {
        if (! preg_match('#^/filament/exports/[0-9]+/download\?#', $link)) {
            throw new GeoliceCaptureException('Geo no entregó un enlace de descarga válido.');
        }
        $jar = new CookieJar;
        foreach ($this->browser->cookies() as $cookie) {
            $jar->setCookie(new SetCookie([
                'Name' => $cookie['name'], 'Value' => $cookie['value'],
                'Domain' => $cookie['domain'], 'Path' => $cookie['path'],
                'Secure' => $cookie['secure'] ?? true,
                'Expires' => $cookie['expiry'] ?? null,
            ]));
        }
        $maxBytes = (int) config('courier_geo.max_file_bytes');
        $sink = @fopen($destination, 'w+b');
        if ($sink === false) {
            throw new GeoliceCaptureException('No se pudo preparar el archivo para descargar los paquetes.');
        }
        $response = null;
        try {
            $response = Http::connectTimeout(30)->timeout((int) config('courier_geo.download_timeout'))
                ->withUserAgent((string) $this->browser->execute('return navigator.userAgent;'))
                ->withOptions([
                    'cookies' => $jar,
                    'sink' => $sink,
                    'allow_redirects' => ['max' => 5, 'protocols' => ['https']],
                    'progress' => function (float $total, float $downloaded) use ($maxBytes): void {
                        if ($total > $maxBytes || $downloaded > $maxBytes) {
                            throw new GeoliceCaptureException('El archivo supera los 100 MB permitidos para esta captura. Solicita un rango de fechas menor.');
                        }
                    },
                ])->get($baseUrl.$link);
        } catch (GeoliceCaptureException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $this->record('descarga_error_transporte', ['exception_type' => $exception::class], 'warning');
            throw new GeoliceCaptureException('No se completó la descarga. El archivo puede seguir disponible en las notificaciones de Geo. También puedes solicitar un rango de fechas menor.');
        } finally {
            // Release the HTTP file handle before validating or renaming on Windows mounts.
            try {
                $response?->close();
            } finally {
                if (is_resource($sink)) {
                    fclose($sink);
                }
            }
        }
        $this->record('descarga_respuesta_http', ['http_status' => $response->status()]);
        if (! $response->successful()) {
            throw new GeoliceCaptureException(in_array($response->status(), [502, 503, 504], true)
                ? 'Geo tardó demasiado en entregar el archivo. La exportación sigue en sus notificaciones; vuelve a solicitarla más tarde.'
                : 'Geo no permitió descargar el archivo. Revisa la conexión de tu cuenta antes de volver a solicitarlo.');
        }
        $file = @fopen($destination, 'rb');
        $firstLine = $file === false ? false : fgets($file, 8192);
        if ($file !== false) {
            fclose($file);
        }
        if ($firstLine === false || ! str_contains($firstLine, 'Seguimiento paquete')) {
            $this->record('descarga_csv_sin_cabecera', [], 'warning');
            throw new GeoliceCaptureException('Geo devolvió un archivo que no corresponde a la base de paquetes.');
        }
    }
}
