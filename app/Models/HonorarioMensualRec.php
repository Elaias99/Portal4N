<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CalendarioPagoServicio;

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

        'servicio_manual',


    ];

    protected $casts = [
        'fecha_emision'   => 'date',
        'fecha_anulacion' => 'date',
        'fecha_vencimiento' => 'date',
    ];

    protected $appends = [
        'fecha_ultima_gestion',
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
        // Si tiene pago o pronto pago → saldo 0
        if ($this->pagos()->exists() || $this->prontoPagos()->exists()) {
            $this->update(['saldo_pendiente' => 0]);
            return 0;
        }

        // Monto base: monto pagado informado por SII
        $saldo = $this->monto_pagado ?? 0;

        // Descontar abonos
        $saldo -= $this->abonos()->sum('monto');

        // Descontar cruces
        $saldo -= $this->cruces()->sum('monto');

        // Nunca negativo
        $saldo = max($saldo, 0);

        // Persistir
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



    // =========================
    // SERVICIO FINAL
    // =========================

    public function getServicioFinalAttribute()
    {
        // Si hay servicio manual → prevalece
        if (!empty($this->servicio_manual)) {
            return $this->servicio_manual;
        }

        // Si hay proveedor asociado → usar su servicio
        if ($this->cobranzaCompra) {
            return $this->cobranzaCompra->servicio;
        }

        // Fallback explícito
        return null;
    }



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




    public function getFechaPagoCorporativaAttribute()
    {
        if (!$this->fecha_emision || !$this->cobranzaCompra) {
            return null;
        }

        $anio = $this->fecha_emision->year;
        $mes  = $this->fecha_emision->month;

        $servicio = strtoupper(trim($this->cobranzaCompra->servicio));
        $creditos = $this->cobranzaCompra->creditos;

        $query = CalendarioPagoServicio::where('anio', $anio)
            ->where('mes', $mes)
            ->where('servicio', $servicio);

        // Solo Courier distingue por créditos
        if ($servicio === 'COURIER') {
            $query->where('creditos', $creditos);
        } else {
            $query->whereNull('creditos');
        }

        $calendario = $query->first();

        return $calendario?->fecha_pago;
    }









}