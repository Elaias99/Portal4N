<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jefe extends Model
{
    use HasFactory;

    protected $table = 'jefes';

    protected $fillable = [
        'nombre',
        'area',
        'user_id',
    ];

    // Relación con el modelo Trabajador
    public function trabajadores()
    {
        return $this->hasMany(Trabajador::class, 'id_jefe');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
