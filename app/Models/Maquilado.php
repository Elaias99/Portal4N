<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maquilado extends Model
{
    use HasFactory;

    protected $table = 'maquilados';

    protected $fillable = [
        'cotizador_id',
        'insumo',

        'tipo_maquila_id'
    ];

    public function cotizador()
    {
        return $this->belongsTo(Cotizador::class);
    }

    public function insumos()
    {
        return $this->hasMany(Maquilado_Insumo::class, 'maquilado_id');
    }

    public function tipoMaquila()
    {
        return $this->belongsTo(TipoMaquilado::class, 'tipo_maquila_id');
    }



}
