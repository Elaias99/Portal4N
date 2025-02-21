<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Hijo extends Model
{
    use HasFactory;


    protected $fillable = [
        'nombre', 'genero', 'parentesco', 'fecha_nacimiento', 'trabajador_id'
    ];

    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class);
    }

    // Este accesor permite calcular automáticamente la edad del hijo basado en su fecha de nacimiento. Esto es útil para determinar si el hijo es dependiente o si es elegible para beneficios específicos.
    public function getEdadAttribute()
    {
        return Carbon::parse($this->fecha_nacimiento)->age;
    }


}
