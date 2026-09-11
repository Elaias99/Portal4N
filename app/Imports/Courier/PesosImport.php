<?php

namespace App\Imports\Courier;

use App\Models\CourierTarifa;
use App\Models\CourierTarifaTramo;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

/*
 * Hoja Pesos → courier_tarifas + courier_tarifa_tramos.
 *
 * La hoja es una matriz:
 *   fila 1: encabezado ("KILOS/DETALLE", nombres de tabla)
 *   fila 2: kilo adicional por tabla
 *   fila 3: número de tabla (0–16)
 *   filas 4–23: A = peso 1..20, B..R = valor de cada tabla
 *   filas 24+: proyección valor(20) + (peso-20) × kilo adicional — no se guarda
 *
 * Los índices del array son 0-based: fila 1 → $rows[0], columna A → $row[0].
 */
class PesosImport implements ToCollection, WithCalculatedFormulas
{


    public array $resumen = [
        'tarifas_nuevas' => 0,
        'tarifas_actualizadas' => 0,
        'tarifas_sin_cambio' => 0,
        'tramos_escritos' => 0,
    ];

    public function collection(Collection $rows): void
    {
        $encabezado = $rows[0];
        $kiloAdicional = $rows[1];
        $numeroTabla = $rows[2];

        // Columnas B..R → índices 1..17
        for ($col = 1; $col <= 17; $col++) {
            $numero = $numeroTabla[$col] ?? null;

            if (! is_numeric($numero)) {
                continue;
            }

            $numero = (int) $numero;

            $nombre = trim((string) ($encabezado[$col] ?? ''));

            if ($nombre === '') {
                $nombre = $numero === 0 ? 'TABLA 0 (sin tarifa)' : "TABLA {$numero}";
            }

            $tarifa = CourierTarifa::updateOrCreate(
                ['numero' => $numero],
                [
                    'nombre' => $nombre,
                    'kilo_adicional' => is_numeric($kiloAdicional[$col] ?? null)
                        ? (int) $kiloAdicional[$col]
                        : 0,
                ]
            );

            if ($tarifa->wasRecentlyCreated) {
                $this->resumen['tarifas_nuevas']++;
            } elseif ($tarifa->wasChanged()) {
                $this->resumen['tarifas_actualizadas']++;
            } else {
                $this->resumen['tarifas_sin_cambio']++;
            }

            // Filas 4..23 → índices 3..22, pesos 1..20
            for ($fila = 3; $fila <= 22; $fila++) {
                $peso = $rows[$fila][0] ?? null;
                $valor = $rows[$fila][$col] ?? null;

                if (! is_numeric($peso) || ! is_numeric($valor)) {
                    continue;
                }

                CourierTarifaTramo::updateOrCreate(
                    [
                        'courier_tarifa_id' => $tarifa->id,
                        'peso' => (int) $peso,
                    ],
                    ['valor' => (int) $valor]
                );

                $this->resumen['tramos_escritos']++;
            }
        }
    }
}