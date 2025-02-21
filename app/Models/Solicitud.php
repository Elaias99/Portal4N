<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    use HasFactory;

    protected $table = 'solicitudes';


    protected $fillable = ['trabajador_id', 'campo', 'descripcion', 'estado', 'comentario_admin' ,'archivo','archivo_admin','tipo_dia'];


    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class);
    }

    // Relación con el modelo Vacacion (una solicitud puede tener una vacacion asociada)
    public function vacacion()
    {
        return $this->hasOne(Vacacion::class);
    }



}
