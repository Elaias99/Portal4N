<?php

namespace App\Services\LatamTracking;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LatamTrackingClient
{
    private const BASE_URL = 'https://www.latamcargo.com/es/trackshipment';
    private const API_URL = 'https://www.latamcargo.com/es/doTrackShipmentsAction';
    private const DEFAULT_DOC_TYPE = 'SO';

    public function fetchTrackingFragment(
        string $prefix,
        string $code,
        string $docType = self::DEFAULT_DOC_TYPE
    ): string {
        $cookieJar = new CookieJar();

        $headersGet = $this->buildHeadersGet();
        $headersPost = $this->buildHeadersPost($prefix, $code, $docType);
        $payload = $this->buildPayload($prefix, $code, $docType);

        $meta = [
            'prefix' => $prefix,
            'code' => $code,
            'doc_type' => $docType,
        ];

        try {
            $t0 = microtime(true);

            $baseResponse = Http::withOptions([
                    'cookies' => $cookieJar,
                ])
                ->withHeaders($headersGet)
                ->connectTimeout(4)
                ->timeout(8)
                ->retry(2, 800, throw: false)
                ->get(self::BASE_URL);

            Log::info('LATAM base GET', $meta + [
                'status' => $baseResponse->status(),
                'ok' => $baseResponse->successful(),
                'ms' => (int) ((microtime(true) - $t0) * 1000),
                'body_length' => strlen($baseResponse->body() ?? ''),
            ]);

            $baseResponse->throw();

            $t1 = microtime(true);

            $trackResponse = Http::withOptions([
                    'cookies' => $cookieJar,
                ])
                ->withHeaders($headersPost)
                ->connectTimeout(4)
                ->timeout(12)
                ->retry(2, 1000, throw: false)
                ->asJson()
                ->post(self::API_URL, $payload);

            Log::info('LATAM track POST', $meta + [
                'status' => $trackResponse->status(),
                'ok' => $trackResponse->successful(),
                'ms' => (int) ((microtime(true) - $t1) * 1000),
                'body_length' => strlen($trackResponse->body() ?? ''),
            ]);

            $trackResponse->throw();

            $html = $trackResponse->body();

            if (trim($html) === '') {
                Log::warning('LATAM empty fragment', $meta);
                throw new \RuntimeException('LATAM devolvió una respuesta vacía.');
            }

            return $html;
        } catch (\Throwable $e) {
            Log::warning('LATAM tracking failure', $meta + [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function buildHeadersGet(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0',
            'Accept-Language' => 'es-ES,es;q=0.9,en;q=0.8',
        ];
    }

    private function buildHeadersPost(string $prefix, string $code, string $docType): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0',
            'Accept' => '*/*',
            'Content-Type' => 'application/json',
            'Origin' => 'https://www.latamcargo.com',
            'Referer' => self::BASE_URL
                . '?docNumber=' . urlencode($code)
                . '&docPrefix=' . urlencode($prefix)
                . '&soType=' . urlencode($docType),
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept-Language' => 'es-ES,es;q=0.9,en;q=0.8',
        ];
    }

    private function buildPayload(string $prefix, string $code, string $docType): array
    {
        return [
            'cargoTrackingRequestSOs' => [
                [
                    'documentPrefix' => $prefix,
                    'documentNumber' => $code,
                    'documentType' => $docType,
                ],
            ],
        ];
    }
}