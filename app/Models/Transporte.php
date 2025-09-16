<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transporte extends Model
{
    use HasFactory;

    protected $table = 'transportes';

    protected $fillable = [
        'nombre',      // Ej: "Camión"
        'perfil_api',  // Ej: "driving-hgv"
    ];

    public function cotizaciones()
    {
        return $this->hasMany(Cotizador::class);
    }



}
