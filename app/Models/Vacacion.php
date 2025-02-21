<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vacacion extends Model
{
    use HasFactory;

    protected $fillable = ['solicitud_id','trabajador_id', 'fecha_inicio','fecha_fin','dias','archivo','archivo_admin','archivo_respuesta_admin'];

    // Relación con el modelo Solicitud
    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }

    // Relación con el modelo Trabajador
    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class);
    }

        // Esto convierte fecha_inicio_trabajo en un objeto Carbon
        protected $casts = [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date', // Agregar este cast
        ];





    //////////////////////////////////////////
    public static function solicitudesPendientes($trabajadorId)
    {
        return self::where('trabajador_id', $trabajadorId)
            ->whereHas('solicitud', function ($query) {
                $query->where('estado', 'pendiente');
            })
            ->with('solicitud')
            ->first();
    }

    public static function diasTomados($trabajadorId)
    {
        return self::where('trabajador_id', $trabajadorId)
            ->whereHas('solicitud', function ($query) {
                $query->where('estado', 'aprobado');
            })
            ->sum('dias');
    }



}
