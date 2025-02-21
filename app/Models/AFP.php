<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AFP extends Model
{
    use HasFactory;

    protected $table = 'a_f_p_s';

    protected $fillable = [
        'Nombre',

    ];

    

    public function trabajadores()
    {
        return $this->hasMany(Trabajador::class);
    }

    // Relación con el modelo TasaAfp
    public function tasaAfp()
    {
        return $this->hasOne(TasaAfp::class, 'id_afp');
    }
}

