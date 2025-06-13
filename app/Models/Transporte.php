<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transporte extends Model
{
    use HasFactory;

    protected $table = 'transportes';

    protected $fillable = [
        'nombre',

    ];

    public function rutas()
    {
        return $this->hasMany(RutaGeografica::class, 'transporte_id');
    }



}
