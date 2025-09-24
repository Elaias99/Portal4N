<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
    use HasFactory;

    protected $fillable = ['tipo', 'marca','modelo','procesador','ram','version_windows','nombre_equipo','direccion_ip','controlador','tipo_impresora','resolucion',
                            'tamano_etiqueta', 'funcion_principal', 'ubicacion', 'usuario','usuario_asignado','contrasena','estado','observacion'];


}
