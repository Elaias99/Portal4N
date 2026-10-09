<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuscripcionPrefacturaEnvio extends Model
{
    public const ESTADO_ENVIANDO = 'enviando';
    public const ESTADO_ENVIADO = 'enviado';
    public const ESTADO_OMITIDO = 'omitido';
    public const ESTADO_FALLIDO = 'fallido';

    protected $table = 'suscripcion_prefactura_envios';

    protected $fillable = [
        'anio',
        'mes',
        'suscripcion_proveedor_id',
        'grupo_prefactura',

        'estado',
        'correo',
        'archivo',
        'oc',
        'mensaje',

        'ultimo_intento_at',
        'enviado_at',
    ];

    protected $casts = [
        'anio' => 'integer',
        'mes' => 'integer',
        'suscripcion_proveedor_id' => 'integer',

        'ultimo_intento_at' => 'datetime',
        'enviado_at' => 'datetime',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(
            SuscripcionProveedor::class,
            'suscripcion_proveedor_id'
        );
    }
}
