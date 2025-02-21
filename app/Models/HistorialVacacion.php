<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialVacacion extends Model
{
    use HasFactory;

    protected $table = 'historial_vacacions';

    protected $fillable = [
        'trabajador_id',
        'fecha_inicio',
        'fecha_fin',
        'dias_laborales',
        'tipo_dia',
        'comentario_admin',
        'es_historico',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    // Definir la relación con Trabajador
    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class, 'trabajador_id');
    }

}
