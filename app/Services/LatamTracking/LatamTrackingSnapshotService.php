<?php

namespace App\Services\LatamTracking;

use App\Models\TrackingAlmacenado;
use App\Models\TrackingConsulta;
use App\Models\TrackingEstadoActual;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Throwable;

class LatamTrackingSnapshotService
{
    private const DEFAULT_DOC_TYPE = 'SO';

    public function __construct(
        private LatamTrackingClient $client,
        private LatamTrackingHtmlParser $htmlParser,
        private LatamTrackingStateBuilder $stateBuilder,
    ) {
    }

    public function consultarYPersistir(
        string $prefix,
        string $code,
        string $docType = self::DEFAULT_DOC_TYPE,
        ?TrackingAlmacenado $trackingAlmacenado = null,
    ): array {
        $docType = trim($docType) !== '' ? trim($docType) : self::DEFAULT_DOC_TYPE;

        $trackingAlmacenado = $trackingAlmacenado ?: $this->resolveTrackingAlmacenado($prefix, $code);
        $estadoActual = $trackingAlmacenado ? $this->ensureEstadoActualStub($trackingAlmacenado, $docType) : null;

        try {
            $html = $this->client->fetchTrackingFragment(
                prefix: $prefix,
                code: $code,
                docType: $docType,
            );

            $parsedResult = $this->htmlParser->parse(
                html: $html,
                prefix: $prefix,
                code: $code,
                docType: $docType,
            );

            $built = $this->stateBuilder->build($parsedResult, $html);

            if (!($parsedResult['ok'] ?? false)) {
                $trackingError = 'LATAM respondió, pero no devolvió datos utilizables para esa guía.';

                $consulta = $trackingAlmacenado
                    ? $this->registrarConsultaFallida(
                        trackingAlmacenado: $trackingAlmacenado,
                        estadoActual: $estadoActual,
                        httpStatus: 200,
                        html: $html,
                        docType: $docType,
                        errorCode: 'parser_no_tracking_data',
                        errorMessage: $trackingError,
                        parsedPayload: $parsedResult,
                    )
                    : null;

                return [
                    'ok' => false,
                    'trackingLookup' => [
                        'prefix' => $prefix,
                        'code' => $code,
                        'document_type' => $docType,
                    ],
                    'trackingResult' => $parsedResult,
                    'trackingError' => $trackingError,
                    'persisted' => $trackingAlmacenado !== null,
                    'trackingAlmacenado' => $trackingAlmacenado,
                    'estadoActual' => $estadoActual?->fresh(),
                    'consulta' => $consulta?->fresh(),
                    'cambioDetectado' => false,
                    'fallbackDisponible' => (bool) ($estadoActual?->tiene_estado_valido),
                ];
            }

            $cambioDetectado = false;
            $consulta = null;

            if ($trackingAlmacenado) {
                [$estadoActual, $consulta, $cambioDetectado] = $this->registrarConsultaExitosa(
                    trackingAlmacenado: $trackingAlmacenado,
                    estadoActual: $estadoActual,
                    built: $built,
                    html: $html,
                );
            }

            return [
                'ok' => true,
                'trackingLookup' => [
                    'prefix' => $prefix,
                    'code' => $code,
                    'document_type' => $docType,
                ],
                'trackingResult' => $parsedResult,
                'trackingError' => null,
                'persisted' => $trackingAlmacenado !== null,
                'trackingAlmacenado' => $trackingAlmacenado,
                'estadoActual' => $estadoActual?->fresh(),
                'consulta' => $consulta?->fresh(),
                'cambioDetectado' => $cambioDetectado,
                'fallbackDisponible' => false,
            ];
        } catch (ConnectionException $e) {
            $trackingError = 'LATAM no respondió a tiempo. Intenta nuevamente.';

            $consulta = $trackingAlmacenado
                ? $this->registrarConsultaFallida(
                    trackingAlmacenado: $trackingAlmacenado,
                    estadoActual: $estadoActual,
                    httpStatus: null,
                    html: null,
                    docType: $docType,
                    errorCode: 'latam_timeout',
                    errorMessage: $trackingError,
                    parsedPayload: null,
                )
                : null;

            return [
                'ok' => false,
                'trackingLookup' => [
                    'prefix' => $prefix,
                    'code' => $code,
                    'document_type' => $docType,
                ],
                'trackingResult' => null,
                'trackingError' => $trackingError,
                'persisted' => $trackingAlmacenado !== null,
                'trackingAlmacenado' => $trackingAlmacenado,
                'estadoActual' => $estadoActual?->fresh(),
                'consulta' => $consulta?->fresh(),
                'cambioDetectado' => false,
                'fallbackDisponible' => (bool) ($estadoActual?->tiene_estado_valido),
            ];
        } catch (RequestException $e) {
            $status = $e->response?->status();
            $body = $e->response?->body();

            $trackingError = in_array($status, [502, 503, 504], true)
                ? 'LATAM está temporalmente inestable. Intenta nuevamente.'
                : 'LATAM devolvió un error al consultar la guía.';

            $consulta = $trackingAlmacenado
                ? $this->registrarConsultaFallida(
                    trackingAlmacenado: $trackingAlmacenado,
                    estadoActual: $estadoActual,
                    httpStatus: $status,
                    html: $body,
                    docType: $docType,
                    errorCode: 'latam_http_error',
                    errorMessage: $trackingError,
                    parsedPayload: null,
                )
                : null;

            return [
                'ok' => false,
                'trackingLookup' => [
                    'prefix' => $prefix,
                    'code' => $code,
                    'document_type' => $docType,
                ],
                'trackingResult' => null,
                'trackingError' => $trackingError,
                'persisted' => $trackingAlmacenado !== null,
                'trackingAlmacenado' => $trackingAlmacenado,
                'estadoActual' => $estadoActual?->fresh(),
                'consulta' => $consulta?->fresh(),
                'cambioDetectado' => false,
                'fallbackDisponible' => (bool) ($estadoActual?->tiene_estado_valido),
            ];
    } catch (Throwable $e) {

        $trackingError = 'No fue posible consultar LATAM Cargo en este momento.';

        $consulta = $trackingAlmacenado
            ? $this->registrarConsultaFallida(
                trackingAlmacenado: $trackingAlmacenado,
                estadoActual: $estadoActual,
                httpStatus: null,
                html: null,
                docType: $docType,
                errorCode: 'unexpected_exception',
                errorMessage: $trackingError,
                parsedPayload: null,
            )
            : null;

        return [
            'ok' => false,
            'trackingLookup' => [
                'prefix' => $prefix,
                'code' => $code,
                'document_type' => $docType,
            ],
            'trackingResult' => null,
            'trackingError' => $trackingError,
            'persisted' => $trackingAlmacenado !== null,
            'trackingAlmacenado' => $trackingAlmacenado,
            'estadoActual' => $estadoActual?->fresh(),
            'consulta' => $consulta?->fresh(),
            'cambioDetectado' => false,
            'fallbackDisponible' => (bool) ($estadoActual?->tiene_estado_valido),
        ];
    }
    }

