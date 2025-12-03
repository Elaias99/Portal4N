<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomaticEmail extends Model
{
    protected $fillable = [
        'nombre',
        'asunto',
        'cuerpo_html',
        'destinatarios',
        'tipo_frecuencia',
        'hora_envio',
        'dias_semana',
        'activo',
    ];

    protected $casts = [
        'dias_semana' => 'array',
        'activo' => 'boolean',
    ];
}
