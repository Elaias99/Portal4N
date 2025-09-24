<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaquilaTransporte extends Model
{
    use HasFactory;

    protected $table = 'maquila_transportes';

    protected $fillable = ['maquilado_id', 'transporte_id', 'origen', 'origen_lat', 'origen_lon', 'destino','destino_lat','destino_lon',
                            'distancia_km', 'lleva_pioneta', 'cantidad_pionetas', 'jornada_pioneta', 'con_carga'];

    public function maquilado()
    {
        return $this->belongsTo(Maquilado::class);
    }

    public function transporte()
    {
        return $this->belongsTo(Transporte::class);
    }



}
