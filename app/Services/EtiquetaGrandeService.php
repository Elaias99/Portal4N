<?php

namespace App\Services;

use Illuminate\Support\Collection;

class EtiquetaGrandeService
{
    public function makeLabel(Collection $rows): string
    {
        // Asegurar índices numéricos 0..n (para acceder por $rows[$i])
        $rows = $rows->values();

        $row = $rows->first() ?? [];

        // Campos (cabecera)
        $idEtiqueta    = (string) ($row['id'] ?? '');
        $idBulto       = (string) ($row['id_bulto'] ?? '');
        $direccion     = (string) ($row['direccion'] ?? '');
        $numeroDestino = (string) ($row['numero_destino'] ?? '');
        $dptoDestino   = (string) ($row['depto_destino'] ?? '');
        $comuna        = (string) ($row['comuna_destino'] ?? '');
        $campana       = (string) ($row['nombre_campana'] ?? '');

        $totalRevistas = $rows->count();

        /*
         |----------------------------------------------------------------------
         | Layout dinámico del detalle (evita solapes y controla extremos)
         |----------------------------------------------------------------------
         */
        $startY = 560;
        $endY   = 900; // antes de la línea/total
        $availableHeight = $endY - $startY;

        $maxColumns = 3;
        $columnX = [50, 290, 530]; // 3 columnas
        $colWidth = 230;           // ancho de cada columna (para ^FB)

        // Probar tamaños hasta que quepa en 3 columnas
        $candidates = [
            ['font' => 22, 'lh' => 28],
            ['font' => 20, 'lh' => 26],
            ['font' => 18, 'lh' => 22],
            ['font' => 16, 'lh' => 20],
            ['font' => 14, 'lh' => 18],
        ];

        $fontSize = 22;
        $lineHeight = 28;
        $rowsPerColumn = (int) floor($availableHeight / $lineHeight);
        $capacity = $rowsPerColumn * $maxColumns;

        foreach ($candidates as $opt) {
            $rp = (int) floor($availableHeight / $opt['lh']);
            $cap = $rp * $maxColumns;

            if ($totalRevistas <= $cap) {
                $fontSize = $opt['font'];
                $lineHeight = $opt['lh'];
                $rowsPerColumn = $rp;
                $capacity = $cap;
                break;
            }

            // Si no cabe, nos quedamos con el último candidato (más compacto)
            $fontSize = $opt['font'];
            $lineHeight = $opt['lh'];
            $rowsPerColumn = $rp;
            $capacity = $cap;
        }

        // Construir items del detalle (y si excede capacidad, avisar en la última línea)
        $items = [];
        $showCount = min($totalRevistas, $capacity);

        // Si no cabe todo, reservamos la última línea para "... y N más"
        $overflow = 0;
        if ($totalRevistas > $capacity) {
            $overflow = $totalRevistas - ($capacity - 1);
            $showCount = $capacity - 1; // dejamos 1 slot para el resumen
        }

        $idx = 1;
        for ($i = 0; $i < $showCount; $i++) {
            $grado  = trim((string) ($rows[$i]['grado'] ?? ''));
            $nombre = trim((string) ($rows[$i]['nombre'] ?? ''));

            // Prioriza lo más útil visualmente
            if ($nombre !== '' && $grado !== '') {
                $textoBase = "{$nombre} ({$grado})";
            } else {
                $textoBase = $nombre !== '' ? $nombre : $grado;
            }

            $texto = $idx . '. ' . $textoBase;

            $items[] = $textoBase === '' ? ($idx . '. (sin datos)') : $texto;
            $idx++;
        }

        if ($overflow > 0) {
            $items[] = '… y ' . $overflow . ' más';
        }

        $totalItems = count($items);
        $columns = (int) ceil($totalItems / max(1, $rowsPerColumn));
        $columns = min($columns, $maxColumns);

        /*
         |----------------------------------------------------------------------
         | ZPL
         |----------------------------------------------------------------------
         */
        $zpl  = "^XA\n";
        $zpl .= "^PW800\n";
        $zpl .= "^LL1200\n";
        $zpl .= "^CI28\n";
        // BORDE EXTERIOR (margen para que no se corte en impresora)
        $zpl .= "^FO20,20^GB760,1160,2^FS\n";

        // ID etiqueta
        $zpl .= "^FO50,40^A0N,28,28^FDID: {$this->zplText($idEtiqueta)}^FS\n";

        // ---- COLUMNA DERECHA (QR + ID + COMUNA) ----
        $rightX = 520;   // inicio columna derecha
        $rightW = 260;   // ancho columna derecha

        // QR centrado dentro de la columna derecha
        $qrX = $rightX + 55; // centrado visual dentro de 260 de ancho
        $zpl .= "^FO{$qrX},40^BQN,2,7\n";
        $zpl .= "^FDLA,{$this->zplText($idBulto)}^FS\n";

        // ID Bulto bajo el QR, CENTRADO
        $idText = 'ID: ' . $idBulto;
        $zpl .= "^FO{$rightX},220^A0N,20,20^FB{$rightW},1,0,C^FD{$this->zplText($idText)}^FS\n";

        // Comuna CENTRADA y SIN CORTES (wrap dinámico)
        $comunaTxt = trim($comuna);
        $comunaFont  = 34;
        $comunaLines = 2;

        if (mb_strlen($comunaTxt) > 16) { $comunaFont = 30; $comunaLines = 3; }
        if (mb_strlen($comunaTxt) > 28) { $comunaFont = 26; $comunaLines = 4; }

        $zpl .= "^FO{$rightX},260^A0N,{$comunaFont},{$comunaFont}^FB{$rightW},{$comunaLines},2,C^FD{$this->zplText($comunaTxt)}^FS\n";




        // DESTINO
        $zpl .= "^FO50,200^A0N,30,30^FDDestino^FS\n";
        $zpl .= "^FO50,240^A0N,26,26^FB450,2,4,L^FD{$this->zplText($direccion)}^FS\n";
        $zpl .= "^FO50,300^A0N,24,24^FB450,2,4,L^FD{$this->zplText($numeroDestino)}^FS\n";
        $zpl .= "^FO50,360^A0N,24,24^FB450,2,4,L^FD{$this->zplText($dptoDestino)}^FS\n";

        // Campaña
        $zpl .= "^FO50,430^A0N,24,24^FB700,2,4,L^FD{$this->zplText($campana)}^FS\n";

        // DETALLE
        $zpl .= "^FO50,520^A0N,30,30^FDDetalle dentro del sobre^FS\n";

        /**
         * Ajuste de columnas y ancho por cantidad real de columnas
         * (evita wrap y solapes)
         */
        if ($columns === 1) {
            $columnX = [50];
            $colWidth = 700;
        } elseif ($columns === 2) {
            $columnX = [50, 420];
            $colWidth = 330;
        } else {
            $columnX = [50, 290, 530];
            $colWidth = 230;
        }

        $itemIndex = 0;

        for ($col = 0; $col < $columns; $col++) {
            $x = $columnX[$col] ?? $columnX[array_key_last($columnX)];
            $y = $startY;

            for ($r = 0; $r < $rowsPerColumn; $r++) {
                if (!isset($items[$itemIndex])) {
                    break;
                }

                // Forzar 1 línea real (truncate + el ^FB a 1 línea)
                $texto = $this->truncateForColumn((string) $items[$itemIndex], $colWidth, $fontSize);

                $zpl .= "^FO{$x},{$y}";
                $zpl .= "^A0N,{$fontSize}," . max(10, $fontSize - 2); // un poquito más angosto
                $zpl .= "^FB{$colWidth},1,0,L,0";
                $zpl .= "^FD{$this->zplText($texto)}^FS\n";

                $y += $lineHeight;
                $itemIndex++;
            }
        }

        // Línea + total
        $zpl .= "^FO50,920^GB700,2,2^FS\n";
        $zpl .= "^FO50,940^A0N,28,28^FDTotal: {$totalRevistas} revista(s)^FS\n";
        $zpl .= "^XZ\n";

        return $zpl;
    }

