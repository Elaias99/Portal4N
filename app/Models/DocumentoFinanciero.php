<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DocumentoFinanciero extends Model
{
    use HasFactory;

    protected $table = 'documentos_financieros';
    protected $appends = ['status_final'];

    protected $fillable = [
        'nro',
        'tipo_doc',
        'tipo_venta',
        'rut_cliente',
        'razon_social',
        'folio',
        'fecha_docto',
        'fecha_recepcion',
        'fecha_acuse_recibo',
        'fecha_reclamo',
        'monto_exento',
        'monto_neto',
        'monto_iva',
        'monto_total',
        'iva_retenido_total',
        'iva_retenido_parcial',
        'iva_no_retenido',
        'iva_propio',
        'iva_terceros',
        'rut_emisor_liquid_factura',
        'neto_comision_liquid_factura',
        'exento_comision_liquid_factura',
        'iva_comision_liquid_factura',
        'iva_fuera_de_plazo',
        'tipo_docto_referencia',
        'folio_docto_referencia',
        'num_ident_receptor_extranjero',
        'nacionalidad_receptor_extranjero',
        'credito_empresa_constructora',
        'impto_zona_franca_ley_18211',
        'garantia_dep_envases',
        'indicador_venta_sin_costo',
        'indicador_servicio_periodico',
        'monto_no_facturable',
        'total_monto_periodo',
        'venta_pasajes_transporte_nacional',
        'venta_pasajes_transporte_internacional',
        'numero_interno',
        'codigo_sucursal',
        'nce_nde_sobre_fact_compra',
        'codigo_otro_imp',
        'valor_otro_imp',
        'tasa_otro_imp',
        'cobranza_id',
        'empresa_id',

        'status',
        'fecha_vencimiento',

        'fecha_estado_manual',
        'status_original',

        'referencia_id',

        'tipo_documento_id'
        
    ];

    // Relación: un documento pertenece a una cobranza
    public function cobranza()
    {
        return $this->belongsTo(Cobranza::class, 'cobranza_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function abonos()
    {
        return $this->hasMany(Abono::class, 'documento_financiero_id');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoDocumento::class, 'documento_financiero_id');
    }


    // Documento referenciado (por ejemplo, la factura asociada a una nota de crédito)
    public function referencia()
    {
        return $this->belongsTo(DocumentoFinanciero::class, 'referencia_id');
    }

    // 🔁 Documentos que hacen referencia a este (por ejemplo, notas de crédito aplicadas)
    public function referenciados()
    {
        return $this->hasMany(DocumentoFinanciero::class, 'referencia_id');
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class, 'tipo_documento_id');
    }

    public function cruces()
    {
        return $this->hasMany(Cruce::class, 'documento_financiero_id');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'documento_financiero_id');
    }

    public function prontoPagos()
    {
        return $this->hasMany(ProntoPago::class, 'documento_financiero_id');
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getStatusFinalAttribute()
    {
        // Si tiene status manual → prevalece
        if ($this->status) {
            return $this->status;
        }

        // Si no tiene status manual, usar cálculo de vencimiento
        if ($this->fecha_vencimiento) {
            $fechaVenc = Carbon::parse($this->fecha_vencimiento);

            if ($fechaVenc->isPast()) {
                return 'Vencido';
            } else {
                return 'Al día';
            }
        }

        return 'Sin cálculo';
    }

    public function getEstaVencidoAttribute()
    {
        if (!$this->fecha_vencimiento) {
            return false;
        }

        return Carbon::parse($this->fecha_vencimiento)->isPast();
    }

    public function getSaldoPendienteAttribute()
    {
        // 🟢 Si tiene pagos registrados → saldo = 0
        $pagos = $this->relationLoaded('pagos') ? $this->pagos : $this->pagos()->get();
        if ($pagos->count() > 0) {
            return 0;
        }

        // 🟣 Si es una Nota de Crédito Electrónica → saldo = 0
        if ($this->tipo_documento_id == 61 ||
            (isset($this->tipoDocumento) && str_contains(strtolower($this->tipoDocumento->nombre), 'nota de crédito'))) {
            return 0;
        }

        // 🔹 Monto base del documento
        $saldo = $this->monto_total ?? 0;

        // 🔹 Relaciones
        $referenciados = $this->relationLoaded('referenciados')
            ? $this->referenciados
            : $this->referenciados()->get();

        $abonos = $this->relationLoaded('abonos')
            ? $this->abonos
            : $this->abonos()->get();

        $cruces = $this->relationLoaded('cruces')
            ? $this->cruces
            : $this->cruces()->get();

        // ✅ Restar notas de crédito
        $totalNotasCredito = $referenciados
            ->where('tipo_documento_id', 61)
            ->sum('monto_total');
        $saldo -= $totalNotasCredito;

        // ✅ Sumar notas de débito
        $totalNotasDebito = $referenciados
            ->where('tipo_documento_id', 56)
            ->sum('monto_total');
        $saldo += $totalNotasDebito;

        // ✅ Restar abonos y cruces
        $saldo -= $abonos->sum('monto');
        $saldo -= $cruces->sum('monto');

        return max($saldo, 0);
    }


    public function actualizarFechaVencimiento()
    {
        $this->loadMissing('cobranza');

        if ($this->fecha_docto && $this->cobranza && $this->cobranza->creditos) {
            $this->fecha_vencimiento = Carbon::parse($this->fecha_docto)
                ->addDays((int) $this->cobranza->creditos)
                ->format('Y-m-d');
            $this->save();
        }
    }










}
