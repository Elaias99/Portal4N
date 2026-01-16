<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HonorarioMensualRecTotal extends Model
{
    use HasFactory;

    protected $table = 'honorarios_mensuales_rec_totales';

    protected $fillable = [
        'rut_contribuyente',
        'razon_social',
        'anio',
        'mes',
        'monto_bruto',
        'monto_retenido',
        'monto_pagado',
    ];
}
