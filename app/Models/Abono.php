<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Abono extends Model
{
    use HasFactory;

    protected $fillable = ['documento_financiero_id', 'monto' , 'fecha_abono'];

    public function documento()
    {
        return $this->belongsTo(DocumentoFinanciero::class, 'documento_financiero_id');
    }



}
