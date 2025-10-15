<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cobranza extends Model
{
    use HasFactory;

    protected $table = 'cobranzas';

    protected $fillable = ['rut_cliente', 'razon_social', 'servicio', 'creditos'];

    // Relación: una cobranza tiene muchos documentos
    public function documentos()
    {
        return $this->hasMany(DocumentoFinanciero::class, 'cobranza_id');
    }

    // ⚙️ Evento automático: si cambian los créditos, recalcula vencimientos
    protected static function booted()
    {
        static::updated(function ($cobranza) {
            // Solo ejecutar si el valor de "creditos" cambió
            if ($cobranza->wasChanged('creditos')) {

                // Carga los documentos asociados
                $cobranza->load('documentos');

                // Recalcula la fecha de vencimiento en cada documento
                foreach ($cobranza->documentos as $doc) {
                    $doc->actualizarFechaVencimiento();
                }
            }
        });
    }
}
