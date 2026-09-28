<?php

namespace App\Imports\Courier;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

/*
 * Hace que PhpSpreadsheet cargue sólo las primeras columnas de una
 * hoja. La planilla de Operaciones tiene hojas de más de cien mil filas
 * con decenas de columnas; leerlas completas agota la memoria, y de
 * todas formas el importador sólo usa las primeras.
 */
class FiltroColumnas implements IReadFilter
{
    /** @var array<string,true> */
    private array $columnas;

    public function __construct(int $hastaColumna)
    {
        $letras = [];

        for ($i = 1; $i <= $hastaColumna; $i++) {
            $letras[\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i)] = true;
        }

        $this->columnas = $letras;
    }

    public function readCell($columnAddress, $row, $worksheetName = ''): bool
    {
        return isset($this->columnas[$columnAddress]);
    }
}
