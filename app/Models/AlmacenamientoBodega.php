<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlmacenamientoBodega extends Model
{
    use HasFactory;

    
    protected $table="almacenamiento_bodega";

    protected $fillable = ["Nombre", "precio", "cantidad", "descripcion"];

    public function historial(): HasMany
    {
        return $this->hasMany(AlmacenamientoBodegaHistorial::class, 'almacenamiento_bodega_id');
    }

}
