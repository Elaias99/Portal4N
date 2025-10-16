<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoDocumento extends Model
{
    use HasFactory;

    protected $table = 'movimientos_documentos';

    protected $fillable = [
        'documento_financiero_id',
        'user_id',
        'tipo_movimiento',
        'descripcion',
        'datos_anteriores',
        'datos_nuevos',
    ];

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
    ];

    // Relaciones
    public function documento()
    {
        return $this->belongsTo(DocumentoFinanciero::class, 'documento_financiero_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
