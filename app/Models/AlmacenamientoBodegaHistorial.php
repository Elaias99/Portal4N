<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlmacenamientoBodegaHistorial extends Model
{
    use HasFactory;

    protected $table="almacenamiento_bodega_historial";

    protected $fillable = ["almacenamiento_bodega_id", "nombre_producto", "accion"];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(AlmacenamientoBodega::class, 'almacenamiento_bodega_id');
    }

    


}
