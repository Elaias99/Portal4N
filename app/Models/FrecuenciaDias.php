<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FrecuenciaDias extends Model
{
    use HasFactory;

    protected $table = 'frecuencia_dias';

    protected $fillable = [
        'nombre',
        'lunes',
        'martes',
        'miercoles',
        'jueves',
        'viernes',
        'sabado',
        'domingo',
    ];


    public function clasificaciones()
    {
        return $this->hasMany(ComunaClasificacionOperativa::class);
    }



}
