<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CentroCosto extends Model
{
    use HasFactory;

    protected $table = 'centros_costos';

    protected $fillable = ['nombre'];

    public function compras()
    {
        return $this->hasMany(Compra::class, 'centro_costo_id');
    }
}
