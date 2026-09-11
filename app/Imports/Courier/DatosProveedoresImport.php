<?php

namespace App\Imports\Courier;

use App\Models\CourierProveedor;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

/*
 * Hoja DatosProveedores → courier_proveedores.
 *
 * Columnas: A Operador · B Usuario · C Llave (=A&B, no se lee)
 *           D Transportista · E Razon Social · F Rut · G Empresa
 *           H TIPO_DOCUMENTO · I TITULAR_BANCO · J RUT_TITULAR_BANCO
 *           K BANCO · L TIPO_CUENTA · M NRO_CUENTA
 *
 * El usuario NO se recorta: la hoja tiene nombres con espacio final que
 * calzan con lo que Geolice envía en "Nombre del repartidor".
 */
class DatosProveedoresImport implements ToCollection, WithCalculatedFormulas
{
    public array $resumen = [
        'filas' => 0,
        'nuevos' => 0,
        'actualizados' => 0,
        'sin_cambio' => 0,
        'llaves_repetidas' => 0,
    ];

    public function collection(Collection $rows): void
    {
        $vistas = [];

        foreach ($rows as $i => $row) {
            if ($i === 0) {
                continue;
            }

            $operador = trim((string) ($row[0] ?? ''));

            if ($operador === '') {
                continue;
            }

            $this->resumen['filas']++;

            $usuario = (string) ($row[1] ?? '');
            $llave = CourierProveedor::llave($operador, $usuario);

            if (isset($vistas[$llave])) {
                // Igual que el BUSCARV: manda la primera aparición.
                $this->resumen['llaves_repetidas']++;

                continue;
            }

            $vistas[$llave] = true;

            $proveedor = CourierProveedor::updateOrCreate(
                ['llave' => $llave],
                [
                    'operador' => $operador,
                    'usuario' => $usuario,
                    'transportista' => $this->limpiar($row[3] ?? null),
                    'razon_social' => trim((string) ($row[4] ?? '')) ?: 'No Aplica',
                    'rut' => $this->limpiar($row[5] ?? null),
                    'empresa' => $this->limpiar($row[6] ?? null),
                    'tipo_documento' => trim((string) ($row[7] ?? '')) ?: 'Sin Documento',
                    'titular_banco' => $this->limpiar($row[8] ?? null),
                    'rut_titular_banco' => $this->limpiar($row[9] ?? null),
                    'banco' => $this->limpiar($row[10] ?? null),
                    'tipo_cuenta' => $this->limpiar($row[11] ?? null),
                    'nro_cuenta' => $this->limpiar($row[12] ?? null),
                    'activo' => true,
                ]
            );

            if ($proveedor->wasRecentlyCreated) {
                $this->resumen['nuevos']++;
            } elseif ($proveedor->wasChanged()) {
                $this->resumen['actualizados']++;
            } else {
                $this->resumen['sin_cambio']++;
            }
        }
    }

    /*
     * "N/A", "-" y vacío se guardan como NULL.
     */
    private function limpiar(mixed $valor): ?string
    {
        $texto = trim((string) $valor);

        if (in_array(mb_strtoupper($texto), ['', 'N/A', 'NA', '-'], true)) {
            return null;
        }

        return $texto;
    }
}