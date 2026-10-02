<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/*
 * Cuántos días se pagan de un servicio en el mes (matriz de la hoja
 * Calendario): un número fijo, o los días de la semana que trabaja.
 */
class CourierAcuerdoRegla extends Model
{
    public const FIJO = 'fijo';
    public const DIAS_SEMANA = 'dias_semana';

    protected $table = 'courier_acuerdo_reglas';

    protected $fillable = [
        'courier_periodo_id',
        'servicio',
        'modo',
        'dias_semana',
        'cantidad_fija',
    ];

    protected $casts = [
        'dias_semana' => 'array',
        'cantidad_fija' => 'integer',
    ];

    public function scopeDelPeriodo(Builder $query, int $periodoId): Builder
    {
        return $query->where('courier_periodo_id', $periodoId);
    }
}
