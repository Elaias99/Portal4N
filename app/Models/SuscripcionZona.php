<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuscripcionZona extends Model
{
    use HasFactory;

    protected $table = 'suscripcion_zonas';

    protected $fillable = [
        'numero_zona',
        'despacho',
        'activo',
    ];

    protected $casts = [
        'numero_zona' => 'integer',
        'activo' => 'boolean',
    ];

    public function asignaciones(): HasMany
    {
        return $this->hasMany(
            Asignaciones::class,
            'suscripcion_zona_id'
        );
    }

    public function diasOperativos(): HasMany
    {
        return $this->hasMany(
            SuscripcionZonaDiaOperativo::class,
            'suscripcion_zona_id'
        );
    }


}
