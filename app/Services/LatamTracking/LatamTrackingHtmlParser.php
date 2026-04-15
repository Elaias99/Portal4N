<?php

namespace App\Services\LatamTracking;

class LatamTrackingHtmlParser
{
    private const DEFAULT_DOC_TYPE = 'SO';

    public function parse(
        string $html,
        string $prefix,
        string $code,
        string $docType = self::DEFAULT_DOC_TYPE
    ): array {
        [$document, $xpath] = $this->makeXPath($html);

        $summary = $this->parseSummaryRow($xpath);
        $label = $this->parseLabel($xpath);
        $arrivalOnOrBefore = $this->parseArrivalOnOrBefore($xpath);
        $latestLeg = $this->parseLatestLeg($xpath);
        $latestEvent = $this->parseLatestEvent($xpath);
        $statusSummary = $this->parseStatusSummary($xpath);
        $hiddenMetadata = $this->parseHiddenMetadata($xpath);
        $irregularities = $this->parseIrregularities($xpath);
        $exportOptions = $this->parseExportOptions($xpath);

        $hasData = !empty($label)
            || !empty($summary['origin'] ?? null)
            || !empty($summary['destination'] ?? null)
            || !empty($latestLeg)
            || !empty($latestEvent);

        if (!$hasData) {
            return [
                'ok' => false,
                'query' => [
                    'prefix' => $prefix,
                    'code' => $code,
                    'document_type' => $docType,
                ],
                'error' => 'no_tracking_data',
                'tracking' => null,
                'latest_leg' => null,
                'latest_event' => null,
                'hidden_metadata' => [],
                'irregularities' => [],
                'export_options' => [
                    'xml_modal_available' => false,
                    'pdf_modal_available' => false,
                ],
            ];
        }

        return [
            'ok' => true,
            'query' => [
                'prefix' => $prefix,
                'code' => $code,
                'document_type' => $docType,
            ],
            'tracking' => [
                'label' => $label,
                'origin' => $summary['origin'] ?? null,
                'destination' => $summary['destination'] ?? null,
                'status_summary' => $statusSummary,
                'arrival_on_or_before' => $arrivalOnOrBefore,
                'product' => $summary['product'] ?? null,
                'commodity' => $summary['commodity'] ?? null,
                'pieces' => $this->toInt($summary['pieces'] ?? null),
                'weight' => $this->toFloat($summary['weight'] ?? null),
            ],
            'latest_leg' => $latestLeg,
            'latest_event' => $latestEvent,
            'hidden_metadata' => $hiddenMetadata,
            'irregularities' => $irregularities,
            'export_options' => $exportOptions,
        ];
    }

    private function parseLabel(\DOMXPath $xpath): ?string
    {
        $node = $this->queryOne($xpath, '//p[contains(@class, "txt-bold")]//u[1]');
        return $this->extractText($node);
    }

    private function parseArrivalOnOrBefore(\DOMXPath $xpath): ?string
    {
        $node = $this->queryOne($xpath, '//*[@id="sla_time_details"]');
        $text = $this->extractText($node);

        if (!$text) {
            return null;
        }

        if (preg_match('/on\/before\s+(.+)$/i', $text, $matches)) {
            return $this->norm($matches[1]);
        }

        if (preg_match('/en\/antes\s+(.+)$/i', $text, $matches)) {
            return $this->norm($matches[1]);
        }

        return $text;
    }

    private function parseSummaryRow(\DOMXPath $xpath): array
    {
        $row = $this->queryOne(
            $xpath,
            '//tr[' . $this->xpathClassContains('btn1') . ']'
        );

        if (!$row) {
            return [];
        }

        $cells = $xpath->query('./td', $row);

        if (!$cells || $cells->length < 6) {
            return [];
        }

        return [
            'origin' => $this->extractText($cells->item(0)),
            'destination' => $this->extractText($cells->item(1)),
            'product' => $this->extractText($cells->item(2)),
            'commodity' => $this->extractText($cells->item(3)),
            'pieces' => $this->extractText($cells->item(4)),
            'weight' => $this->extractText($cells->item(5)),
        ];
    }

    private function parseLatestLeg(\DOMXPath $xpath): array
    {
        $rows = $xpath->query('//tr[' . $this->xpathClassContains('legDetails_tr') . ']');

        if (!$rows || $rows->length === 0) {
            return [];
        }

        foreach ($rows as $row) {
            if ($this->isHiddenNode($row)) {
                continue;
            }

            $cells = $xpath->query('./td', $row);

            if (!$cells || $cells->length < 7) {
                continue;
            }

            return [
                'origin' => $this->extractText($cells->item(0)),
                'destination' => $this->extractText($cells->item(1)),
                'flight' => $this->extractText($cells->item(2)),
                'etd' => $this->extractText($cells->item(3)),
                'eta' => $this->extractText($cells->item(4)),
                'pieces' => $this->toInt($this->extractText($cells->item(5))),
                'weight' => $this->toFloat($this->extractText($cells->item(6))),
            ];
        }

        return [];
    }

