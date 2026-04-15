<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackingConsulta extends Model
{
    use HasFactory;

    protected $table = 'tracking_consultas';

    protected $fillable = [
        'tracking_almacenado_id',
        'document_type',
        'latam_http_status',
        'latam_respondio',
        'html_recibido',
        'parse_ok',
        'estado_detectado',
        'cambio_detectado',
        'estado_resumen',
        'latest_event_code',
        'latest_event_time_raw',
        'estado_firma',
        'html_hash',
        'parsed_payload_json',
        'raw_html',
        'parser_version',
        'error_code',
        'error_message',
        'consultado_en',
    ];

    protected $casts = [
        'latam_http_status' => 'integer',
        'latam_respondio' => 'boolean',
        'html_recibido' => 'boolean',
        'parse_ok' => 'boolean',
        'estado_detectado' => 'boolean',
        'cambio_detectado' => 'boolean',
        'parsed_payload_json' => 'array',
        'consultado_en' => 'datetime',
    ];

    public function trackingAlmacenado()
    {
        return $this->belongsTo(TrackingAlmacenado::class, 'tracking_almacenado_id');
    }


}
