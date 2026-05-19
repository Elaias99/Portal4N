<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Factory extends Model
{
    use HasFactory;

    protected $table = 'factories';

    protected $fillable = [
        'documento_financiero_id',
        'banco_id',
        'rut_factory',
        'fecha_factory',
        'monto',
        'user_id',


        'cesion',
        'saldo_liquido',
        'diferencia',




    ];

    protected $casts = [
        'fecha_factory' => 'date',
        'monto' => 'integer',
        'saldo_liquido' => 'integer',
        'diferencia' => 'integer',
    ];

    public function documentoFinanciero()
    {
        return $this->belongsTo(DocumentoFinanciero::class, 'documento_financiero_id');
    }

    public function banco()
    {
        return $this->belongsTo(Banco::class, 'banco_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}