<?php

namespace App\Services\Sii;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class HonorarioMensualRecParser
{
    protected UploadedFile $archivo;
    protected string $contenido;

    public function __construct(UploadedFile $archivo)
    {
        $this->archivo = $archivo;

        $this->cargarContenidoCrudo();
    }

    /**
     * Lee el archivo como TEXTO y normaliza encoding
     */
    protected function cargarContenidoCrudo(): void
    {
        $raw = file_get_contents($this->archivo->getRealPath());

        $this->contenido = mb_convert_encoding(
            $raw,
            'UTF-8',
            ['ISO-8859-1', 'Windows-1252', 'UTF-8']
        );
    }

    /**
     * Punto de entrada principal
     */
    public function parse(): array
    {

        $filas = $this->extraerFilasHtml();

        $meta = $this->extraerMeta($filas[0][0] ?? '');

        $datos    = [];
        $totales  = null;

        // Las filas de datos comienzan después del header (fila índice 2)
        for ($i = 3; $i < count($filas); $i++) {
            $fila = $filas[$i];

            // Fila Totales
            if (
                isset($fila[0]) &&
                stripos($fila[0], 'totales') !== false
            ) {
                $totales = $this->mapearTotales($fila);
                break;
            }

            // Fila de datos válida (10 columnas)
            if (count($fila) === 10 && is_numeric($fila[0])) {
                $datos[] = $this->mapearRegistro($fila);
            }
        }

        return [
            'meta'      => $meta,
            'registros' => $datos,
            'totales'   => $totales,
        ];
    }

    /**
     * Extrae todas las filas <tr> como arrays de <td>
     */
    protected function extraerFilasHtml(): array
    {
        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $this->contenido, $matches);

        $filas = [];

        foreach ($matches[1] as $filaHtml) {
            preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $filaHtml, $celdas);

            if (!empty($celdas[1])) {
                $filas[] = array_map(
                    fn ($c) => trim(html_entity_decode(strip_tags($c))),
                    $celdas[1]
                );
            }
        }

        return $filas;
    }

    /**
     * Extrae contribuyente, RUT, mes y año
     */
    protected function extraerMeta(string $texto): array
    {
        preg_match('/Contribuyente:\s*(.+)/i', $texto, $c);
        preg_match('/RUT\s*:\s*([0-9Kk\-]+)/', $texto, $r);
        preg_match('/mes\s+(\d+)\s+del\s+año\s+(\d{4})/i', $texto, $p);

        return [
            'razon_social'      => $c[1] ?? null,
            'rut_contribuyente' => $r[1] ?? null,
            'mes'               => isset($p[1]) ? (int) $p[1] : null,
            'anio'              => isset($p[2]) ? (int) $p[2] : null,
        ];
    }

    /**
     * Mapea una fila de boleta
     */
    protected function mapearRegistro(array $fila): array
    {
        return [
            'folio'                   => (int) $fila[0],
            'fecha_emision'           => $this->normalizarFecha($fila[1]),
            'estado'                  => $fila[2],
            'fecha_anulacion'         => $fila[3] ? $this->normalizarFecha($fila[3]) : null,
            'rut_emisor'              => $fila[4],
            'razon_social_emisor'     => $fila[5],
            'sociedad_profesional'    => strtoupper($fila[6]) === 'SI',
            'monto_bruto'             => (int) $fila[7],
            'monto_retenido'          => (int) $fila[8],
            'monto_pagado'            => (int) $fila[9],
        ];
    }

    /**
     * Mapea fila Totales
     */
    protected function mapearTotales(array $fila): array
    {
        return [
            'monto_bruto'    => (int) ($fila[7] ?? 0),
            'monto_retenido' => (int) ($fila[8] ?? 0),
            'monto_pagado'   => (int) ($fila[9] ?? 0),
        ];
    }

    /**
     * Convierte fecha dd/mm/yyyy a yyyy-mm-dd
     */
    protected function normalizarFecha(string $fecha): ?string
    {
        if (!str_contains($fecha, '/')) {
            return null;
        }

        [$d, $m, $y] = explode('/', $fecha);

        return "{$y}-{$m}-{$d}";
    }
}
