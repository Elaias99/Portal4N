<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HonorarioPagoProgramado extends Model
{
    use HasFactory;

    protected $table = 'honorario_pagos_programados';

    protected $fillable = [
        'honorario_mensual_rec_id',
        'fecha_programada',
        'user_id',
        'observacion',
    ];

    protected $casts = [
        'fecha_programada' => 'date',
    ];


    // Escribir relacion con modelo HonorarioMensualRec
    public function honorarioMensualRec()
    {
        return $this->belongsTo(HonorarioMensualRec::class, 'honorario_mensual_rec_id');
    }   

}
