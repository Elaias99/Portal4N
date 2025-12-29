<?php

namespace App\Services\Sii;

use Illuminate\Http\UploadedFile;

class BteResumenAnualParser
{
    protected UploadedFile $archivo;
    protected string $contenido;

    public function __construct(UploadedFile $archivo)
    {
        $this->archivo = $archivo;

        $raw = file_get_contents($archivo->getRealPath());

        // Normalización de encoding (archivos SII)
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
        return [
            'rut_contribuyente' => $this->extraerRut(),
            'razon_social'      => $this->extraerRazonSocial(),
            'anio'              => $this->extraerAnio(),
            'meses'             => $this->extraerMeses(),
            'totales'           => $this->extraerTotales(),
        ];
    }


    protected function extraerRut(): ?string
    {
        if (preg_match('/RUT\s*:\s*([0-9\-Kk]+)/', $this->contenido, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    protected function extraerRazonSocial(): ?string
    {
        if (preg_match('/Contribuyente\s*:\s*([^\r\n<]+)/i', $this->contenido, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    protected function extraerAnio(): ?int
    {
        if (preg_match('/Informe\s+correspondiente\s+al\s+año\s+(\d{4})/i', $this->contenido, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    protected function extraerMeses(): array
    {
        $meses = [];

        preg_match_all(
            '/<tr>\s*<td>.*?(ENERO|FEBRERO|MARZO|ABRIL|MAYO|JUNIO|JULIO|AGOSTO|SEPTIEMBRE|OCTUBRE|NOVIEMBRE|DICIEMBRE).*?<\/tr>/si',
            $this->contenido,
            $rows
        );

        foreach ($rows[0] as $row) {
            preg_match_all(
                '/<td[^>]*>\s*<div[^>]*>\s*<font[^>]*>(.*?)<\/font>/si',
                $row,
                $cols
            );

            if (count($cols[1]) >= 8) {
                $meses[] = [
                    'mes_nombre'       => trim($cols[1][0]),
                    'folio_inicial'    => (int) str_replace('.', '', $cols[1][1]),
                    'folio_final'      => (int) str_replace('.', '', $cols[1][2]),
                    'boletas_vigentes' => (int) $cols[1][3],
                    'boletas_nulas'    => (int) $cols[1][4],
                    'honorario_bruto'  => (int) str_replace('.', '', $cols[1][5]),
                    'retenciones'      => (int) str_replace('.', '', $cols[1][6]),
                    'total_liquido'    => (int) str_replace('.', '', $cols[1][7]),
                ];
            }
        }

        return $meses;
    }


    protected function extraerTotales(): ?array
    {
        if (preg_match(
            '/\*Totales.*?<font[^>]*>(\d+).*?<font[^>]*>(\d+).*?<font[^>]*>(\d+).*?<font[^>]*>(\d+).*?<font[^>]*>([\d\.]+).*?<font[^>]*>([\d\.]+).*?<font[^>]*>([\d\.]+)/si',
            $this->contenido,
            $m
        )) {
            return [
                'folio_inicial'    => (int) $m[1],
                'folio_final'      => (int) $m[2],
                'boletas_vigentes' => (int) $m[3],
                'boletas_nulas'    => (int) $m[4],
                'honorario_bruto'  => (int) str_replace('.', '', $m[5]),
                'retenciones'      => (int) str_replace('.', '', $m[6]),
                'total_liquido'    => (int) str_replace('.', '', $m[7]),
            ];
        }

        return null;
    }



}
