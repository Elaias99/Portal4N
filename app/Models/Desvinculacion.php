<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Desvinculacion extends Model
{
    use HasFactory;

    protected $table = 'desvinculaciones';

    protected $fillable = [
        'trabajador_id',
        'situacion_id',
        'sistema_trabajo_id',
        'fecha_desvinculo',
        'motivo',
    ];

    /**
     * Relación con el trabajador.
     */
    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class, 'trabajador_id');
    }

    /**
     * Relación con la situación.
     */
    public function situacion()
    {
        return $this->belongsTo(Situacion::class, 'situacion_id');
    }

    /**
     * Relación con el sistema de trabajo.
     */
    public function sistemaTrabajo()
    {
        return $this->belongsTo(SistemaTrabajo::class, 'sistema_trabajo_id');
    }
}