    private function parseLatestEvent(\DOMXPath $xpath): array
    {
        $row = $this->queryOne($xpath, '//*[@id="statusTable"]//tbody/tr[1]');

        if (!$row) {
            $row = $this->queryOne($xpath, '//*[@id="statusTable"]//tr[1]');
        }

        if (!$row) {
            return [];
        }

        $cells = $xpath->query('./td', $row);

        if (!$cells || $cells->length < 6) {
            return [];
        }

        return [
            'code' => $this->extractText($cells->item(0)),
            'description' => $this->extractText($cells->item(1)),
            'station' => $this->extractText($cells->item(2)),
            'flight' => $this->extractText($cells->item(3)),
            'actual_pk' => $this->extractText($cells->item(4)),
            'actual_time' => $this->extractText($cells->item(5)),
        ];
    }

    private function parseStatusSummary(\DOMXPath $xpath): ?string
    {
        $activeNodes = $xpath->query(
            '//*[' . $this->xpathClassContains('active_flt_dots') . ']//*[contains(@class, "flt_dot_desc")]'
        );

        $activeTexts = [];

        if ($activeNodes) {
            foreach ($activeNodes as $node) {
                $text = $this->extractText($node);
                if ($text) {
                    $activeTexts[] = $text;
                }
            }
        }

        for ($i = count($activeTexts) - 1; $i >= 0; $i--) {
            $status = $this->resolveStatusText($activeTexts[$i]);
            if ($status) {
                return $status;
            }
        }

        $summary = $this->parseSummaryRow($xpath);
        $origin = $summary['origin'] ?? null;
        $destination = $summary['destination'] ?? null;
        $latestEvent = $this->parseLatestEvent($xpath);

        $code = strtoupper((string) ($latestEvent['code'] ?? ''));
        $station = $latestEvent['station'] ?? null;

        if ($code === 'DLV') {
            return 'Entregado';
        }

        if (in_array($code, ['RCF', 'ARR', 'NFD'], true)) {
            if ($station) {
                return 'Llegó a ' . $station;
            }

            if ($destination) {
                return 'Llegó a ' . $destination;
            }

            return 'Llegó a destino';
        }

        if (in_array($code, ['DEP', 'MAN'], true)) {
            return 'En tránsito';
        }

        if (in_array($code, ['BKD', 'FOH', 'RCS'], true)) {
            if ($station) {
                return 'Recibido en ' . $station;
            }

            if ($origin) {
                return 'Recibido en ' . $origin;
            }

            return 'Recibido';
        }

        $blockText = $this->extractText(
            $this->queryOne($xpath, '//*[@id="trackShipmentResult"]')
        ) ?? '';

        $status = $this->resolveStatusText($blockText);

        if ($status) {
            return $status;
        }

        return null;
    }

    private function parseHiddenMetadata(\DOMXPath $xpath): array
    {
        $data = [
            'shipment_master_id' => $this->textByXPath($xpath, '//p[' . $this->xpathClassContains('shpMst') . '][1]'),
            'access_key' => $this->textByXPath($xpath, '//p[' . $this->xpathClassContains('accessKeyy') . '][1]'),
            'shipment_master_pdf_id' => $this->textByXPath($xpath, '//p[' . $this->xpathClassContains('shpMstIDPDF') . '][1]'),
            'service_code' => $this->textByXPath($xpath, '//*[@id="serviceCode"][1]'),
            'pickup_flag' => $this->textByXPath($xpath, '//p[' . $this->xpathClassContains('coleta') . '][1]'),
            'delivery_flag' => $this->textByXPath($xpath, '//p[' . $this->xpathClassContains('entrega') . '][1]'),
            'commodity_code' => $this->textByXPath($xpath, '//p[' . $this->xpathClassContains('commodityCode') . '][1]'),
            'product_code' => $this->textByXPath($xpath, '//p[' . $this->xpathClassContains('productCode') . '][1]'),
            'product_type_code' => $this->textByXPath($xpath, '//*[@id="productType"][1]'),
            'time_definite_flag' => $this->textByXPath($xpath, '//*[@id="timeDefinite"][1]'),
            'aircraft_type' => $this->textByXPath($xpath, '//p[' . $this->xpathClassContains('ac_type') . '][1]'),
            'flight_date_hh' => $this->textByXPath($xpath, '//p[' . $this->xpathClassContains('flt_date_hh') . '][1]'),
        ];

        return array_filter(
            $data,
            fn ($value) => $value !== null && $value !== ''
        );
    }

