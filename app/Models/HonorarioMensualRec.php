<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HonorarioMensualRec extends Model
{
    use HasFactory;

    protected $table = 'honorarios_mensuales_rec';

    protected $fillable = [
        'rut_contribuyente',
        'razon_social',
        'anio',
        'mes',
        'folio',
        'fecha_emision',
        'estado',
        'fecha_anulacion',
        'rut_emisor',
        'razon_social_emisor',
        'sociedad_profesional',
        'empresa_id',

        'monto_bruto',
        'monto_retenido',
        'monto_pagado',

        'estado_financiero_inicial',
        'estado_financiero',
        'fecha_estado_financiero',
        'saldo_pendiente',
        'cobranza_compra_id',

        'fecha_vencimiento',


    ];

    protected $casts = [
        'fecha_emision'   => 'date',
        'fecha_anulacion' => 'date',
        'fecha_vencimiento' => 'date',
    ];

    // =========================
    // RELACIONES FINANCIERAS
    // =========================

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cobranzaCompra()
    {
        return $this->belongsTo(CobranzaCompra::class, 'cobranza_compra_id');
    }

    public function abonos()
    {
        return $this->hasMany(Abono::class, 'honorario_mensual_rec_id');
    }

    public function cruces()
    {
        return $this->hasMany(Cruce::class, 'honorario_mensual_rec_id');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'honorario_mensual_rec_id');
    }

    public function prontoPagos()
    {
        return $this->hasMany(ProntoPago::class, 'honorario_mensual_rec_id');
    }

    public function movimientos()
    {
        return $this->hasMany(
            MovimientoHonorarioMensualRec::class,
            'honorario_mensual_rec_id');
    }




    // =========================
    // SALDO PENDIENTE
    // =========================

    public function recalcularSaldoPendiente()
    {
        // 1️⃣ Si tiene pago o pronto pago → saldo 0
        if ($this->pagos()->exists() || $this->prontoPagos()->exists()) {
            $this->update(['saldo_pendiente' => 0]);
            return 0;
        }

        // 2️⃣ Monto base: monto pagado informado por SII
        $saldo = $this->monto_pagado ?? 0;

        // 3️⃣ Descontar abonos
        $saldo -= $this->abonos()->sum('monto');

        // 4️⃣ Descontar cruces
        $saldo -= $this->cruces()->sum('monto');

        // 5️⃣ Nunca negativo
        $saldo = max($saldo, 0);

        // 6️⃣ Persistir
        $this->update(['saldo_pendiente' => $saldo]);

        return $saldo;
    }


    // =========================
    // ESTADO FINANCIERO FINAL
    // =========================

    public function getEstadoFinancieroFinalAttribute()
    {
        // Si hay estado manual → prevalece
        if ($this->estado_financiero) {
            return $this->estado_financiero;
        }

        // Si no, usar el inicial (Al día / Vencido)
        return $this->estado_financiero_inicial;
    }






}