<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FrecuenciaDistribucion extends Model
{
    use HasFactory;

    protected $table = 'frecuencias_distribucion';

    protected $fillable = ['comuna_id', 'proveedor_id'];

    public function dias()
    {
        return $this->hasMany(FrecuenciaDia::class, 'frecuencia_id');
    }


}
