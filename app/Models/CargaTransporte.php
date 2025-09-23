<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CargaTransporte extends Model
{
    use HasFactory;

    protected $table = 'cargas_transporte';

    protected $fillable = [
        'cotizador_id',
        'descripcion',
        'cantidad',
        'medida',
        'peso_total',
        'unidad_peso',
    ];


    // Relación: cada carga pertenece a un cotizador
    public function cotizador()
    {
        return $this->belongsTo(Cotizador::class);
    }


}
