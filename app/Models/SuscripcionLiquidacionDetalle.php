<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuscripcionLiquidacionDetalle extends Model
{
    use HasFactory;

    protected $table = 'suscripcion_liquidacion_detalles';

    protected $fillable = ['suscripcion_asignacion_id','anio','mes','codigo','costo','q_calendario','q_inasistencia','cantidad','total'];

    public function asignacion()
    {
        return $this->belongsTo(Asignaciones::class, 'suscripcion_asignacion_id');
    }


}
