<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoMaquilado extends Model
{
    use HasFactory;

    protected $table = 'tipos_maquila';

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    public function maquilados()
    {
        return $this->hasMany(Maquilado::class, 'tipo_maquila_id');
    }
}
