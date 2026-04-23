<?php

namespace App\Services;

use Carbon\Carbon;
use Smalot\PdfParser\Parser;


class LatamGuidePdfParserService
{
    public function parsePdfFile(string $pdfPath): array
    {
        $rawText = $this->extractPdfText($pdfPath);

        return $this->parseLatamPdfText($rawText);
    }

    public function parsePdfBinary(string $binaryContent): array
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'latam_pdf_');

        if ($tmpPath === false) {
            throw new \RuntimeException('No se pudo crear archivo temporal.');
        }

        file_put_contents($tmpPath, $binaryContent);

        try {
            return $this->parsePdfFile($tmpPath);
        } finally {
            @unlink($tmpPath);
        }
    }

    public function extractPdfText(string $pdfPath): string
    {
        $parser = new Parser();
        $document = $parser->parseFile($pdfPath);
        $text = trim($document->getText());

        if ($text === '') {
            throw new \RuntimeException('El PDF no devolvió texto.');
        }

        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        $text = preg_replace("/\r\n|\r/u", "\n", $text) ?? $text;
        $text = preg_replace('/[^\P{C}\n\t]/u', '', $text) ?? $text;

        return $text;
    }

    public function parseLatamPdfText(string $text): array
    {
        $lines = preg_split('/\n+/u', $text) ?: [];

        $lines = array_values(array_filter(array_map(function ($line) {
            $line = preg_replace('/\s+/u', ' ', trim($line)) ?? trim($line);
            return $line;
        }, $lines), fn ($line) => $line !== ''));

        $normalized = implode(' ', $lines);

        $os = null;
        $prefix = null;
        $code = null;
        $origin = null;
        $destinationCode = null;
        $originCity = null;
        $destinationCity = null;
        $issuedAt = null;
        $fechaProceso = null;
        $destinoFormulario = null;

        if (
            preg_match('/\bNumero\s+de\s+OS\s*(\d{3})-(\d{8})\b/iu', $normalized, $m) ||
            preg_match('/\b(\d{3})-(\d{8})\b/u', $normalized, $m)
        ) {
            $prefix = $m[1];
            $code = $m[2];
            $os = $prefix . '-' . $code;
        }



        foreach ($lines as $line) {
            if ($origin === null && preg_match('/^Origen\s*([A-Z]{3})$/iu', $line, $m)) {
                $origin = strtoupper($m[1]);
                continue;
            }

            if ($destinationCode === null && preg_match('/^Destino\s*([A-Z]{3})$/iu', $line, $m)) {
                $destinationCode = strtoupper($m[1]);
                continue;
            }
        }

        if ($origin === null && preg_match('/\bOrigen\s*([A-Z]{3})\b/iu', $normalized, $m)) {
            $origin = strtoupper($m[1]);
        }

        if ($destinationCode === null && preg_match('/\bDestino\s*([A-Z]{3})\b/iu', $normalized, $m)) {
            $destinationCode = strtoupper($m[1]);
        }

        

        if (preg_match('/\bSHP\.EmiDateTime\s*(\d{4}-\d{2}-\d{2})(?:\s*(\d{2}:\d{2}))?/iu', $normalized, $m)) {
            $issuedAt = trim(($m[1] ?? '') . ' ' . ($m[2] ?? ''));

            $fechaProceso = Carbon::createFromFormat('Y-m-d', $m[1])
                ->addDay()
                ->toDateString();
        }

        foreach ($lines as $line) {
            if (
                $originCity === null &&
                preg_match('/^Ciudad\s+([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ\s\-]{1,60})$/u', $line, $m)
            ) {
                $candidate = $this->cleanValue($m[1]);

                if (
                    $candidate !== null &&
                    !preg_match('/^(SHP\.EmiDateTime|Nombre de usuario|Datos del Envio)$/iu', $candidate)
                ) {
                    $originCity = $candidate;
                    continue;
                }
            }

            if (
                $destinationCity === null &&
                preg_match('/^([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ\s\-]{1,60})\s+Ciudad$/u', $line, $m)
            ) {
                $candidate = $this->cleanValue($m[1]);

                if (
                    $candidate !== null &&
                    !preg_match('/^(Origen|Destino|Numero de OS)$/iu', $candidate)
                ) {
                    $destinationCity = $candidate;
                    continue;
                }
            }
        }

        $destinationCity = $this->resolveDestinationCity($destinationCode, $destinationCity);

        if ($origin && $destinationCity) {
            $destinoFormulario = $origin . ' ' . $destinationCity;
        }

        return [
            'os' => $os,
            'prefijo' => $prefix,
            'codigo_tracking' => $code,
            'origen_codigo' => $origin,
            'destino_codigo' => $destinationCode,
            'ciudad_origen' => $originCity,
            'ciudad_destino' => $destinationCity,
            'fecha_emision_raw' => $issuedAt,
            'fecha_proceso' => $fechaProceso,
            'destino_formulario' => $destinoFormulario,
        ];
    }

    private function resolveDestinationCity(?string $destinationCode, ?string $fallbackCity): ?string
    {
        $map = [
            'ARI' => 'ARICA',
            'IQQ' => 'IQUIQUE',
            'ANF' => 'ANTOFAGASTA',
            'CJC' => 'CALAMA',
            'PUQ' => 'PUNTA ARENAS',
            'BBA' => 'BALMACEDA',
            'IPC' => 'ISLA DE PASCUA',
        ];

        if ($destinationCode !== null && isset($map[$destinationCode])) {
            return $map[$destinationCode];
        }

        return $fallbackCity;
    }

    private function cleanValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);

        return $value !== '' ? $value : null;
    }
}