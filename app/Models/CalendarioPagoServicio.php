<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class CalendarioPagoServicio extends Model
{
    use HasFactory;

    protected $table = 'calendario_pagos_servicios';

    protected $fillable = [
        'anio',
        'mes',
        'servicio',
        'creditos',
        'fecha_pago',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
    ];
}
