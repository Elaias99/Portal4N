<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoCompra extends Model
{
    use HasFactory;

    protected $table = 'movimientos_compras';

    protected $fillable = [
        'documento_compra_id',
        'usuario_id',
        'estado_anterior',
        'nuevo_estado',
        'fecha_cambio',


    ];


    public function compra()
    {
        return $this->belongsTo(DocumentoCompra::class, 'documento_compra_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    

    

}
