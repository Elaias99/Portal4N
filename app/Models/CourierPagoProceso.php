<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/*
 * Un pago del mes de Ruta CV, Servicios, Visitas, Especiales o Apoyo
 * Alza. Ver la migración create_courier_pagos_proceso_table.
 */
class CourierPagoProceso extends Model
{
    public const RUTA_CV = 'Ruta CV';
    public const SERVICIOS = 'Servicios';
    public const VISITAS = 'Visitas';
    public const ESPECIALES = 'Especiales';
    public const APOYO_ALZA = 'Apoyo Alza';

    /* Nombre en el comando → proceso. */
    public const PROCESOS = [
        'ruta-cv' => self::RUTA_CV,
        'servicios' => self::SERVICIOS,
        'visitas' => self::VISITAS,
        'especiales' => self::ESPECIALES,
        'apoyo-alza' => self::APOYO_ALZA,
    ];

    protected $table = 'courier_pagos_proceso';

    protected $fillable = [
        'courier_periodo_id',
        'proceso',
        'fila_origen',
        'proveedor',
        'rut_proveedor',
        'courier_proveedor_id',
        'zona',
        'concepto',
        'valor_unitario',
        'cantidad',
        'total',
        'dias',
        'datos',
        'archivo_origen',
    ];

    protected $casts = [
        'valor_unitario' => 'integer',
        'cantidad' => 'integer',
        'total' => 'integer',
        'dias' => 'array',
        'datos' => 'array',
    ];

    public function scopeDelPeriodo(Builder $query, int $periodoId): Builder
    {
        return $query->where('courier_periodo_id', $periodoId);
    }
}
