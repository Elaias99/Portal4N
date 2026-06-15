<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuscripcionCantidadMensual extends Model
{
    use HasFactory;

    protected $table = 'suscripcion_cantidades_mensuales';

    protected $fillable = ['suscripcion_asignacion_id', 'anio', 'mes','codigo', 'costo', 'cantidad', 'total', 'observacion'];

    public function asignacion()
    {
        return $this->belongsTo(Asignaciones::class, 'suscripcion_asignacion_id');
    }


}
