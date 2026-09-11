<?php

namespace App\Imports\Courier;

use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/*
 * Recibe ['NombreDeHoja' => objetoImport] y hace que Laravel Excel
 * cargue SOLO esas hojas del libro. Es lo que permite abrir la réplica
 * de 63 MB sin leer BaseGeolize completa.
 */
class HojasImport implements WithMultipleSheets, SkipsUnknownSheets
{
    public function __construct(
        private array $hojas
    ) {
    }

    public function sheets(): array
    {
        return $this->hojas;
    }

    public function onUnknownSheet($sheetName): void
    {
        // Las hojas no pedidas se ignoran en silencio.
    }
}