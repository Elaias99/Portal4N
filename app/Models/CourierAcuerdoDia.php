<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/* Un día del mes en el calendario de Acuerdos, y si es feriado. */
class CourierAcuerdoDia extends Model
{
    protected $table = 'courier_acuerdo_dias';

    protected $fillable = [
        'courier_periodo_id',
        'fecha',
        'es_feriado',
    ];

    protected $casts = [
        'fecha' => 'date',
        'es_feriado' => 'boolean',
    ];

    public function scopeDelPeriodo(Builder $query, int $periodoId): Builder
    {
        return $query->where('courier_periodo_id', $periodoId);
    }
}
