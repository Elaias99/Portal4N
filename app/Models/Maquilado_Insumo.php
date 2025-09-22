<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maquilado_Insumo extends Model
{
    use HasFactory;

    protected $table = 'maquilado_insumos';

    protected $fillable = [
        'maquilado_id',
        'detalle',
        'cantidad',
        'precio',
        'subtotal',
    ];

    public function maquilado()
    {
        return $this->belongsTo(Maquilado::class);
    }
}
