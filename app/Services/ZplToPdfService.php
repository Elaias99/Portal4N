<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use setasign\Fpdi\Fpdi;

class ZplToPdfService
{
    public function convert(string $zpl): string 
    {
        // 1️⃣ Separar etiquetas (^XA ... ^XZ)
        preg_match_all('/\^XA.*?\^XZ/s', $zpl, $matches);
        $labels = collect($matches[0]);

        if ($labels->isEmpty()) {
            throw new \Exception('ZPL sin etiquetas válidas');
        }

        // 2️⃣ Sub-lotes de 50
        $chunks = $labels->chunk(50);

        $pdf = new Fpdi();

        foreach ($chunks as $chunk) {
            $chunkZpl = $chunk->implode("\n");

            // 3️⃣ Llamada a Labelary (SIN índice)
            $response = Http::withHeaders([
                'Accept' => 'application/pdf',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->timeout(30)->withBody(
                $chunkZpl,
                'application/x-www-form-urlencoded'
            )->post('http://api.labelary.com/v1/printers/8dpmm/labels/4x6/');


            if (!$response->ok()) {
                throw new \Exception('Error Labelary: ' . $response->body());
            }

            // 4️⃣ Guardar PDF parcial temporal
            $tmpPdf = tempnam(sys_get_temp_dir(), 'zplpdf_');
            file_put_contents($tmpPdf, $response->body());

            // 5️⃣ Importar todas las páginas del PDF parcial
            $pageCount = $pdf->setSourceFile($tmpPdf);

            for ($page = 1; $page <= $pageCount; $page++) {
                $tpl = $pdf->importPage($page);
                $pdf->addPage();
                $pdf->useTemplate($tpl);
            }

            unlink($tmpPdf);
        }

        // 6️⃣ Guardar PDF final
        // 6️⃣ Devolver PDF final en memoria
        return $pdf->Output('S');

    }
}
