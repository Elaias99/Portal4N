<?php

namespace App\Services\Sii;

use Illuminate\Http\UploadedFile;

class HonorarioMensualTerceroRecParser
{
    protected UploadedFile $archivo;
    protected string $contenido;

    public function __construct(UploadedFile $archivo)
    {
        $this->archivo = $archivo;
        $this->cargarContenidoCrudo();
    }

    protected function cargarContenidoCrudo(): void
    {
        $raw = file_get_contents($this->archivo->getRealPath());

        $this->contenido = mb_convert_encoding(
            $raw,
            'UTF-8',
            ['ISO-8859-1', 'Windows-1252', 'UTF-8']
        );
    }

    public function parse(): array
    {
        $meta  = $this->extraerMetaDesdeContenido();
        $filas = $this->extraerFilasHtml();

        $datos   = [];
        $totales = null;

        // En este formato:
        // fila 0 = encabezado de grupos
        // fila 1 = encabezado de columnas
        // fila 2..n = datos / totales
        for ($i = 2; $i < count($filas); $i++) {
            $fila = $filas[$i];

            if (
                isset($fila[0]) &&
                stripos($fila[0], 'totales') !== false
            ) {
                $totales = $this->mapearTotales($fila);
                break;
            }

            if (count($fila) === 11 && is_numeric(trim($fila[0]))) {
                $datos[] = $this->mapearRegistro($fila);
            }
        }

        // Fallbacks desde la primera fila válida
        if (!empty($datos)) {
            $primerRegistro = $datos[0];

            if (empty($meta['rut_contribuyente'])) {
                $meta['rut_contribuyente'] = $primerRegistro['rut_contribuyente'] ?? null;
            }

            if (empty($meta['razon_social'])) {
                $meta['razon_social'] = $primerRegistro['razon_social'] ?? null;
            }

            if (empty($meta['mes']) || empty($meta['anio'])) {
                if (!empty($primerRegistro['fecha_emision'])) {
                    [$anio, $mes] = explode('-', $primerRegistro['fecha_emision']);
                    $meta['anio'] = (int) $anio;
                    $meta['mes']  = (int) $mes;
                }
            }
        }

        return [
            'tipo_boleta' => 'Boleta de Terceros',
            'meta'        => $meta,
            'registros'   => $datos,
            'totales'     => $totales,
        ];
    }

    protected function extraerMetaDesdeContenido(): array
    {
        $textoPlano = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $this->contenido)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        preg_match('/Contribuyente\s*:\s*(.+)/iu', $textoPlano, $c);
        preg_match('/RUT\s*:\s*([0-9Kk\.\-]+)/iu', $textoPlano, $r);
        preg_match('/Informe\s+correspondiente\s+a\s+([A-Za-zÁÉÍÓÚáéíóúÑñ]+)\s+del\s+año\s+(\d{4})/iu', $textoPlano, $p);

        $mesTexto = $p[1] ?? null;
        $anio     = isset($p[2]) ? (int) $p[2] : null;

        return [
            'razon_social'      => isset($c[1]) ? trim($c[1]) : null,
            'rut_contribuyente' => $r[1] ?? null,
            'mes'               => $this->mesTextoANumero($mesTexto),
            'anio'              => $anio,
        ];
    }

    protected function extraerFilasHtml(): array
    {
        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $this->contenido, $matches);

        $filas = [];

        foreach ($matches[1] as $filaHtml) {
            preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $filaHtml, $celdas);

            if (!empty($celdas[1])) {
                $filas[] = array_map(
                    fn ($c) => $this->limpiarTexto($c),
                    $celdas[1]
                );
            }
        }

        return $filas;
    }

    protected function mapearRegistro(array $fila): array
    {
        return [
            'folio'               => (int) trim($fila[0]),
            'estado'              => trim($fila[1]),

            // Fecha Emisión
            'fecha_emision'       => $this->normalizarFecha($fila[5]),

            // Emisión
            'rut_contribuyente'   => trim($fila[3]),
            'razon_social'        => trim($fila[4]),

            // Receptor
            'rut_emisor'          => trim($fila[6]),
            'razon_social_emisor' => trim($fila[7]),

            'fecha_anulacion'      => null,
            'sociedad_profesional' => 0,

            'monto_bruto'         => $this->normalizarMonto($fila[8]),
            'monto_retenido'      => $this->normalizarMonto($fila[9]),
            'monto_pagado'        => $this->normalizarMonto($fila[10]),
        ];
    }

    protected function mapearTotales(array $fila): array
    {
        return [
            'monto_bruto'    => $this->normalizarMonto($fila[8] ?? 0),
            'monto_retenido' => $this->normalizarMonto($fila[9] ?? 0),
            'monto_pagado'   => $this->normalizarMonto($fila[10] ?? 0),
        ];
    }

    protected function normalizarFecha(?string $fecha): ?string
    {
        $fecha = trim((string) $fecha);

        if ($fecha === '') {
            return null;
        }

        if (str_contains($fecha, '-')) {
            [$d, $m, $y] = explode('-', $fecha);
            return sprintf('%04d-%02d-%02d', (int) $y, (int) $m, (int) $d);
        }

        if (str_contains($fecha, '/')) {
            [$d, $m, $y] = explode('/', $fecha);
            return sprintf('%04d-%02d-%02d', (int) $y, (int) $m, (int) $d);
        }

        return null;
    }

    protected function normalizarMonto($valor): int
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return 0;
        }

        $valor = preg_replace('/[^\d\-]/', '', $valor);

        return (int) $valor;
    }

    protected function mesTextoANumero(?string $mesTexto): ?int
    {
        if (!$mesTexto) {
            return null;
        }

        $mesTexto = mb_strtolower(trim($mesTexto), 'UTF-8');

        $mapa = [
            'enero'      => 1,
            'febrero'    => 2,
            'marzo'      => 3,
            'abril'      => 4,
            'mayo'       => 5,
            'junio'      => 6,
            'julio'      => 7,
            'agosto'     => 8,
            'septiembre' => 9,
            'setiembre'  => 9,
            'octubre'    => 10,
            'noviembre'  => 11,
            'diciembre'  => 12,
        ];

        return $mapa[$mesTexto] ?? null;
    }

    protected function limpiarTexto(string $texto): string
    {
        $texto = html_entity_decode(strip_tags($texto), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = preg_replace('/\s+/u', ' ', $texto);

        return trim($texto);
    }
}