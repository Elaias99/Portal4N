<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoCuenta extends Model
{
    use HasFactory;

    protected $fillable = ['nombre'];

    public function cobranzasCompras()
    {
        return $this->hasMany(CobranzaCompra::class, 'tipo_cuenta_id');
    }



}
