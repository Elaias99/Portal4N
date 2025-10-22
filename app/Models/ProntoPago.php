<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProntoPago extends Model
{
    use HasFactory;

    protected $table = 'pronto_pagos';

    protected $fillable = [
        'documento_financiero_id',
        'fecha_pronto_pago',
        'user_id',

    ];

    public function documentoFinanciero()
    {
        return $this->belongsTo(DocumentoFinanciero::class, 'documento_financiero_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }






}
