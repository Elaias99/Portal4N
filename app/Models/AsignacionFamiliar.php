<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsignacionFamiliar extends Model
{
    use HasFactory;

    protected $fillable = [
        'tramo',
        'salario_minimo',
        'salario_maximo',
        'monto'
    ];

    /**
     * Este método busca y devuelve el tramo de asignación familiar
     * correspondiente según el salario bruto del trabajador.
     */
    public static function obtenerTramo($salario_bruto)
    {
        return self::where('salario_minimo', '<=', $salario_bruto)
                    ->where('salario_maximo', '>=', $salario_bruto)
                    ->first();
    }


}
