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
        'courier_agente_id',
        'comerciante',
        'servicio',
        'llave',
        'pagar',
        'tabla',
        'activo'
    ];

    protected $casts = [
        'tabla' => 'integer',
        'activo' => 'boolean'
    ];

    /*
     * Replica PagosCentroCostos!D: =+A2&B2&C2, en minúsculas porque la
     * columna es utf8mb4_bin. Comerciante y servicio van tal cual llegan
     * de Geolice, sin trim: la llave debe calzar byte a byte.
     */
    public static function llave(string $agente, string $comerciante, string $servicio): string
    {
        return mb_strtolower($agente . $comerciante . $servicio);
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