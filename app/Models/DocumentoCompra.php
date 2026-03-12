<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
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
        'estado', 'fecha_vencimiento','status_original','fecha_estado_manual', 'cobranza_compra_id','saldo_pendiente'];

    public function empresa()
    {               
        return $this->belongsTo(Empresa::class);
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class, 'tipo_documento_id');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoCompra::class, 'documento_compra_id');
    }

    public function abonos()
    {
        return $this->hasMany(Abono::class, 'documento_compra_id');
    }


    public function abonosReales()
    {
        return $this->hasMany(Abono::class, 'documento_compra_id')
            ->where(function ($q) {
                $q->whereNull('origen')
                ->orWhere('origen', '!=', 'referencia_nc');
            });
    }

    public function abonosPorReferencia()
    {
        return $this->hasMany(Abono::class, 'documento_compra_id')
            ->where('origen', 'referencia_nc');
    }



    public function cruces()
    {
        return $this->hasMany(Cruce::class, 'documento_compra_id');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'documento_compra_id');
    }


    public function pagosReales()
    {
        return $this->hasMany(Pago::class, 'documento_compra_id')
            ->where(function ($q) {
                $q->whereNull('origen')
                ->orWhere('origen', '!=', 'referencia_nc');
            });
    }

    public function pagosPorReferencia()
    {
        return $this->hasMany(Pago::class, 'documento_compra_id')
            ->where('origen', 'referencia_nc');
    }




    public function prontoPagos()
    {
        return $this->hasMany(ProntoPago::class, 'documento_compra_id');
    }

    public function cobranzaCompra()
    {
        return $this->belongsTo(CobranzaCompra::class, 'cobranza_compra_id');
    }

    // Documento al que este documento hace referencia (por ejemplo, nota de crédito → factura)
    public function referencia()
    {
        return $this->belongsTo(DocumentoCompra::class, 'referencia_id');
    }

    // Documentos que hacen referencia a este (notas aplicadas a la factura)
    public function referenciados()
    {
        return $this->hasMany(DocumentoCompra::class, 'referencia_id');
    }

    public function pagoProgramado()
    {
        return $this->hasOne(DocumentoCompraPagoProgramado::class, 'documento_compra_id');
    }







    /////////////////////////////////////////////


    public function actualizarFechaVencimiento()
    {
        $this->loadMissing('cobranzaCompra');

        if ($this->fecha_docto && $this->cobranzaCompra && $this->cobranzaCompra->creditos) {
            $this->fecha_vencimiento = Carbon::parse($this->fecha_docto)
                ->addDays((int) $this->cobranzaCompra->creditos)
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
    // Cálculo de saldo pendiente (mismo patrón que CxC)
    // ======================================================



    public function getSaldoPendienteAttribute()
    {
        // 1) Valor en BD
        $valorBD = $this->attributes['saldo_pendiente'] ?? null;

        // 2) Detectar si debe recálculo


        $tienePagos      = $this->pagosReales()->exists();




        $tieneAbonos     = $this->abonos()->exists();
        $tieneCruces     = $this->cruces()->exists();
        $esProntoPago    = $this->prontoPagos()->exists();
        $tieneReferencia = !is_null($this->referencia_id);

        $tieneNotas = method_exists($this, 'referenciados')
            ? $this->referenciados()->exists()
            : false;

        $requiereRecalculo =
            $tienePagos ||
            $tieneAbonos ||
            $tieneCruces ||
            $tieneNotas ||
            $esProntoPago ||
            $tieneReferencia;

        // 3) Si NO requiere recálculo → devolver valor BD
        if (!$requiereRecalculo && $valorBD !== null) {
            return $valorBD;
        }

        return $this->recalcularSaldoPendiente();
    }






    // ======================================================
    // Cálculo de saldo pendiente (los recalcula)
    // ======================================================

    public function recalcularSaldoPendiente()
    {
        // ============================================================
        // 1) Si tiene pagos → saldo = 0
        // ============================================================
        if ($this->pagosReales()->exists()) {
            $this->update(['saldo_pendiente' => 0]);
            return 0;
        }

        // ============================================================
        // 1.1) Si tiene pago automático por referencia → saldo = 0
        // ============================================================
        if ($this->pagosPorReferencia()->exists()) {
            $this->update(['saldo_pendiente' => 0]);
            return 0;
        }


        // ============================================================
        // 2) Si es Pronto Pago → saldo = 0
        // ============================================================
        if ($this->estado === 'Pronto pago' || $this->prontoPagos()->exists()) {
            $this->update(['saldo_pendiente' => 0]);
            return 0;
        }

        // ============================================================
        // 3) Si es Nota de Crédito
        //    - con referencia  -> saldo = 0
        //    - sin referencia  -> saldo = monto_total
        // ============================================================
        if (
            $this->tipo_documento_id == 61 ||
            (isset($this->tipoDocumento) &&
            str_contains(strtolower($this->tipoDocumento->nombre), 'nota de crédito'))
        ) {
            $saldoNota = $this->referencia_id ? 0 : ($this->monto_total ?? 0);

            $this->update(['saldo_pendiente' => $saldoNota]);

            return $saldoNota;
        }

        // ============================================================
        // 4) Iniciar saldo desde monto_total
        // ============================================================
        $saldo = $this->monto_total ?? 0;

        // ============================================================
        // 5) Notas asociadas
        //    Evitar doble descuento cuando ya existan abonos/pagos
        //    automáticos generados por referencia
        // ============================================================
        if (method_exists($this, 'referenciados')) {

            $referenciados = $this->referenciados()->get();

            // Total de NC referenciadas a esta factura
            $totalNotasCredito = $referenciados
                ->where('tipo_documento_id', 61)
                ->sum('monto_total');

            // Total ya materializado como abono automático por referencia
            $totalAbonosReferencia = $this->abonosPorReferencia()->sum('monto');

            // Si ya existe pago por referencia, no volver a descontar NC aquí
            if ($this->pagosPorReferencia()->exists()) {
                $pendienteDescontarPorNC = 0;
            } else {
                // Solo descontar la parte de NC que aún no ha sido materializada
                $pendienteDescontarPorNC = max($totalNotasCredito - $totalAbonosReferencia, 0);
            }

            $saldo -= $pendienteDescontarPorNC;

            // Notas de débito suman
            $saldo += $referenciados
                ->where('tipo_documento_id', 56)
                ->sum('monto_total');
        }

        // ============================================================
        // 6) Abonos → restan
        // ============================================================
        $saldo -= $this->abonos()->sum('monto');

        // ============================================================
        // 7) Cruces → restan
        // ============================================================
        $saldo -= $this->cruces()->sum('monto');

        // ============================================================
        // 8) Evitar negativos
        // ============================================================
        $saldo = max($saldo, 0);

        // ============================================================
        // 9) Guardar en BD
        // ============================================================
        $this->update(['saldo_pendiente' => $saldo]);

        return $saldo;
    }







    protected static function booted()
    {
        static::updating(function ($model) {
            if ($model->isDirty('estado') && $model->estado !== null) {
                $model->fecha_estado_manual = now();
            }
        });
    }





    public function getEstadoVisibleAttribute()
    {
        if ($this->estado === 'Pago') {
            return 'Pagado';
        }

        return $this->estado ?: $this->status_original;
    }





    // Accesor para mostrar fecha de transacción (pago, abono, cruce o pronto pago)

    public function getFechaUltimaGestionAttribute()
    {
        $fechas = collect();

        if ($this->abonos->isNotEmpty()) {
            $fechas->push(
                $this->abonos->max('fecha_abono')
            );
        }

        if ($this->cruces->isNotEmpty()) {
            $fechas->push(
                $this->cruces->max('fecha_cruce')
            );
        }

        if ($this->pagos->isNotEmpty()) {
            $fechas->push(
                $this->pagos->max('fecha_pago')
            );
        }

        if ($this->prontoPagos->isNotEmpty()) {
            $fechas->push(
                $this->prontoPagos->max('fecha_pronto_pago')
            );
        }

        return $fechas->filter()->max();
    }





}
