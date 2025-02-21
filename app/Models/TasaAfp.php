<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TasaAfp extends Model
{
    use HasFactory;

    // Definimos las columnas que pueden ser asignadas de forma masiva
    protected $fillable = [
        'id_afp',
        'tasa_cotizacion',
        'tasa_sis',
    ];

    // Relación con el modelo AFP
    public function afp()
    {
        return $this->belongsTo(AFP::class, 'id_afp');
    }




}
