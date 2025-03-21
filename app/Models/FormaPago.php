<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormaPago extends Model
{
    use HasFactory;

    protected $table = 'forma_pago';
    protected $fillable = ['nombre'];

    // Relación con la tabla compras
    public function compras()
    {
        return $this->hasMany(Compra::class, 'forma_pago_id');
    }
}

