<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/*
 * Una regla de Apoyo Alza, sin período. Ver la migración
 * create_courier_apoyo_alza_reglas_table.
 */
class CourierApoyoAlzaRegla extends Model
{
    protected $table = 'courier_apoyo_alza_reglas';

    protected $fillable = [
        'fila_origen',
        'proveedor',
        'rut_proveedor',
        'zona',
        'proceso_base',
        'servicio_acuerdo',
        'factor',
        'porcentaje',
        'monto_dia',
        'empresa_mandante',
        'agencia',
        'archivo_origen',
    ];

    protected $casts = [
        'fila_origen' => 'integer',
        'monto_dia' => 'integer',
    ];
}