    private function resolveTrackingAlmacenado(string $prefix, string $code): ?TrackingAlmacenado
    {
        return TrackingAlmacenado::query()
            ->where('prefijo', $prefix)
            ->where('codigo_tracking', $code)
            ->latest('id')
            ->first();
    }

    private function ensureEstadoActualStub(
        TrackingAlmacenado $trackingAlmacenado,
        string $docType,
    ): TrackingEstadoActual {
        return TrackingEstadoActual::firstOrCreate(
            ['tracking_almacenado_id' => $trackingAlmacenado->id],
            [
                'document_type' => $docType,
                'tiene_estado_valido' => false,
            ]
        );
    }

    private function registrarConsultaExitosa(
        TrackingAlmacenado $trackingAlmacenado,
        TrackingEstadoActual $estadoActual,
        array $built,
        string $html,
    ): array {
        return DB::transaction(function () use ($trackingAlmacenado, $estadoActual, $built, $html) {
            $estadoPayload = $built['estado_actual'];
            $consultaPayload = $built['consulta'];

            $cambioDetectado = $this->detectarCambio($estadoActual, $estadoPayload);

            $consulta = TrackingConsulta::create([
                'tracking_almacenado_id' => $trackingAlmacenado->id,
                'document_type' => $consultaPayload['document_type'],
                'latam_http_status' => 200,
                'latam_respondio' => true,
                'html_recibido' => trim($html) !== '',
                'parse_ok' => (bool) $consultaPayload['parse_ok'],
                'estado_detectado' => (bool) $consultaPayload['estado_detectado'],
                'cambio_detectado' => $cambioDetectado,
                'estado_resumen' => $consultaPayload['estado_resumen'],
                'latest_event_code' => $consultaPayload['latest_event_code'],
                'latest_event_time_raw' => $consultaPayload['latest_event_time_raw'],
                'estado_firma' => $consultaPayload['estado_firma'],
                'html_hash' => $consultaPayload['html_hash'],
                'parsed_payload_json' => $consultaPayload['parsed_payload_json'],


                'raw_html' => $this->shouldStoreRawHtml($estadoActual, $consultaPayload['html_hash'], true)
                    ? $this->normalizeHtmlForStorage($html)
                    : null,



                'parser_version' => $consultaPayload['parser_version'],
                'error_code' => null,
                'error_message' => null,
                'consultado_en' => now(),
            ]);

            $estadoActual->fill([
                ...$estadoPayload,
                'ultima_consulta_at' => now(),
                'ultima_consulta_exitosa_at' => now(),
                'ultimo_cambio_at' => $cambioDetectado
                    ? now()
                    : ($estadoActual->ultimo_cambio_at ?: now()),
                'ultimo_error_code' => null,
                'ultimo_error_message' => null,
            ]);

            $estadoActual->save();

            return [$estadoActual, $consulta, $cambioDetectado];
        });
    }

