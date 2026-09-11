<?php

namespace App\Imports\Courier;

use App\Models\CourierAgentes;
use App\Models\CourierConfiguracion;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

/*
 * Hoja PagosCentroCostos → courier_configuracions.
 *
 * Columnas: A Agente · B Comerciante · C Servicio · D Llave · E Pagar · F Tabla
 *
 * El agente se resuelve por nombre contra courier_agentes, que ya cargó
 * la hoja Operador. Si no existe, la fila se reporta y NO se crea el
 * agente: un agente sin cobertura sería un dato a medias.
 */
class PagosCentroCostosImport implements ToCollection, WithCalculatedFormulas
{
    public array $resumen = [
        'filas' => 0,
        'nuevas' => 0,
        'actualizadas' => 0,
        'sin_cambio' => 0,
        'agentes_no_encontrados' => 0,
    ];

    /** @var array<string,int> nombres de agente que no existen y cuántas filas tenían */
    public array $agentesNoEncontrados = [];

    public function collection(Collection $rows): void
    {
        $agentes = CourierAgentes::pluck('id', 'nombre');

        foreach ($rows as $i => $row) {
            if ($i === 0) {
                continue;
            }

            $nombreAgente = trim((string) ($row[0] ?? ''));

            if ($nombreAgente === '') {
                continue;
            }

            $this->resumen['filas']++;

            $agenteId = $agentes->get($nombreAgente);

            if ($agenteId === null) {
                $this->resumen['agentes_no_encontrados']++;
                $this->agentesNoEncontrados[$nombreAgente] = ($this->agentesNoEncontrados[$nombreAgente] ?? 0) + 1;

                continue;
            }

            $comerciante = (string) ($row[1] ?? '');
            $servicio = (string) ($row[2] ?? '');
            $pagar = mb_strtoupper(trim((string) ($row[4] ?? '')));
            $tabla = is_numeric($row[5] ?? null) ? (int) $row[5] : null;

            if (! in_array($pagar, ['SI', 'NO', 'REVISAR'], true)) {
                $pagar = 'REVISAR';
            }

            $configuracion = CourierConfiguracion::updateOrCreate(
                ['llave' => CourierConfiguracion::llave($nombreAgente, $comerciante, $servicio)],
                [
                    'courier_agente_id' => $agenteId,
                    'comerciante' => $comerciante,
                    'servicio' => $servicio,
                    'pagar' => $pagar,
                    'tabla' => $tabla,
                    'activo' => true,
                ]
            );

            if ($configuracion->wasRecentlyCreated) {
                $this->resumen['nuevas']++;
            } elseif ($configuracion->wasChanged()) {
                $this->resumen['actualizadas']++;
            } else {
                $this->resumen['sin_cambio']++;
            }
        }
    }
}