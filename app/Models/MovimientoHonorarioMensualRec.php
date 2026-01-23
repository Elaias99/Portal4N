<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoHonorarioMensualRec extends Model
{
    use HasFactory;

    protected $table = 'movimientos_honorarios_mensuales_rec';

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos'     => 'array',
        'fecha_cambio'     => 'datetime',
    ];

    protected $fillable = [
        'honorario_mensual_rec_id',
        'usuario_id',
        'estado_anterior',
        'nuevo_estado',
        'fecha_cambio',
        'tipo_movimiento',
        'descripcion',
        'datos_anteriores',
        'datos_nuevos',
        'fecha_cambio',

    ];

    // 🔗 Honorario asociado
    public function honorario()
    {
        return $this->belongsTo(
            HonorarioMensualRec::class,
            'honorario_mensual_rec_id'
        );
    }

    // 👤 Usuario que realizó la acción
    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

}
