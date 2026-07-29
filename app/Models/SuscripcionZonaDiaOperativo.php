<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuscripcionZonaDiaOperativo extends Model
{
    use HasFactory;

    protected $table = 'suscripcion_zona_dias_operativos';

    protected $fillable = [
        'suscripcion_zona_id',
        'fecha',
        'hubo_despacho',
        'observacion',
    ];

    protected $casts = [
        'suscripcion_zona_id' => 'integer',
        'fecha' => 'date',
        'hubo_despacho' => 'boolean',
    ];

    public function zona(): BelongsTo
    {
        return $this->belongsTo(
            SuscripcionZona::class,
            'suscripcion_zona_id'
        );
    }
}