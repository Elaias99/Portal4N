<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bultos extends Model
{
    use HasFactory;

    protected $table = 'bultos';

    protected $fillable = ['id_envio','atencion','numero_destino','depto_destino','codigo_bulto', 'direccion', 'comuna_id', 'fecha_carga', 'estado', 'id_jefe',
                           'razon_social', 'fecha_entrega','ubicacion', 'nombre_campana', 'descripcion_bulto','observacion', 'referencia','peso',
                            'telefono', 'mail', 'unidad'];

    // Relación con Reclamos (Un bulto puede tener muchos reclamos)
    public function reclamos()
    {
        return $this->hasMany(Reclamos::class, 'id_bulto');
    }


    public function jefe()
    {
        return $this->belongsTo(Jefe::class, 'id_jefe');
    }

    public function comuna()
    {
        return $this->belongsTo(Comuna::class, 'comuna_id');
    }


}
