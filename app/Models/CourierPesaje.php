<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierPesaje extends Model
{
    use HasFactory;

    protected $table = 'courier_pesajes';

    protected $fillable = [
        'courier_periodo_id',
        'seguimiento',
        'codigo',
        'fecha_pesaje',
        'kilos',
        'archivo_origen',
    ];

    protected $casts = [
        'fecha_pesaje' => 'date',
        'kilos' => 'integer',
    ];

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(
            CourierPeriodo::class,
            'courier_periodo_id'
        );
    }

    /* Cruce por código con sufijo; no es FK porque el bulto puede llegar después. */
    public function bulto(): BelongsTo
    {
        return $this->belongsTo(
            CourierBulto::class,
            'seguimiento',
            'seguimiento'
        );
    }

    public function scopeDelPeriodo(Builder $query, int $periodoId): Builder
    {
        return $query->where('courier_periodo_id', $periodoId);
    }

    public function scopeConPeso(Builder $query): Builder
    {
        return $query->where('kilos', '>', 0);
    }
}
