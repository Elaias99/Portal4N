<?php

namespace App\Services\Sii;

use Illuminate\Http\UploadedFile;

class BteResumenAnualParser
{
    protected UploadedFile $archivo;
    protected string $contenido;

    protected const MESES = [
        'ENERO'       => 1,
        'FEBRERO'     => 2,
        'MARZO'       => 3,
        'ABRIL'       => 4,
        'MAYO'        => 5,
        'JUNIO'       => 6,
        'JULIO'       => 7,
        'AGOSTO'      => 8,
        'SEPTIEMBRE'  => 9,
        'OCTUBRE'     => 10,
        'NOVIEMBRE'   => 11,
        'DICIEMBRE'   => 12,
    ];

    public function __construct(UploadedFile $archivo)
    {
        $this->archivo = $archivo;

        $raw = file_get_contents($archivo->getRealPath());

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
            'resumen_mensual'   => $this->extraerResumenMensual(),
            'totales'           => $this->extraerTotales(),
        ];
    }

    protected function extraerRut(): ?string
    {
        return preg_match('/RUT\s*:\s*([0-9\-Kk]+)/i', $this->contenido, $m)
            ? trim($m[1])
            : null;
    }

    protected function extraerRazonSocial(): ?string
    {
        return preg_match('/Contribuyente\s*:\s*([^\r\n<]+)/i', $this->contenido, $m)
            ? trim($m[1])
            : null;
    }

    protected function extraerAnio(): ?int
    {
        return preg_match('/año\s+(\d{4})/i', $this->contenido, $m)
            ? (int) $m[1]
            : null;
    }

    /**
     * Extrae filas mensuales del resumen anual
     */
    protected function extraerResumenMensual(): array
    {
        $resumen = [];

        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $this->contenido, $rows);

        foreach ($rows[1] as $row) {
            preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $row, $cols);

            $c = array_map(
                fn ($v) => trim(preg_replace('/\s+/', ' ', strip_tags($v))),
                $cols[1] ?? []
            );

            // Esperamos exactamente 8 columnas
            if (count($c) !== 8) {
                continue;
            }

            $mesNombre = strtoupper($c[0]);

            if (!isset(self::MESES[$mesNombre])) {
                continue;
            }

            $resumen[] = [
                'mes'              => self::MESES[$mesNombre],
                'mes_nombre'       => $mesNombre,
                'folio_inicial'    => (int) $c[1],
                'folio_final'      => (int) $c[2],
                'boletas_vigentes' => (int) $c[3],
                'boletas_nulas'    => (int) $c[4],
                'honorario_bruto'  => $this->toInt($c[5]),
                'retenciones'      => $this->toInt($c[6]),
                'total_liquido'    => $this->toInt($c[7]),
            ];
        }

        return $resumen;
    }

    /**
     * Extrae totales generales
     */
    protected function extraerTotales(): ?array
    {
        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $this->contenido, $rows);

        foreach ($rows[1] as $row) {
            preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $row, $cols);

            $c = array_map(
                fn ($v) => trim(preg_replace('/\s+/', ' ', strip_tags($v))),
                $cols[1] ?? []
            );

            // Esperamos: Totales + 7 columnas
            if (count($c) !== 8) {
                continue;
            }

            if (stripos($c[0], 'Totales') === false) {
                continue;
            }

            return [
                'folio_inicial' => (int) $c[1],
                'folio_final'   => (int) $c[2],
                'vigentes'      => (int) $c[3],
                'nulas'         => (int) $c[4],
                'bruto'         => $this->toInt($c[5]),
                'retenido'      => $this->toInt($c[6]),
                'liquido'       => $this->toInt($c[7]),
            ];
        }

        return null;
    }


    protected function toInt(string $valor): int
    {
        return (int) str_replace('.', '', $valor);
    }
}
