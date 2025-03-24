<?php

namespace App\Imports;
use App\Models\Banco;
use App\Models\Proveedor;
use App\Models\TipoCuenta;
use App\Models\TipoPago;
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
            'tipo_cuenta_id' => $this->getTipoCuentaId($row['tipo_cuenta']),

            'nro_cuenta' => $row['nro_cuenta'],
            
            'tipo_pago_id' => $this->getTipoPagoId($row['tipo_pago']),

            'telefono_empresa' => $row['telefono_empresa'] ?? 'N/A',
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

            

            'nombre_contacto2' => $row['nombre_contacto2'] ?? 'N/A',
            'telefono_contacto2' => $row['telefono_contacto2'] ?? 'N/A',
            'correo_contacto2' => $row['correo_contacto2'] ?? 'N/A',
            'correo_banco' => $row['correo_banco'] ?? 'N/A',
            'nombre_razon_social_banco' => $row['nombre_razon_social_banco'] ?? 'N/A',
            'cargo_contacto1' => $row['cargo_contacto1'] ?? 'N/A',
            'cargo_contacto2' => $row['cargo_contacto2'] ?? 'N/A',

            'comuna_id' => $this->getComunaId($row['comuna_empresa'] ?? null),
        ]);
    }

    public function getBancoId($nombreBanco)
    {
        if (!$nombreBanco) {
            return null;
        }

        // 🔹 Si el valor recibido es un número, asumir que es un ID y verificar si existe
        if (is_numeric($nombreBanco)) {
            return Banco::find($nombreBanco)?->id; // Devolver el ID si existe en la tabla bancos
        }

        $nombreBanco = strtoupper(trim($nombreBanco));
        $nombreBanco = str_replace(['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'], ['A', 'E', 'I', 'O', 'U', 'N'], $nombreBanco);

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

        $banco = Banco::where('nombre', 'LIKE', "%$nombreBanco%")->first();

        return $banco ? $banco->id : Banco::create(['nombre' => $nombreBanco])->id;
    }

    public function getTipoCuentaId($nombreTipoCuenta)
    {
        if (!$nombreTipoCuenta) {
            return null;
        }

        // 🔹 Si el valor recibido es un número, asumir que es un ID y verificar si existe
        if (is_numeric($nombreTipoCuenta)) {
            return TipoCuenta::find($nombreTipoCuenta)?->id; // Devolver el ID si existe en la tabla
        }

        // 🔹 Normalizar el nombre: Convertir a mayúsculas, eliminar espacios extra y caracteres especiales
        $nombreTipoCuenta = strtoupper(trim(preg_replace('/\s+/', ' ', $nombreTipoCuenta))); 
        $nombreTipoCuenta = str_replace(['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'], ['A', 'E', 'I', 'O', 'U', 'N'], $nombreTipoCuenta);

        // 🔹 Corrección de nombres comunes para evitar errores
        $correcciones = [
            'CUENTA CORRIENTE' => 'Cuenta Corriente',
            'CUENTA VISTA' => 'Cuenta Vista',
            'CUENTA RUT' => 'Cuenta Rut',
            'CUENTA DE AHORRO' => 'Cuenta de Ahorro',
            'CUENTA AHORRO' => 'Cuenta de Ahorro', // 🔹 Ahora también detectará CUENTA AHORRO
            'AHORRO' => 'Cuenta de Ahorro',
        ];

        if (isset($correcciones[$nombreTipoCuenta])) {
            $nombreTipoCuenta = $correcciones[$nombreTipoCuenta];
        }

        // 🔹 Buscar si el tipo de cuenta ya existe **exactamente**
        $tipoCuenta = TipoCuenta::where('nombre', $nombreTipoCuenta)->first();

        return $tipoCuenta ? $tipoCuenta->id : TipoCuenta::create(['nombre' => $nombreTipoCuenta])->id;
    }


    public function getTipoPagoId($nombreTipoPago)
    {
        if (!$nombreTipoPago) {
            return null;
        }

        // 🔹 Si el valor recibido es un número, asumir que es un ID y verificar si existe
        if (is_numeric($nombreTipoPago)) {
            return TipoPago::find($nombreTipoPago)?->id; // Devolver el ID si existe en la tabla
        }

        // 🔹 Normalizar el nombre: Convertir a mayúsculas, eliminar espacios extra y caracteres especiales
        $nombreTipoPago = strtoupper(trim(preg_replace('/\s+/', ' ', $nombreTipoPago))); 
        $nombreTipoPago = str_replace(['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'], ['A', 'E', 'I', 'O', 'U', 'N'], $nombreTipoPago);

        // 🔹 Corrección de nombres comunes para evitar errores
        $correcciones = [
            'TRANSFERENCIA' => 'Transferencia',
            'CHEQUE' => 'Cheque',
            'EFECTIVO' => 'Efectivo',
            'FACTURA' => 'Factura',
            'BOLETA' => 'Boleta',
            'FACTURA EXENTA' => 'Factura Exenta',
            'DOCUMENTO' => 'Documento',
        ];

        if (isset($correcciones[$nombreTipoPago])) {
            $nombreTipoPago = $correcciones[$nombreTipoPago];
        }

        // 🔹 Buscar si el tipo de pago ya existe en la base de datos
        $tipoPago = TipoPago::where('nombre', $nombreTipoPago)->first();

        return $tipoPago ? $tipoPago->id : TipoPago::create(['nombre' => $nombreTipoPago])->id;
    }


    public function getComunaId($nombreComuna)
    {
        if (!$nombreComuna) {
            return null;
        }

        // 🔹 Si es numérico, lo tomamos como ID
        if (is_numeric($nombreComuna)) {
            return \App\Models\Comuna::find($nombreComuna)?->id;
        }

        // 🔹 Si no es numérico, asumimos que es nombre y buscamos por nombre
        $nombreComuna = strtoupper(trim($nombreComuna));
        $nombreComuna = str_replace(['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'], ['A', 'E', 'I', 'O', 'U', 'N'], $nombreComuna);

        $comuna = \App\Models\Comuna::where('nombre', 'LIKE', "%$nombreComuna%")->first();

        return $comuna ? $comuna->id : null;
    }





}
