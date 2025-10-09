<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoDocumento  extends Model
{
    use HasFactory;

    protected $table = 'tipo_documentos';

    protected $fillable = ['nombre'];

    public function documentosFinancieros()
    {
        return $this->hasMany(DocumentoFinanciero::class, 'tipo_documento_id');
    }
}
