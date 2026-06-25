<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuscripcionConceptoPagoVariable extends Model
{
    use HasFactory;

    protected $table = 'suscripcion_conceptos_pago_variable';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'activo',
        'orden',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function ajustesMensuales()
    {
        return $this->hasMany(
            SuscripcionAjusteMensual::class,
            'concepto_pago_variable_id'
        );
    }


}