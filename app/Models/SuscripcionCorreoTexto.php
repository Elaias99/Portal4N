<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuscripcionCorreoTexto extends Model
{
    protected $table = 'suscripcion_correo_textos';

    protected $fillable = [
        'anio',
        'mes',
        'cuerpo',
    ];

    protected $casts = [
        'anio' => 'integer',
        'mes' => 'integer',
    ];
}
