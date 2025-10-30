<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use HasFactory;

    protected $table = 'pagos';

    protected $fillable = [
        'documento_financiero_id',
        'documento_compra_id',
        'fecha_pago',
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

    public function documentoCompra()
    {
        return $this->belongsTo(DocumentoCompra::class, 'documento_compra_id');
    }



}
