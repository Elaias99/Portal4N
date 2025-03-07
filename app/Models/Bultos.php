<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bultos extends Model
{
    use HasFactory;

    protected $table = 'bultos';

    protected $fillable = ['codigo_bulto', 'direccion', 'comuna', 'fecha_carga', 'estado'];

    // Relación con Reclamos (Un bulto puede tener muchos reclamos)
    public function reclamos()
    {
        return $this->hasMany(Reclamos::class, 'id_bulto');
    }

}
