<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackingEstadoActual extends Model
{
    use HasFactory;

    protected $table = 'tracking_estados_actuales';

    protected $fillable = [
        'tracking_almacenado_id',
        'document_type',
        'tiene_estado_valido',
        'estado_resumen',
        'origen',
        'destino_latam',
        'arrival_on_or_before_raw',
        'product',
        'commodity',
        'pieces',
        'weight',
        'latest_event_code',
        'latest_event_description',
        'latest_event_station',
        'latest_event_time_raw',
        'latest_leg_flight',
        'latest_leg_etd_raw',
        'latest_leg_eta_raw',
        'parsed_payload_json',
        'hidden_metadata_json',
        'irregularities_json',
        'export_options_json',
        'estado_firma',
        'html_hash',
        'parser_version',
        'ultima_consulta_at',
        'ultima_consulta_exitosa_at',
        'ultimo_cambio_at',
        'ultimo_error_code',
        'ultimo_error_message',  


    ];

    protected $casts = [
        'tiene_estado_valido' => 'boolean',
        'pieces' => 'integer',
        'weight' => 'decimal:2',
        'parsed_payload_json' => 'array',
        'hidden_metadata_json' => 'array',
        'irregularities_json' => 'array',
        'export_options_json' => 'array',
        'ultima_consulta_at' => 'datetime',
        'ultima_consulta_exitosa_at' => 'datetime',
        'ultimo_cambio_at' => 'datetime',
    ];

    public function trackingAlmacenado()
    {
        return $this->belongsTo(TrackingAlmacenado::class, 'tracking_almacenado_id');
    }


}
