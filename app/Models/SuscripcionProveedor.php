<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuscripcionProveedor extends Model
{
    use HasFactory;

    protected $table = 'suscripcion_proveedores';

    protected $fillable = ['cobranza_compra_id', 'tipo', 'detalle_documento','detalle_impuesto', 'final'];


    public function cobranzaCompra()
    {
        return $this->belongsTo(CobranzaCompra::class, 'cobranza_compra_id');
    }

    public function asignaciones()
    {
        return $this->hasMany(Asignaciones::class, 'suscripcion_proveedor_id');
    }


}