    private function registrarConsultaFallida(
        TrackingAlmacenado $trackingAlmacenado,
        ?TrackingEstadoActual $estadoActual,
        ?int $httpStatus,
        ?string $html,
        string $docType,
        string $errorCode,
        string $errorMessage,
        ?array $parsedPayload,
    ): TrackingConsulta {
        return DB::transaction(function () use (
            $trackingAlmacenado,
            $estadoActual,
            $httpStatus,
            $html,
            $docType,
            $errorCode,
            $errorMessage,
            $parsedPayload,
        ) {
            $htmlHash = $html !== null && trim($html) !== ''
                ? hash('sha256', $html)
                : null;

            $consulta = TrackingConsulta::create([
                'tracking_almacenado_id' => $trackingAlmacenado->id,
                'document_type' => $docType,
                'latam_http_status' => $httpStatus,
                'latam_respondio' => $httpStatus !== null || ($html !== null),
                'html_recibido' => $html !== null && trim($html) !== '',
                'parse_ok' => false,
                'estado_detectado' => false,
                'cambio_detectado' => false,
                'estado_resumen' => null,
                'latest_event_code' => null,
                'latest_event_time_raw' => null,
                'estado_firma' => null,
                'html_hash' => $htmlHash,
                'parsed_payload_json' => $parsedPayload,



                'raw_html' => $this->normalizeHtmlForStorage($html),



                'parser_version' => LatamTrackingStateBuilder::PARSER_VERSION,
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'consultado_en' => now(),
            ]);

            if ($estadoActual) {
                $estadoActual->fill([
                    'document_type' => $docType,
                    'ultima_consulta_at' => now(),
                    'ultimo_error_code' => $errorCode,
                    'ultimo_error_message' => $errorMessage,
                    'parser_version' => LatamTrackingStateBuilder::PARSER_VERSION,
                ]);

                if (!$estadoActual->tiene_estado_valido) {
                    $estadoActual->fill([
                        'html_hash' => $htmlHash,
                    ]);
                }

                $estadoActual->save();
            }

            return $consulta;
        });
    }

    private function detectarCambio(
        TrackingEstadoActual $estadoActual,
        array $nuevoEstadoPayload,
    ): bool {
        $nuevaFirma = $nuevoEstadoPayload['estado_firma'] ?? null;

        if (!$estadoActual->exists) {
            return true;
        }

        if (!$estadoActual->tiene_estado_valido && ($nuevoEstadoPayload['tiene_estado_valido'] ?? false)) {
            return true;
        }

        if ($estadoActual->estado_firma === null && $nuevaFirma !== null) {
            return true;
        }

        return $estadoActual->estado_firma !== $nuevaFirma;
    }

    private function shouldStoreRawHtml(
        TrackingEstadoActual $estadoActual,
        ?string $newHtmlHash,
        bool $parseOk,
    ): bool {
        if (!$parseOk) {
            return true;
        }

        if (!$estadoActual->exists || $estadoActual->html_hash === null) {
            return true;
        }

        return $estadoActual->html_hash !== $newHtmlHash;
    }

    private function normalizeHtmlForStorage(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $html = trim($html);

        if ($html === '') {
            return null;
        }

        return mb_convert_encoding($html, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
    }



}