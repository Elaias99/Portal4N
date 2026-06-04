<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuscripcionTransportista extends Model
{
    use HasFactory;

    protected $table = 'suscripcion_transportistas';

    protected $fillable = ['nombre_transportista'];

    public function asignaciones()
    {
        return $this->hasMany(Asignaciones::class, 'suscripcion_transportista_id');
    }



}
