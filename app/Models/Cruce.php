<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cruce extends Model
{
    use HasFactory;

    protected $fillable = [
        'documento_financiero_id',
        'documento_compra_id',
        'cobranza_id',
        'cobranza_compra_id',
        'monto',
        'fecha_cruce',
    ];
    /**
     * Relación con el documento financiero.
     */
    public function documento()
    {
        return $this->belongsTo(DocumentoFinanciero::class, 'documento_financiero_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function documentoCompra()
    {
        return $this->belongsTo(DocumentoCompra::class, 'documento_compra_id');
    }

    public function cobranza()
    {
        return $this->belongsTo(Cobranza::class, 'cobranza_id');
    }

    public function cobranzaCompra()
    {
        return $this->belongsTo(CobranzaCompra::class, 'cobranza_compra_id');
    }


    public function movimientos()
    {
        return $this->morphMany(
            MovimientoDocumento::class,
            'origen'
        );
    }



}
