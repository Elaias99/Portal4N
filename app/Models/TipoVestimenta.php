<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoVestimenta extends Model
{
    use HasFactory;

    protected $fillable = ['Nombre'];

    public function tallas()
    {
        return $this->hasMany(Talla::class);
    }
}
