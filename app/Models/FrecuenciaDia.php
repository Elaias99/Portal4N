<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FrecuenciaDia extends Model
{
    use HasFactory;

    protected $fillable = ['frecuencia_id', 'dia_semana'];

    public function frecuencia()
    {
        return $this->belongsTo(FrecuenciaDistribucion::class, 'frecuencia_id');
    }


}
