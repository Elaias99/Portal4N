<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bultos extends Model
{
    use HasFactory;

    protected $table = 'bultos';

    protected $fillable = ['codigo_bulto', 'direccion', 'comuna', 'fecha_carga', 'estado', 'id_jefe'];

    // Relación con Reclamos (Un bulto puede tener muchos reclamos)
    public function reclamos()
    {
        return $this->hasMany(Reclamos::class, 'id_bulto');
    }


    public function jefe()
    {
        return $this->belongsTo(Jefe::class, 'id_jefe');
    }
}
