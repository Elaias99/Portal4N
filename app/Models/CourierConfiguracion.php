<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierConfiguracion extends Model
{
    use HasFactory;

    protected $table = 'courier_configuracions';

    protected $fillable = [
        'courier_periodo_id',
        'courier_agente_id',
        'comerciante',
        'servicio',
        'llave',
        'pagar',
        'tabla',
    ];

    protected $casts = [
        'tabla' => 'integer',
    ];

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(
            CourierPeriodo::class,
            'courier_periodo_id'
        );
    }

    public function agente(): BelongsTo
    {
        return $this->belongsTo(
            CourierAgentes::class,
            'courier_agente_id'
        );
    }

    public function scopePagables(Builder $query): Builder
    {
        return $query->where('pagar', 'SI');
    }

    public function scopePorRevisar(Builder $query): Builder
    {
        return $query->where('pagar', 'REVISAR');
    }
}