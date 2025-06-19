<?php

namespace App\Imports;

use App\Models\Bultos;
use App\Models\Comuna;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BultosImport implements ToCollection, WithHeadingRow
{
    protected $comunas = [];
    public $insertados = 0;
    public $duplicados = 0;

    public function __construct()
    {
        // Precarga todas las comunas
        $this->comunas = Comuna::all()->pluck('id', 'Nombre')->mapWithKeys(function ($id, $nombre) {
            return [strtolower(trim($nombre)) => $id];
        })->toArray();
    }

    public function collection(Collection $rows)
    {
        // Precargar códigos ya existentes (en minúsculas y sin espacios)
        $existentes = Bultos::pluck('codigo_bulto')->map(function ($codigo) {
            return strtolower(trim($codigo));
        })->toArray();

        $nuevosBultos = [];

        foreach ($rows as $row) {
            $codigo = trim(strtolower($row['id_bulto'] ?? ''));

            if (empty($codigo)) {
                continue; // omitir vacíos
            }

            if (in_array($codigo, $existentes)) {
                $this->duplicados++;
                continue;
            }

            $nuevosBultos[] = [
                'codigo_bulto'   => $codigo,
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
                'created_at'     => now(),
                'updated_at'     => now(),
            ];

            $this->insertados++;
        }

        // Insertar en lote si hay nuevos
        if (!empty($nuevosBultos)) {
            DB::table('bultos')->insert($nuevosBultos);
        }
    }

    private function getComunaId($nombreComuna)
    {
        return $this->comunas[strtolower(trim($nombreComuna))] ?? null;
    }
}
