<?php

namespace App\Services\Courier\Geo;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/** Control del navegador mediante el protocolo WebDriver; no ejecuta Python. */
class GeoliceBrowser
{
    private ?string $sessionId = null;

    /** @var array<string, mixed>|null */
    private ?array $lastFailure = null;

    /** @var array{restored: int, expired: int, rejected: int} */
    private array $restoredCookies = ['restored' => 0, 'expired' => 0, 'rejected' => 0];

    public function start(): void
    {
        $this->lastFailure = null;
        $this->restoredCookies = ['restored' => 0, 'expired' => 0, 'rejected' => 0];
        $value = $this->command('POST', '/session', [
            'capabilities' => ['alwaysMatch' => [
                'browserName' => 'chrome',
                'goog:chromeOptions' => [
                    'binary' => config('courier_geo.chromium_binary'),
                    'args' => ['--headless=new', '--no-sandbox', '--disable-dev-shm-usage', '--window-size=1440,900'],
                ],
            ]],
        ]);
        $this->sessionId = $value['sessionId'] ?? null;
        if ($this->sessionId === null) {
            throw new GeoliceCaptureException('No se pudo iniciar el navegador de captura.');
        }
        $this->command('POST', $this->sessionPath().'/timeouts', ['script' => 180000, 'pageLoad' => 60000, 'implicit' => 0]);
    }

    public function navigate(string $url): void
    {
        $this->command('POST', $this->sessionPath().'/url', ['url' => $url]);
    }

    public function url(): string
    {
        return (string) $this->command('GET', $this->sessionPath().'/url');
    }

    /** @param list<mixed> $arguments */
    public function execute(string $script, array $arguments = []): mixed
    {
        return $this->command('POST', $this->sessionPath().'/execute/sync', ['script' => $script, 'args' => $arguments]);
    }

    /** @param list<mixed> $arguments */
    public function executeAsync(string $script, array $arguments = []): mixed
    {
        $value = $this->command('POST', $this->sessionPath().'/execute/async', ['script' => $script, 'args' => $arguments]);
        if (! is_array($value) || ! ($value['ok'] ?? false)) {
            $action = $arguments[0] ?? null;
            $errorName = is_array($value) ? ($value['error_name'] ?? null) : null;
            $this->lastFailure = [
                'kind' => 'script',
                'action' => in_array($action, ['ready', 'diagnostics', 'login', 'observe', 'export', 'poll'], true) ? $action : null,
                'error_name' => in_array($errorName, ['Error', 'TypeError', 'ReferenceError', 'SyntaxError', 'RangeError', 'URIError', 'EvalError'], true) ? $errorName : 'UnknownError',
            ];
            throw new GeoliceCaptureException('Geo no respondió a la acción solicitada.');
        }

        return $value['value'] ?? null;
    }

    /** @return list<array<string, mixed>> */
    public function cookies(): array
    {
        return $this->command('GET', $this->sessionPath().'/cookie');
    }

    /** @param list<array<string, mixed>> $cookies */
    public function restoreCookies(array $cookies): void
    {
        $this->restoredCookies = ['restored' => 0, 'expired' => 0, 'rejected' => 0];
        foreach ($cookies as $cookie) {
            if (isset($cookie['expiry']) && $cookie['expiry'] < time()) {
                $this->restoredCookies['expired']++;
                continue;
            }
            try {
                $this->command('POST', $this->sessionPath().'/cookie', ['cookie' => $cookie]);
                $this->restoredCookies['restored']++;
            } catch (RuntimeException) {
                $this->restoredCookies['rejected']++;
                continue;
            }
        }
    }

    /** @return array{restored: int, expired: int, rejected: int} */
    public function restoredCookieCounts(): array
    {
        return $this->restoredCookies;
    }

    /** @return array<string, mixed>|null */
    public function lastFailure(): ?array
    {
        return $this->lastFailure;
    }

    public function close(): void
    {
        if ($this->sessionId !== null) {
            try {
                $this->command('DELETE', $this->sessionPath());
            } catch (RuntimeException) {
                // La captura conserva su resultado aunque el navegador ya se haya cerrado.
            } finally {
                $this->sessionId = null;
            }
        }
    }

    /** @param array<string, mixed> $payload */
    private function command(string $method, string $path, array $payload = []): mixed
    {
        $command = ['method' => $method, 'endpoint' => preg_replace('#/session/[^/]+#', '/session/{id}', $path)];
        try {
            $response = Http::acceptJson()->connectTimeout(10)->timeout(240)
                ->send($method, rtrim(config('courier_geo.webdriver_url'), '/').$path, $payload === [] ? [] : ['json' => $payload]);
            $data = $response->json();
        } catch (Throwable $exception) {
            $this->lastFailure = [...$command, 'kind' => 'transport', 'exception_type' => $exception::class];
            throw new GeoliceCaptureException('No se pudo comunicar con el navegador de captura. Comprueba que el servicio de Portal4N esté iniciado con la nueva configuración.');
        }
        if (! $response->successful() || ! is_array($data) || isset($data['value']['error'])) {
            $error = is_array($data) ? ($data['value']['error'] ?? null) : null;
            $this->lastFailure = [
                ...$command,
                'kind' => 'webdriver',
                'http_status' => $response->status(),
                'error_code' => is_string($error) && preg_match('/^[a-z ]{1,80}$/', $error) ? $error : null,
            ];
            throw new GeoliceCaptureException('El navegador no pudo completar la acción de Geo. La captura se detuvo.');
        }

        return $data['value'] ?? null;
    }

    private function sessionPath(): string
    {
        if ($this->sessionId === null) {
            throw new GeoliceCaptureException('El navegador de captura no está iniciado.');
        }

        return '/session/'.$this->sessionId;
    }
}
