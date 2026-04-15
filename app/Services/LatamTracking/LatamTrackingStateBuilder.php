<?php

namespace App\Services\LatamTracking;

class LatamTrackingStateBuilder
{
    public const PARSER_VERSION = 'latam-html-parser-v1';

    public function build(array $parsedResult, string $html): array
    {
        $tracking = $parsedResult['tracking'] ?? [];
        $latestEvent = $parsedResult['latest_event'] ?? [];
        $latestLeg = $parsedResult['latest_leg'] ?? [];
        $hiddenMetadata = $parsedResult['hidden_metadata'] ?? [];
        $irregularities = $parsedResult['irregularities'] ?? [];
        $exportOptions = $parsedResult['export_options'] ?? [];
        $query = $parsedResult['query'] ?? [];

        $parseOk = (bool) ($parsedResult['ok'] ?? false);

        $estadoResumen = $this->nullableString($tracking['status_summary'] ?? null);
        $latestEventCode = $this->nullableString($latestEvent['code'] ?? null);
        $latestEventTimeRaw = $this->nullableString($latestEvent['actual_time'] ?? null);

        $estadoDetectado = $this->hasAnyValue([
            $estadoResumen,
            $latestEventCode,
            $latestEventTimeRaw,
            $this->nullableString($tracking['origin'] ?? null),
            $this->nullableString($tracking['destination'] ?? null),
        ]);

        $estadoFirma = $parseOk
            ? $this->buildEstadoFirma($tracking, $latestEvent, $latestLeg)
            : null;

        $htmlHash = trim($html) !== '' ? hash('sha256', $html) : null;

        return [
            'estado_actual' => [
                'document_type' => $this->nullableString($query['document_type'] ?? null) ?? 'SO',
                'tiene_estado_valido' => $parseOk && $estadoDetectado,
                'estado_resumen' => $estadoResumen,
                'origen' => $this->nullableString($tracking['origin'] ?? null),
                'destino_latam' => $this->nullableString($tracking['destination'] ?? null),
                'arrival_on_or_before_raw' => $this->nullableString($tracking['arrival_on_or_before'] ?? null),
                'product' => $this->nullableString($tracking['product'] ?? null),
                'commodity' => $this->nullableString($tracking['commodity'] ?? null),
                'pieces' => $this->nullableInt($tracking['pieces'] ?? null),
                'weight' => $this->nullableFloat($tracking['weight'] ?? null),
                'latest_event_code' => $latestEventCode,
                'latest_event_description' => $this->nullableString($latestEvent['description'] ?? null),
                'latest_event_station' => $this->nullableString($latestEvent['station'] ?? null),
                'latest_event_time_raw' => $latestEventTimeRaw,
                'latest_leg_flight' => $this->nullableString($latestLeg['flight'] ?? null),
                'latest_leg_etd_raw' => $this->nullableString($latestLeg['etd'] ?? null),
                'latest_leg_eta_raw' => $this->nullableString($latestLeg['eta'] ?? null),
                'parsed_payload_json' => $parsedResult,
                'hidden_metadata_json' => is_array($hiddenMetadata) ? $hiddenMetadata : [],
                'irregularities_json' => is_array($irregularities) ? array_values($irregularities) : [],
                'export_options_json' => is_array($exportOptions) ? $exportOptions : [],
                'estado_firma' => $estadoFirma,
                'html_hash' => $htmlHash,
                'parser_version' => self::PARSER_VERSION,
            ],
            'consulta' => [
                'document_type' => $this->nullableString($query['document_type'] ?? null) ?? 'SO',
                'parse_ok' => $parseOk,
                'estado_detectado' => $estadoDetectado,
                'estado_resumen' => $estadoResumen,
                'latest_event_code' => $latestEventCode,
                'latest_event_time_raw' => $latestEventTimeRaw,
                'estado_firma' => $estadoFirma,
                'html_hash' => $htmlHash,
                'parsed_payload_json' => $parsedResult,
                'parser_version' => self::PARSER_VERSION,
            ],
        ];
    }

    public function buildEstadoFirma(array $tracking, array $latestEvent, array $latestLeg): string
    {
        $parts = [
            $this->nullableString($tracking['status_summary'] ?? null),
            $this->nullableString($latestEvent['code'] ?? null),
            $this->nullableString($latestEvent['description'] ?? null),
            $this->nullableString($latestEvent['station'] ?? null),
            $this->nullableString($latestEvent['actual_time'] ?? null),
            $this->nullableString($latestLeg['flight'] ?? null),
            $this->nullableString($latestLeg['etd'] ?? null),
            $this->nullableString($latestLeg['eta'] ?? null),
            $this->nullableString($tracking['arrival_on_or_before'] ?? null),
            $this->nullableString($tracking['pieces'] ?? null),
            $this->nullableString($tracking['weight'] ?? null),
        ];

        $normalized = implode('|', array_map(
            fn ($value) => $value === null ? '' : mb_strtoupper(trim((string) $value)),
            $parts
        ));

        return hash('sha256', $normalized);
    }

    private function hasAnyValue(array $values): bool
    {
        foreach ($values as $value) {
            if ($this->nullableString($value) !== null) {
                return true;
            }
        }

        return false;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '';
        return $text !== '' ? $text : null;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value) && (string) (int) $value === (string) $value) {
            return (int) $value;
        }

        return null;
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_float($value) || is_int($value)) {
            return (float) $value;
        }

        $normalized = str_replace(',', '.', trim((string) $value));

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}