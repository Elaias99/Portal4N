<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    protected $fillable = ['Nombre', 'logo'];

    public function facturas()
    {
        return $this->hasMany(Factura::class);
    }

    public function trabajadores()
    {
        return $this->hasMany(Trabajador::class, 'empresa_id');
    }


}
