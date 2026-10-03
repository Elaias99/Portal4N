<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/* El cierre de un período. No se modifica ni se borra (ver la migración). */
class CourierCierre extends Model
{
    public $timestamps = false;

    protected $table = 'courier_cierres';

    protected $fillable = [
        'courier_periodo_id',
        'registros',
        'ordenes_compra',
        'neto',
        'iva',
        'retencion',
        'total',
        'user_id',
        'closed_at',
    ];

    protected $casts = [
        'registros' => 'integer',
        'ordenes_compra' => 'integer',
        'neto' => 'integer',
        'iva' => 'integer',
        'retencion' => 'integer',
        'total' => 'integer',
        'closed_at' => 'datetime',
    ];
}
