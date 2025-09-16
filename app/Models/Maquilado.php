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
        'detalle_insumo',
        'unidades',
        'tipo_maquila'
    ];

    public function cotizador()
    {
        return $this->belongsTo(Cotizador::class);
    }


}
