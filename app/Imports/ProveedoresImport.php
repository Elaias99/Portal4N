<?php

namespace App\Imports;
use App\Models\Banco;
use App\Models\Proveedor;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProveedoresImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Proveedor([
            'razon_social' => $row['razon_social'],
            'rut' => $row['rut'],
            'banco_id' => $this->getBancoId($row['banco']),

            'banco' => $row['banco'],
            'tipo_cuenta' => $row['tipo_cuenta'],
            'nro_cuenta' => $row['nro_cuenta'],
            'tipo_pago' => $row['tipo_pago'],
            'telefono_empresa' => $row['telefono_empresa'] ?? 'N/A', // 👈 Si es NULL, lo cambia a 'N/A'
            'Nombre_RepresentanteLegal' => $row['nombre_representantelegal'] ?? 'N/A',
            'Rut_RepresentanteLegal' => $row['rut_representantelegal'] ?? 'N/A',
            'Telefono_RepresentanteLegal' => $row['telefono_representantelegal'] ?? 'N/A',
            'Correo_RepresentanteLegal' => $row['correo_representantelegal'] ?? 'N/A',
            'contacto_nombre' => $row['contacto_nombre'] ?? 'N/A',
            'contacto_telefono' => $row['contacto_telefono'] ?? 'N/A',
            'contacto_correo' => $row['contacto_correo'] ?? 'N/A',
            'giro_comercial' => $row['giro_comercial'] ?? 'N/A',
            'direccion_facturacion' => $row['direccion_facturacion'] ?? 'N/A',
            'direccion_despacho' => $row['direccion_despacho'] ?? 'N/A',
            'comuna_empresa' => $row['comuna_empresa'] ?? 'N/A',
            'nombre_contacto2' => $row['nombre_contacto2'] ?? 'N/A',
            'telefono_contacto2' => $row['telefono_contacto2'] ?? 'N/A',
            'correo_contacto2' => $row['correo_contacto2'] ?? 'N/A',
            'correo_banco' => $row['correo_banco'] ?? 'N/A',
            'nombre_razon_social_banco' => $row['nombre_razon_social_banco'] ?? 'N/A',
            'cargo_contacto1' => $row['cargo_contacto1'] ?? 'N/A',
            'cargo_contacto2' => $row['cargo_contacto2'] ?? 'N/A',
        ]);
    }

    private function getBancoId($nombreBanco)
    {
        if (!$nombreBanco) {
            return null; // Si el campo está vacío en el Excel, dejar banco_id como NULL
        }

        // 🔹 Normalizar el nombre: Convertir a mayúsculas, quitar espacios extra y caracteres especiales
        $nombreBanco = strtoupper(trim($nombreBanco));
        $nombreBanco = str_replace(['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'], ['A', 'E', 'I', 'O', 'U', 'N'], $nombreBanco);
        
        // 🔹 Manejo de errores tipográficos comunes (corrección manual)
        $correcciones = [
            'BANCOESTADO' => 'BANCO ESTADO',
            'BANCOCHILE' => 'BANCO CHILE',
            'BANCOCHLE' => 'BANCO CHILE',
            'BANCOFALABELLA' => 'BANCO FALABELLA',
            'BANCOBCI' => 'BANCO BCI',
            'BANCOBICE' => 'BANCO BICE',
        ];

        if (isset($correcciones[$nombreBanco])) {
            $nombreBanco = $correcciones[$nombreBanco];
        }

        // 🔹 Buscar si el banco ya existe con una coincidencia flexible
        $banco = Banco::where('nombre', 'LIKE', "%$nombreBanco%")->first();

        // 🔹 Si existe, devolver el ID; si no, crearlo y devolver el nuevo ID
        return $banco ? $banco->id : Banco::create(['nombre' => $nombreBanco])->id;
    }



}

