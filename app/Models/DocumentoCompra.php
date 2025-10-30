<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DocumentoCompra extends Model
{
    use HasFactory;

    protected $table = 'documentos_compras';

    protected $fillable = ['empresa_id', 'tipo_documento_id', 'nro', 'tipo_doc', 'tipo_compra',
        'rut_proveedor', 'razon_social', 'folio', 'fecha_docto', 'fecha_recepcion', 'fecha_acuse', 'monto_exento',
        'monto_neto', 'monto_iva_recuperable', 'monto_iva_no_recuperable', 'codigo_iva_no_rec', 'monto_total', 
        'monto_neto_activo_fijo', 'iva_activo_fijo', 'iva_uso_comun', 'impto_sin_derecho_credito','iva_no_retenido',
        'tabacos_puros', 'tabacos_cigarrillos', 'tabacos_elaborados', 'nce_nde_sobre_fact_compra', 'codigo_otro_impuesto', 'valor_otro_impuesto', 'tasa_otro_impuesto', 
        'estado', 'fecha_vencimiento','cobranza_id','status_original','fecha_estado_manual', ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class, 'tipo_documento_id');
    }

    public function cobranza()
    {
        return $this->belongsTo(Cobranza::class, 'cobranza_id');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoCompra::class, 'documento_compra_id');
    }


    public function abonos()
    {
        return $this->hasMany(Abono::class, 'documento_compra_id');
    }

    public function cruces()
    {
        return $this->hasMany(Cruce::class, 'documento_compra_id');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'documento_compra_id');
    }

    public function prontoPagos()
    {
        return $this->hasMany(ProntoPago::class, 'documento_compra_id');
    }







    /////////////////////////////////////////////


    public function actualizarFechaVencimiento()
    {
        $this->loadMissing('cobranza');

        if ($this->fecha_docto && $this->cobranza && $this->cobranza->creditos) {
            $this->fecha_vencimiento = Carbon::parse($this->fecha_docto)
                ->addDays((int) $this->cobranza->creditos)
                ->format('Y-m-d');

            $this->status_original = Carbon::parse($this->fecha_vencimiento)->isPast()
                ? 'Vencido'
                : 'Al día';

            $this->save();
        }
    }

    public function getStatusOriginalAttribute($value)
    {
        if ($value) return $value;

        if ($this->fecha_vencimiento) {
            $fechaVenc = Carbon::parse($this->fecha_vencimiento);
            return $fechaVenc->isPast() ? 'Vencido' : 'Al día';
        }

        return 'Sin cálculo';
    }

    // ======================================================
    // 💰 Cálculo de saldo pendiente (mismo patrón que CxC)
    // ======================================================



    public function getSaldoPendienteAttribute()
    {
        // 🟢 Si tiene pagos registrados → saldo = 0
        $pagos = $this->relationLoaded('pagos') ? $this->pagos : $this->pagos()->get();
        if ($pagos->count() > 0) {
            return 0;
        }

        // 🔹 Monto base del documento
        $saldo = $this->monto_total ?? 0;

        // 🔹 Cargar relaciones necesarias
        $abonos = $this->relationLoaded('abonos') ? $this->abonos : $this->abonos()->get();
        $cruces = $this->relationLoaded('cruces') ? $this->cruces : $this->cruces()->get();

        // ✅ Restar abonos y cruces
        $saldo -= $abonos->sum('monto');
        $saldo -= $cruces->sum('monto');

        return max($saldo, 0);
    }



}
