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
        'monto',
        'fecha_cruce',
        'proveedor_id',
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


}
