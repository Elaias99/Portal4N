<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reclamos extends Model
{
    use HasFactory;

    protected $table = 'reclamos';

    protected $fillable = ['id_bulto', 'id_trabajador', 'descripcion', 'respuesta_admin', 'estado','id_jefe','area_id', 'tipo_solicitud','casuistica_id'];

    // Relación con Bultos (Cada reclamo pertenece a un bulto)
    public function bulto()
    {
        return $this->belongsTo(Bultos::class, 'id_bulto');
    }

    // Relación con Trabajadors (Cada reclamo lo hace un trabajador)
    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class, 'id_trabajador');
    }

    public function jefe()
    {
        return $this->belongsTo(Jefe::class, 'id_jefe');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function comentarios()
    {
        return $this->hasMany(\App\Models\ReclamoComentario::class, 'reclamo_id');
    }

    public function casuistica()
    {
        return $this->belongsTo(Casuistica::class, 'casuistica_id');
    }



}
