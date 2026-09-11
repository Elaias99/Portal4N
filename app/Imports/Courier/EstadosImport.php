<?php

namespace App\Imports\Courier;

use App\Models\CourierEstadoEntrega;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

/*
 * Hoja Estados → courier_estados_entrega.
 * Columnas: A Estados · B Considerar (PAGAR / DESCONTAR)
 */
class EstadosImport implements ToCollection, WithCalculatedFormulas
{
    public array $resumen = [
        'filas' => 0,
        'nuevos' => 0,
        'actualizados' => 0,
        'sin_cambio' => 0,
    ];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $i => $row) {
            if ($i === 0) {
                continue;
            }

            $estado = trim((string) ($row[0] ?? ''));

            if ($estado === '') {
                continue;
            }

            $this->resumen['filas']++;

            $considerar = mb_strtoupper(trim((string) ($row[1] ?? '')));

            $registro = CourierEstadoEntrega::updateOrCreate(
                ['estado' => $estado],
                [
                    'considerar' => $considerar === CourierEstadoEntrega::DESCONTAR
                        ? CourierEstadoEntrega::DESCONTAR
                        : CourierEstadoEntrega::PAGAR,
                ]
            );

            if ($registro->wasRecentlyCreated) {
                $this->resumen['nuevos']++;
            } elseif ($registro->wasChanged()) {
                $this->resumen['actualizados']++;
            } else {
                $this->resumen['sin_cambio']++;
            }
        }
    }
}
