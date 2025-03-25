<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    protected $fillable = ['nombre'];

    public function trabajadores()
    {
        return $this->hasMany(Trabajador::class);
    }

    public function reclamos()
    {
        return $this->hasMany(Reclamos::class);
    }

}
