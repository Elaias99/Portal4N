<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asignaciones extends Model
{
    use HasFactory;

    protected $table = 'suscripcion_asignaciones';

    protected $fillable = ['suscripcion_proveedor_id','suscripcion_transportista_id','punto_1','punto_2','origen_gasto','codigo','servicio','costo','grupo_prefactura','generar_automaticamente'];


    public function suscripcionProveedor()
    {
        return $this->belongsTo(SuscripcionProveedor::class, 'suscripcion_proveedor_id');
    }

    public function transportista()
    {
        return $this->belongsTo(SuscripcionTransportista::class, 'suscripcion_transportista_id');
    }

    public function liquidacionDetalles()
    {
        return $this->hasMany(SuscripcionLiquidacionDetalle::class, 'suscripcion_asignacion_id');
    }

    public function opvPuntos()
    {
        return $this->hasMany(SuscripcionOPVPuntos::class, 'suscripcion_asignacion_id');
    }

    public function comisionesMensuales()
    {
        return $this->hasMany(SuscripcionComisionMensual::class, 'suscripcion_asignacion_id');
    }



}
