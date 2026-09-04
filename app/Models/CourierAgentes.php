<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class CourierAgentes extends Model
{
    //
    use HasFactory;

    protected $table = 'courier_agentes';

    protected $fillable = [
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function cobertura(): HasMany
    {
        return $this->hasMany(
            CourierCoberturaComuna::class,
            'courier_agente_id'
        );
    }

    public function proveedores(): HasMany
    {
        return $this->hasMany(
            CourierAgenteProveedor::class,
            'courier_agente_id'
        );
    }

}
