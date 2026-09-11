<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourierTarifa extends Model
{
    use HasFactory;

    protected $table = 'courier_tarifas';

    protected $fillable = [
        'numero',
        'nombre',
        'kilo_adicional',
    ];

    protected $casts = [
        'numero' => 'integer',
        'kilo_adicional' => 'integer',
    ];


    public function tramos(): HasMany
    {
        return $this->hasMany(
            CourierTarifaTramo::class,
            'courier_tarifa_id'
        );
    }

    public function esPlana(): bool
    {
        return $this->kilo_adicional === 0;
    }
}