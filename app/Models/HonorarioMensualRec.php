<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HonorarioMensualRec extends Model
{
    use HasFactory;

    protected $table = 'honorarios_mensuales_rec';

    protected $fillable = [
        'rut_contribuyente',
        'razon_social',
        'anio',
        'mes',
        'folio',
        'fecha_emision',
        'estado',
        'fecha_anulacion',
        'rut_emisor',
        'razon_social_emisor',
        'sociedad_profesional',

        'monto_bruto',
        'monto_retenido',
        'monto_pagado',
    ];

    protected $casts = [
        'fecha_emision'   => 'date',
        'fecha_anulacion' => 'date',
    ];

}