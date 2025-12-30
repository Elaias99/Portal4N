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
            'mes'               => $this->extraerMes(),
            'registros'         => $this->extraerBoletas(),
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

    protected function extraerMes(): ?int
    {
        if (preg_match(
            '/correspondiente\s+a\s+([A-Za-zÁÉÍÓÚáéíóú]+)\s+del\s+año/i',
            $this->contenido,
            $m
        )) {
            $mes = strtoupper(trim($m[1]));

            return [
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
            ][$mes] ?? null;
        }

        return null;
    }


    /**
     * EXTRAER BOLETAS INDIVIDUALES
     */
    protected function extraerBoletas(): array
    {
        $boletas = [];

        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $this->contenido, $rows);

        foreach ($rows[1] as $row) {
            preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $row, $cols);

            $c = array_map(
                fn ($v) => trim(preg_replace('/\s+/', ' ', strip_tags($v))),
                $cols[1]
            );

            // Validación mínima: fila real de boleta
            if (count($c) < 11 || !is_numeric($c[0])) {
                continue;
            }

            $boletas[] = [
                'folio'           => (int) $c[0],
                'estado'          => $c[1],

                // 🔴 CLAVES QUE FALTABAN
                'fecha_boleta'    => $this->normalizarFecha($c[2]),
                'fecha_emision'   => $this->normalizarFecha($c[5]),

                'rut_emisor'      => $c[3],
                'nombre_emisor'   => $c[4],

                'rut_receptor'    => $c[6],
                'nombre_receptor' => $c[7],

                'monto_bruto'     => (int) str_replace('.', '', $c[8]),
                'monto_retenido'  => (int) str_replace('.', '', $c[9]),
                'monto_pagado'    => (int) str_replace('.', '', $c[10]),
            ];
        }

        return $boletas;
    }


    protected function extraerTotales(): ?array
    {
        return preg_match(
            '/Totales.*?([\d\.]+).*?([\d\.]+).*?([\d\.]+)/si',
            $this->contenido,
            $m
        )
        ? [
            'bruto'    => (int) str_replace('.', '', $m[1]),
            'retenido' => (int) str_replace('.', '', $m[2]),
            'pagado'   => (int) str_replace('.', '', $m[3]),
        ]
        : null;
    }

    protected function normalizarFecha(?string $fecha): ?string
    {
        if (!$fecha) {
            return null;
        }

        // Espera formato dd-mm-yyyy
        $fecha = trim($fecha);

        if (preg_match('/(\d{2})-(\d{2})-(\d{4})/', $fecha, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        return null;
    }



}
