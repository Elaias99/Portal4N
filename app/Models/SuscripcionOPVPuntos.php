<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuscripcionOPVPuntos extends Model
{
    use HasFactory;

    protected $table = 'suscripcion_opv_puntos';


    protected $fillable = [
        'suscripcion_asignacion_id',
        'ruta_nombre',
        'local',
        'nombre_local',
        'nombre_local_corto',
        'direccion',
        'comuna',
        'lat',
        'lng'
    ];

    public function asignacion()
    {
        return $this->belongsTo(Asignaciones::class, 'suscripcion_asignacion_id');
    }




}
