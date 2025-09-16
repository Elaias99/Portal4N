<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cotizador extends Model
{
    use HasFactory;

    protected $table = 'cotizadors';

    protected $fillable = ['nombre_cliente',
    'servicio_id','Origen','Destino','estado','distancia_km','origen_lat', 'origen_lon', 'destino_lat', 'destino_lon'];

    public function servicio()
    {
        return $this->belongsTo(\App\Models\Servicio::class, 'servicio_id');
    }



}
