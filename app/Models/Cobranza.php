<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cobranza extends Model
{
    use HasFactory;

    protected $table = 'cobranzas';

    protected $fillable = ['rut_cliente', 'razon_social', 'servicio', 'creditos'];
}
