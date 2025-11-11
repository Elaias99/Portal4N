<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CobranzaCompra extends Model
{
    use HasFactory;

    protected $table = 'cobranza_compras';

    protected $fillable = ['rut_cliente', 'razon_social', 'servicio', 'creditos'];

    // Relación: una cobranza de compras tiene muchos documentos de compra
    public function documentos()
    {
        return $this->hasMany(DocumentoCompra::class, 'cobranza_compra_id');
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
