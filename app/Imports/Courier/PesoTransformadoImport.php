<?php

namespace App\Imports\Courier;

use App\Models\CourierPesoTransformado;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

/*
 * Hoja PesoTransformado → courier_pesos_transformados.
 * Columnas: A texto tal como lo escribe Geolice ("2.48 kg", "X") · B entero
 *
 * Todo lo que viene de la hoja se marca origen = catalogo, incluidas las
 * 60 filas que en la réplica se calcularon con la regla: ya son parte
 * del catálogo oficial de la planilla.
 */
class PesoTransformadoImport implements ToCollection, WithCalculatedFormulas
{
    public array $resumen = [
        'filas' => 0,
        'nuevos' => 0,
        'actualizados' => 0,
        'sin_cambio' => 0,
        'sin_peso' => 0,
    ];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $i => $row) {
            if ($i === 0) {
                continue;
            }

            $texto = (string) ($row[0] ?? '');

            if (trim($texto) === '') {
                continue;
            }

            $this->resumen['filas']++;

            if (! is_numeric($row[1] ?? null)) {
                $this->resumen['sin_peso']++;

                continue;
            }

            $registro = CourierPesoTransformado::updateOrCreate(
                ['texto' => $texto],
                [
                    'peso' => max(1, (int) $row[1]),
                    'origen' => CourierPesoTransformado::ORIGEN_CATALOGO,
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
