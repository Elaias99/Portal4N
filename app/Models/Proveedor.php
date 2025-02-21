<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory;

    // Nombre explícito de la tabla
    protected $table = 'proveedores';

    // Campos que se pueden asignar en masa
    protected $fillable = [
        'razon_social', //Nombre legal de la empresa proveedora. Fundamental para identificarla en contratos y operaciones.

        'rut', //Identificación tributaria del proveedor. Se usa para cumplir con obligaciones legales y fiscales.
        'banco',
        'tipo_cuenta',
        'nro_cuenta',
        'tipo_pago',


        'telefono_empresa', //Teléfono principal para consultas o negociaciones.

        'Nombre_RepresentanteLegal',
        'Rut_RepresentanteLegal',
        'Telefono_RepresentanteLegal',
        'Correo_RepresentanteLegal',

        'contacto_nombre',
        'contacto_telefono',
        'contacto_correo',
        'nombre_contacto2',
        'telefono_contacto2',
        'correo_contacto2',
        'cargo_contacto1',
        'cargo_contacto2',

        'giro_comercial', // Actividad económica principal del proveedor. Ayuda a clasificar y entender su rol en el negocio.
        'direccion_facturacion',
        'direccion_despacho',
        'comuna_empresa',
        'correo_banco',
        'nombre_razon_social_banco',
        'rut_banco',
    ];

    public function compras()
    {
        return $this->hasMany(Compra::class);
    }
}
