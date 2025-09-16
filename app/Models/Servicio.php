<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Servicio extends Model
{
    use HasFactory;
    
    protected $table = 'servicios';

    protected $fillable = ['nombre'];

    public function cotizadores()
    {
        return $this->hasMany(\App\Models\Cotizador::class, 'servicio_id');
    }



}
