<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackingAlmacenado extends Model
{
    use HasFactory;

    protected $table = 'trackings_almacenados';

    protected $fillable = [
        'prefijo',
        'codigo_tracking',
        'fecha_proceso',
        'destino',
    ];

    public function estadoActual()
    {
        return $this->hasOne(TrackingEstadoActual::class, 'tracking_almacenado_id');
    }

    public function consultas()
    {
        return $this->hasMany(TrackingConsulta::class, 'tracking_almacenado_id');
    }
}
