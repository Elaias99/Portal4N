<?php

namespace App\Services;

use Illuminate\Support\Collection;

class EtiquetaGrandeService
{
    public function makeLabel(Collection $rows): string
    {
        $row = $rows->first();

        // Campos
        $idEtiqueta    = (string) ($row['id'] ?? '');
        $idBulto       = (string) ($row['id_bulto'] ?? '');
        $direccion     = (string) ($row['direccion'] ?? '');
        $numeroDestino = (string) ($row['numero_destino'] ?? '');
        $dptoDestino   = (string) ($row['depto_destino'] ?? '');
        $comuna        = (string) ($row['comuna_destino'] ?? '');
        $campana       = (string) ($row['nombre_campana'] ?? '');


        $totalRevistas = $rows->count();

        /*
         |--------------------------------------------------------------------------
         | Ajuste dinámico según cantidad
         |--------------------------------------------------------------------------
         */
        if ($totalRevistas <= 12) {
            $font = 26;
            $leading = 4;
        } elseif ($totalRevistas <= 20) {
            $font = 24;
            $leading = 3;
        } elseif ($totalRevistas <= 30) {
            $font = 22;
            $leading = 2;
        } else {
            // caso extremo, sigue cabiendo pero compacto
            $font = 20;
            $leading = 1;
        }

        /*
         |--------------------------------------------------------------------------
         | Construir listado COMPLETO (orden intacto)
         |--------------------------------------------------------------------------
         */
        $lines = [];
        $index = 1;

        foreach ($rows as $item) {
            $lines[] = $index . '. ' . ($item['nombre'] ?? '');
            $index++;
        }

        $listado = implode("\n", $lines);

        /*
         |--------------------------------------------------------------------------
         | ZPL
         |--------------------------------------------------------------------------
         */
        $zpl  = "^XA\n";
        $zpl .= "^PW800\n";
        $zpl .= "^LL1200\n";
        $zpl .= "^CI28\n";

        // ID etiqueta (correlativo)
        $zpl .= "^FO50,40^A0N,28,28^FDID: {$this->zplText($idEtiqueta)}^FS\n";


        // ZONA A – QR
        $zpl .= "^FO600,40^BQN,2,7\n";
        $zpl .= "^FDLA,{$this->zplText($idBulto)}^FS\n";
        // Texto ID Bulto debajo del QR (fallback manual)
        $zpl .= "^FO600,220^A0N,28,28^FDID: {$this->zplText($idBulto)}^FS\n";

        // ZONA B – DESTINO
        $zpl .= "^FO50,200^A0N,30,30^FDDestino^FS\n";
        $zpl .= "^FO50,240^A0N,26,26^FB520,2,4,L^FD{$this->zplText($direccion)}^FS\n";
        $zpl .= "^FO50,300^A0N,24,24^FB520,2,4,L^FD{$this->zplText($numeroDestino)}^FS\n";
        $zpl .= "^FO50,360^A0N,24,24^FB520,2,4,L^FD{$this->zplText($dptoDestino)}^FS\n";

        // Comuna destino (destacada)
        $zpl .= "^FO600,280^A0N,48,48^FD{$this->zplText($comuna)}^FS\n";


        // Campaña
        $zpl .= "^FO50,430^A0N,24,24^FB700,2,4,L^FD{$this->zplText($campana)}^FS\n";

        /**
         * ZONA C – Detalle dentro del sobre (LISTADO VERTICAL EN COLUMNAS)
         */
        $zpl .= "^FO50,520^A0N,30,30^FDDetalle dentro del sobre^FS\n";

        $startY = 560;
        $lineHeight = 28;
        $fontSize = 22;

        $rowsPerColumn = 12;
        $total = $rows->count();
        $columns = (int) ceil($total / $rowsPerColumn);

        $columnX = [50, 280, 510]; // hasta 3 columnas en 10x15

        $index = 1;

        for ($col = 0; $col < $columns; $col++) {
            $y = $startY;
            $x = $columnX[$col];

            for ($i = 0; $i < $rowsPerColumn; $i++) {
                $rowIndex = ($col * $rowsPerColumn) + $i;

                if (!isset($rows[$rowIndex])) {
                    break;
                }

                $grado  = $rows[$rowIndex]['grado'] ?? '';
                $nombre = $rows[$rowIndex]['nombre'] ?? '';

                $texto = $index . '. ' . trim($grado . ' ' . $nombre);


                $zpl .= "^FO{$x},{$y}";
                $zpl .= "^A0N,{$fontSize},{$fontSize}";
                $zpl .= "^FD{$this->zplText($texto)}^FS\n";

                $y += $lineHeight;
                $index++;
            }
        }


        // FIX

        $zpl .= "^FO50,920^GB700,2,2^FS\n";
        $zpl .= "^FO50,940^A0N,28,28^FDTotal: {$totalRevistas} revista(s)^FS\n";
        $zpl .= "^XZ\n";




        return $zpl;
    }

    protected function zplText(string $text): string
    {
        $text = trim($text);
        $text = preg_replace("/\r|\n|\t/", ' ', $text);
        $text = str_replace(['^', '~'], ' ', $text);

        return mb_convert_encoding($text, 'UTF-8', 'UTF-8, ISO-8859-1');
    }
}
