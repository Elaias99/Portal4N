<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ManifiestoMultiExport implements WithMultipleSheets
{
    protected $manifiestos;
    protected $duplicados;
    protected $hermanos;
    protected $headers;
    protected $fecha;

    public function __construct(array $manifiestos, array $duplicados, array $hermanos, array $headers, string $fecha)
    {
        $this->manifiestos = $manifiestos;
        $this->duplicados = $duplicados;
        $this->hermanos = $hermanos;
        $this->headers = $headers;
        $this->fecha = $fecha;
    }

    public function sheets(): array
    {
        return [
            new ManifiestosExport($this->headers, $this->manifiestos),
            new ManifiestosExport(
                ['Fecha', '# Bulto', 'Código', 'Tamaño', 'Atención', 'Comuna', 'Area'], // solo encabezados base
                $this->duplicados
            ),
            new HermanosExport($this->hermanos),
        ];

    }
}
