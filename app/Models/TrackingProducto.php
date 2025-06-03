<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackingProducto extends Model
{
    use HasFactory;

    protected $fillable = [ 'codigo','estado','user_id','trabajador_id','area_id','chofer_id'];

    public function trabajador()
    {
        return $this->belongsTo(\App\Models\Trabajador::class);
    }

    public function bulto()
    {
        return $this->hasOne(\App\Models\Bultos::class, 'codigo_bulto', 'codigo');
    }



}
