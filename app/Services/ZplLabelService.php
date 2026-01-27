<?php

namespace App\Services;

class ZplLabelService
{
    /**
     * Genera ZPL para 1 etiqueta de 70x30 mm (ZD230 203dpi).
     */
    public function makeLabel(array $row): string
    {
        $qr        = (string) ($row['QR'] ?? '');
        $atencion  = (string) ($row['Atencion'] ?? '');
        $direccion = (string) ($row['Direccion'] ?? '');
        $comuna    = (string) ($row['Comuna'] ?? '');

        // ZPL base: 70mm x 30mm -> 560 x 240 dots (203dpi)
        return "^XA\n"
            . "^PW560\n"
            . "^LL240\n"
            . "^CI28\n"
            . "^FO20,20^A0N,30,30^FD{$this->zplText($atencion)}^FS\n"
            . "^FO20,55^A0N,22,22^FD{$this->zplText($direccion)}^FS\n"
            . "^FO20,85^A0N,22,22^FD{$this->zplText($comuna)}^FS\n"
            . "^FO20,115^A0N,20,20^FD{$this->zplText($qr)}^FS\n"
            . "^FO360,20^BQN,2,6^FDLA,{$this->zplText($qr)}^FS\n"
            . "^XZ\n";
    }

    /**
     * Escapa caracteres problemáticos para ZPL.
     */
    private function zplText(string $text): string
    {
        $text = trim($text);
        $text = preg_replace("/\r|\n|\t/", ' ', $text);
        $text = str_replace(['^', '~'], ' ', $text);

        return $text;
    }

}
