<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlazoPago extends Model
{
    use HasFactory;

    protected $table = 'plazo_pago';

    protected $fillable = ['nombre'];

    public function compras()
    {
        return $this->hasMany(Compra::class);
    }
}
