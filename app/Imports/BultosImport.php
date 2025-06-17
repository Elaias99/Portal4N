<?php

namespace App\Imports;

use App\Models\Bultos;
use App\Models\Comuna;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BultosImport implements ToModel, WithHeadingRow
{
    protected $comunas = [];

    public function __construct()
    {
        // Carga todas las comunas una vez (nombre en minúsculas => ID)
        $this->comunas = Comuna::all()->pluck('id', 'Nombre')->mapWithKeys(function ($id, $nombre) {
            return [strtolower(trim($nombre)) => $id];
        })->toArray();
    }

    public function model(array $row)
    {
        return Bultos::updateOrCreate(
            ['codigo_bulto' => $row['id_bulto']],
            [
                'id_envio'       => $row['id_envio_id_solicitud'] ?? null,
                'atencion'       => $row['atencion'] ?? null,
                'numero_destino' => $row['numero_destino'] ?? null,
                'depto_destino'  => $row['depto_destino'] ?? null,
                'direccion'      => $row['direccion'] ?? null,
                'comuna_id'      => $this->getComunaId($row['comuna_destino']),

                'razon_social'   => $row['razon_social'] ?? null,
                'fecha_entrega'  => isset($row['fecha_entrega']) ? 
                                    \Carbon\Carbon::parse($row['fecha_entrega'])->format('Y-m-d') : null,
                'ubicacion'      => $row['ubicacion'] ?? null,
                'nombre_campana' => $row['nombre_campana'] ?? null,
                'descripcion_bulto' => $row['descripcion_bulto'] ?? null,
                'observacion'    => $row['observacion'] ?? null,
                'referencia'     => $row['referencia'] ?? null,
                'peso'           => isset($row['peso']) ? floatval($row['peso']) : null,
                'telefono'       => $row['telefono'] ?? null,
                'mail'           => $row['mail'] ?? null,
                'unidad'         => $row['unidad'] ?? null,
                'fecha_carga'    => now(),
                'estado'         => 'pendiente',
                'id_jefe'        => null,
            ]
        );
    }

    private function getComunaId($nombreComuna)
    {
        return $this->comunas[strtolower(trim($nombreComuna))] ?? null;
    }
}

