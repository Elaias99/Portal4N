<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bsale extends Model
{
    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_ENVIANDO = 'enviando';
    public const ESTADO_GENERADA = 'generada';
    public const ESTADO_ERROR = 'error';
    public const ESTADO_INCIERTA = 'incierta';

    protected $table = 'bsales';

    protected $fillable = [
        'huella_datos',
        'user_id',
        'archivo_origen',
        'datos_grupo',
        'comuna_destino',
        'ciudad_destino',
        'ambiente',
        'empresa_bsale_id',
        'document_type_id',
        'declare_sii',
        'estado',
        'payload_enviado',
        'bsale_shipping_id',
        'bsale_document_id',
        'numero_guia',
        'url_pdf',
        'url_vista',
        'estado_http',
        'respuesta_bsale',
        'mensaje_error',
        'envio_iniciado_at',
        'respuesta_recibida_at',
        'generada_at',
    ];

    protected $attributes = [
        'ambiente' => 'production',
        'empresa_bsale_id' => '101346',
        'document_type_id' => 7,
        'declare_sii' => false,
        'estado' => self::ESTADO_PENDIENTE,
    ];

    protected function casts(): array
    {
        return [
            'datos_grupo' => 'array',
            'payload_enviado' => 'array',
            'respuesta_bsale' => 'array',

            'document_type_id' => 'integer',
            'declare_sii' => 'boolean',
            'estado_http' => 'integer',

            'envio_iniciado_at' => 'datetime',
            'respuesta_recibida_at' => 'datetime',
            'generada_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Bsale $bsale) {
            if (empty($bsale->identificador)) {
                $bsale->identificador = bin2hex(random_bytes(32));
            }
        });
    }
}