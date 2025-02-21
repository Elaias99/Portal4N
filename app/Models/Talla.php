<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Talla extends Model
{
    use HasFactory;

    protected $fillable = [
        'trabajador_id',
        'tipo_vestimenta_id',
        'talla'
    ];

    public function tipoVestimenta()
    {
        return $this->belongsTo(TipoVestimenta::class, 'tipo_vestimenta_id');
    }

    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class);
    }




}
