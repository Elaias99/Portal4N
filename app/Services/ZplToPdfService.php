<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use setasign\Fpdi\Fpdi;

class ZplToPdfService
{
    // Puedes ajustar estos 2 valores si cambias de etiqueta o densidad
    protected string $labelaryUrl = 'https://api.labelary.com/v1/printers/8dpmm/labels/4x6/';
    protected int $chunkSize = 50;

    public function convert(string $zpl): string
    {
        // Separar etiquetas (^XA ... ^XZ)
        preg_match_all('/\^XA.*?\^XZ/s', $zpl, $matches);
        $labels = collect($matches[0]);

        if ($labels->isEmpty()) {
            throw new \Exception('ZPL sin etiquetas válidas');
        }

        // Sub-lotes (Labelary)
        $chunks = $labels->chunk($this->chunkSize);

        $pdf = new Fpdi();

        foreach ($chunks as $chunk) {
            $chunkZpl = $chunk->implode("\n");

            // Llamada a Labelary (con retry básico)
            $response = $this->requestLabelaryPdf($chunkZpl);

            if (!$response->ok()) {
                throw new \Exception('Error Labelary: ' . $response->body());
            }

            // Guardar PDF parcial temporal
            $tmpPdf = tempnam(sys_get_temp_dir(), 'zplpdf_');
            if ($tmpPdf === false) {
                throw new \Exception('No se pudo crear archivo temporal');
            }

            file_put_contents($tmpPdf, $response->body());

            try {
                // Importar páginas del PDF parcial
                $pageCount = $pdf->setSourceFile($tmpPdf);

                for ($page = 1; $page <= $pageCount; $page++) {
                    $tpl  = $pdf->importPage($page);
                    $size = $pdf->getTemplateSize($tpl);

                    // FIX CLAVE: crear la página con el mismo tamaño del template (evita A4)
                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);

                    // Usar el template ocupando todo el lienzo
                    $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);
                }
            } finally {
                @unlink($tmpPdf);
            }
        }

        // Devolver PDF final en memoria
        return $pdf->Output('S');
    }

    protected function requestLabelaryPdf(string $zpl): \Illuminate\Http\Client\Response
    {
        $attempts = 0;

        while (true) {
            $attempts++;

            try {
                $response = Http::withHeaders([
                    'Accept' => 'application/pdf',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ])
                    ->timeout(60)
                    ->withBody($zpl, 'application/x-www-form-urlencoded')
                    ->post($this->labelaryUrl);
            } catch (ConnectionException $e) {
                if ($attempts >= 3) {
                    throw $e;
                }
                usleep(400000 * $attempts); // 0.4s, 0.8s
                continue;
            }

            // Rate limit (429): respetar Retry-After si viene
            if ($response->status() === 429 && $attempts < 4) {
                $retryAfter = (int) ($response->header('Retry-After') ?? 1);
                sleep(max(1, $retryAfter));
                continue;
            }

            // Errores 5xx: reintentar un par de veces
            if ($response->serverError() && $attempts < 3) {
                usleep(400000 * $attempts);
                continue;
            }

            return $response;
        }
    }



}
