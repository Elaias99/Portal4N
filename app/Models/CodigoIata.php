<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodigoIata extends Model
{
    use HasFactory;

    protected $table = 'iata_codigos';

    protected $fillable = [
        'cod_iata',
        'cod_iata2'
    ];

    public function clasificacion()
    {
        return $this->hasMany(ComunaClasificacionOperativa::class);
    }


}