    protected function zplText(string $text): string
    {
        $text = trim($text);

        // Quitar controles y normalizar espacios
        $text = preg_replace("/[\r\n\t]+/", ' ', $text);
        $text = preg_replace('/[[:cntrl:]]/u', ' ', $text);

        // Evitar comandos ZPL accidentales
        $text = str_replace(['^', '~'], ' ', $text);

        // Colapsar espacios múltiples
        $text = preg_replace('/\s{2,}/', ' ', $text);

        // Convertir a UTF-8 (por si viene ISO/Windows-1252 desde Excel)
        return mb_convert_encoding($text, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
    }

    protected function truncateForColumn(string $text, int $colWidth, int $fontSize): string
    {
        $text = trim($text);

        if ($text === '') {
            return '(sin datos)';
        }

        $approxCharWidth = max(6, (int) round($fontSize * 0.55));
        $maxChars = (int) floor(($colWidth - 12) / $approxCharWidth);
        $maxChars = max(10, $maxChars);

        if (mb_strlen($text) <= $maxChars) {
            return $text;
        }

        // Mantener inicio y final (más informativo)
        $keepStart = (int) floor(($maxChars - 1) * 0.6);
        $keepEnd   = $maxChars - 1 - $keepStart;

        return rtrim(mb_substr($text, 0, $keepStart)) . '…' . ltrim(mb_substr($text, -$keepEnd));
    }
}
