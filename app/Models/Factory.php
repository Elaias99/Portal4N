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
        'user_id',
        'cesion_factoring_id',


        'rut_factory',
        'fecha_factory',
        'monto',
        


        'cesion',
        'saldo_liquido',
        'diferencia',

        'monto_no_anticipado',
        'diferencia_precio',

        'comision_total',
        'monto_a_recibir',

        'estado_operacion',




    ];

    protected $casts = [
        'fecha_factory' => 'date',
        'monto' => 'integer',
        'saldo_liquido' => 'integer',

        // Campo legado, pendiente de eliminación posterior.
        'diferencia' => 'integer',

        'monto_no_anticipado' => 'integer',
        'diferencia_precio' => 'integer',

        'comision_total' => 'integer',
        'monto_a_recibir' => 'integer',



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

    public function cesionFactory()
    {
        return $this->belongsTo(CesionFactory::class, 'cesion_factoring_id');
    }




}