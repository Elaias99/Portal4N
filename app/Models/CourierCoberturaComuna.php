<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierCoberturaComuna extends Model
{
    use HasFactory;

    protected $table = 'courier_cobertura_comunas';

    protected $fillable = [
        'courier_periodo_id',
        'courier_agente_id',
        'localidad',
        'localidad_clave',
        'zona',
        'pagar_retorno',
        'valor_retorno',
    ];

    protected $casts = [
        'pagar_retorno' => 'boolean',
        'valor_retorno' => 'integer',
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

    /*
     * Genera la clave de búsqueda desde el texto original.
     * Centralizado acá para que la carga y la consulta usen
     * exactamente la misma regla.
     */
    public static function clave(string $localidad): string
    {
        return mb_strtolower($localidad);
    }
}