<?php

namespace App\Imports\Courier;

use App\Models\CourierAgenteProveedor;
use App\Models\CourierAgentes;
use App\Models\CourierCoberturaComuna;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

/*
 * Hoja Operador → courier_agentes, courier_agente_proveedors,
 * courier_cobertura_comunas.
 *
 * Columnas (sección 2.1 del MD):
 *   A Localidad · B ComunaMatriz (agente) · C Zona
 *   D PAGAR RETORNO · E VALOR/RETORNO · F NombreProveedor
 *
 * La localidad se guarda tal cual, sin trim: "SANTIAGO " con espacio
 * no separable es una variante real que Geolice envía.
 */
class OperadorImport implements ToCollection, WithCalculatedFormulas
{
    public array $resumen = [
        'filas' => 0,
        'agentes_nuevos' => 0,
        'proveedores_nuevos' => 0,
        'comunas_nuevas' => 0,
        'comunas_actualizadas' => 0,
        'comunas_sin_cambio' => 0,
    ];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $i => $row) {
            if ($i === 0) {
                continue; // encabezado
            }

            $localidad = (string) ($row[0] ?? '');

            if ($localidad === '') {
                continue;
            }

            $this->resumen['filas']++;

            $nombreAgente = trim((string) ($row[1] ?? ''));
            $zona = trim((string) ($row[2] ?? '')) ?: null;
            $pagarRetorno = mb_strtoupper(trim((string) ($row[3] ?? ''))) === 'SI';
            $valorRetorno = is_numeric($row[4] ?? null) ? (int) $row[4] : null;
            $nombreProveedor = trim((string) ($row[5] ?? ''));

            $agente = CourierAgentes::firstOrCreate(
                ['nombre' => $nombreAgente],
                ['activo' => true]
            );

            if ($agente->wasRecentlyCreated) {
                $this->resumen['agentes_nuevos']++;
            }

            if ($nombreProveedor !== '') {
                $proveedor = CourierAgenteProveedor::firstOrCreate(
                    [
                        'courier_agente_id' => $agente->id,
                        'nombre_proveedor' => $nombreProveedor,
                    ],
                    [
                        // El primero que aparece para el agente queda como principal.
                        'principal' => ! CourierAgenteProveedor::where('courier_agente_id', $agente->id)->exists(),
                    ]
                );

                if ($proveedor->wasRecentlyCreated) {
                    $this->resumen['proveedores_nuevos']++;
                }
            }

            $cobertura = CourierCoberturaComuna::updateOrCreate(
                ['localidad_clave' => CourierCoberturaComuna::clave($localidad)],
                [
                    'courier_agente_id' => $agente->id,
                    'localidad' => $localidad,
                    'zona' => $zona,
                    'pagar_retorno' => $pagarRetorno,
                    'valor_retorno' => $valorRetorno,
                    'activo' => true,
                ]
            );

            if ($cobertura->wasRecentlyCreated) {
                $this->resumen['comunas_nuevas']++;
            } elseif ($cobertura->wasChanged()) {
                $this->resumen['comunas_actualizadas']++;
            } else {
                $this->resumen['comunas_sin_cambio']++;
            }
        }
    }
}