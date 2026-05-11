<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CobranzaCompra extends Model
{
    use HasFactory;

    protected $table = 'cobranza_compras';

    protected $fillable = ['rut_cliente', 'razon_social', 'servicio', 'creditos'  ,'tipo','facturacion','forma_pago','zona',
                            'importancia', 'responsable', 'nombre_cuenta', 'rut_cuenta', 'numero_cuenta','banco_id', 'tipo_cuenta_id'];


    // Una cobranza de compras → pertenece a un banco
    public function banco()
    {
        return $this->belongsTo(Banco::class, 'banco_id');
    }

    // Una cobranza de compras → pertenece a un tipo de cuenta
    public function tipoCuenta()
    {
        return $this->belongsTo(TipoCuenta::class, 'tipo_cuenta_id');
    }


    // Relación: una cobranza de compras tiene muchos documentos de compra
    public function documentos()
    {
        return $this->hasMany(DocumentoCompra::class, 'cobranza_compra_id');
    }

    // Relación: una cobranza de compras puede tener boletas de honorarios asociadas
    public function honorariosMensualesRec()
    {
        return $this->hasMany(HonorarioMensualRec::class, 'cobranza_compra_id');
    }

    // Evento automático: si cambian los créditos, recalcula vencimientos
    protected static function booted()
    {
        static::updated(function ($cobranzaCompra) {
            if ($cobranzaCompra->wasChanged('creditos')) {
                $cobranzaCompra->load('documentos');

                foreach ($cobranzaCompra->documentos as $doc) {
                    $doc->actualizarFechaVencimiento();
                }
            }
        });
    }
}