    private function parseIrregularities(\DOMXPath $xpath): array
    {
        $items = [];

        $irrNodes = $xpath->query('//p[' . $this->xpathClassContains('irrListSHP') . ']');

        if ($irrNodes) {
            foreach ($irrNodes as $node) {
                $text = $this->extractText($node);
                if ($text) {
                    $items[] = $text;
                }
            }
        }

        $hiddenLegRows = $xpath->query('//tr[' . $this->xpathClassContains('legDetails_tr') . ']');

        if ($hiddenLegRows) {
            foreach ($hiddenLegRows as $row) {
                if (!$this->isHiddenNode($row)) {
                    continue;
                }

                $text = $this->extractText($row);

                if ($text && mb_strlen($text) <= 120) {
                    $items[] = $text;
                }
            }
        }

        return $this->dedupeKeepOrder($items);
    }

    private function parseExportOptions(\DOMXPath $xpath): array
    {
        return [
            'xml_modal_available' => $this->queryOne(
                $xpath,
                '//a[' . $this->xpathClassContains('xml') . ' and ' . $this->xpathClassContains('openModal') . ']'
            ) !== null,
            'pdf_modal_available' => $this->queryOne(
                $xpath,
                '//a[' . $this->xpathClassContains('pdf') . ' and ' . $this->xpathClassContains('openModal') . ']'
            ) !== null,
        ];
    }

    private function resolveStatusText(?string $text): ?string
    {
        $text = $this->norm($text);

        if ($text === '') {
            return null;
        }

        if (preg_match('/(?:llegou en|llegó en|llegó a|arrived at)\s+([A-Z]{3})/iu', $text, $matches)) {
            return 'Llegó a ' . strtoupper($matches[1]);
        }

        if (preg_match('/(?:recibido en|received at)\s+([A-Z]{3})/iu', $text, $matches)) {
            return 'Recibido en ' . strtoupper($matches[1]);
        }

        if (preg_match('/(?:en transito|en tránsito|in transit)/iu', $text)) {
            return 'En tránsito';
        }

        if (preg_match('/(?:entregado|delivered)/iu', $text)) {
            return 'Entregado';
        }

        return null;
    }

    private function makeXPath(string $html): array
    {
        $previous = libxml_use_internal_errors(true);

        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->loadHTML(
            '<?xml encoding="UTF-8">' . $html,
            LIBXML_NOERROR | LIBXML_NOWARNING
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return [$document, new \DOMXPath($document)];
    }

    private function queryOne(\DOMXPath $xpath, string $expression, ?\DOMNode $context = null): ?\DOMNode
    {
        $nodes = $xpath->query($expression, $context);

        if (!$nodes || $nodes->length === 0) {
            return null;
        }

        return $nodes->item(0);
    }

    private function textByXPath(\DOMXPath $xpath, string $expression, ?\DOMNode $context = null): ?string
    {
        return $this->extractText(
            $this->queryOne($xpath, $expression, $context)
        );
    }

    private function extractText(?\DOMNode $node): ?string
    {
        if (!$node) {
            return null;
        }

        $text = $this->norm($node->textContent ?? '');

        return $text !== '' ? $text : null;
    }

    private function norm(?string $text): string
    {
        $text = $text ?? '';
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';
        return trim($text);
    }

    private function toInt($value): ?int
    {
        if ($value === null) {
            return null;
        }

        $clean = trim((string) $value);

        if ($clean === '' || !preg_match('/^-?\d+$/', $clean)) {
            return null;
        }

        return (int) $clean;
    }

    private function toFloat($value): ?float
    {
        if ($value === null) {
            return null;
        }

        $clean = trim(str_replace(',', '.', (string) $value));

        if ($clean === '' || !is_numeric($clean)) {
            return null;
        }

        return (float) $clean;
    }

    private function xpathClassContains(string $class): string
    {
        return 'contains(concat(" ", normalize-space(@class), " "), " ' . $class . ' ")';
    }

    private function isHiddenNode(\DOMNode $node): bool
    {
        if (!($node instanceof \DOMElement)) {
            return false;
        }

        $style = strtolower((string) $node->getAttribute('style'));
        if (str_contains($style, 'display:none') || str_contains($style, 'visibility:hidden')) {
            return true;
        }

        if ($node->hasAttribute('hidden')) {
            return true;
        }

        $class = ' ' . $this->norm($node->getAttribute('class')) . ' ';
        if (str_contains($class, ' d-none ') || str_contains($class, ' hidden ')) {
            return true;
        }

        return false;
    }

    private function dedupeKeepOrder(array $items): array
    {
        $out = [];
        $seen = [];

        foreach ($items as $item) {
            $value = $this->norm((string) $item);

            if ($value === '') {
                continue;
            }

            if (isset($seen[$value])) {
                continue;
            }

            $seen[$value] = true;
            $out[] = $value;
        }

        return $out;
    }
}