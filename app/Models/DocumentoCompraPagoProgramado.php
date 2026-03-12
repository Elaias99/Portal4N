<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentoCompraPagoProgramado extends Model
{
    use HasFactory;

    protected $table = 'documento_compra_pagos_programados';

    protected $casts = [
        'fecha_programada' => 'date',
    ];

    protected $fillable = [
        'documento_compra_id',
        'fecha_programada',
        'user_id',
        'observacion',
    ];

    public function documentoCompra()
    {
        return $this->belongsTo(DocumentoCompra::class, 'documento_compra_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
