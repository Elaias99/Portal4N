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
        'honorario_mensual_rec_id',

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

    public function movimientos()
    {
        return $this->morphMany(
            MovimientoDocumento::class,
            'origen'
        );
    }

    public function honorarioMensualRec()
    {
        return $this->belongsTo(
            HonorarioMensualRec::class,
            'honorario_mensual_rec_id'
        );
    }



}
