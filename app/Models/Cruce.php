<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cruce extends Model
{
    use HasFactory;

    protected $fillable = [
        'documento_financiero_id',
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


}
