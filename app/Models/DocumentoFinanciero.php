<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DocumentoFinanciero extends Model
{
    use HasFactory;

    protected $table = 'documentos_financieros';
    protected $appends = ['status_final','fecha_ultima_transaccion',];

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

        'tipo_documento_id',

        'saldo_pendiente',
    ];

    public function cobranza()
    {
        return $this->belongsTo(Cobranza::class, 'cobranza_id');
    }

    public function cobranzaCompraAsociada()
    {
        return $this->hasOne(CobranzaCompra::class, 'rut_cliente', 'rut_cliente');
    }

    public function documentosCompraAsociados()
    {
        return $this->hasMany(DocumentoCompra::class, 'rut_proveedor', 'rut_cliente');
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

    public function referencia()
    {
        return $this->belongsTo(DocumentoFinanciero::class, 'referencia_id');
    }

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

    public function factoryRegistro()
    {
        return $this->hasOne(Factory::class, 'documento_financiero_id');
    }

    public function getStatusFinalAttribute()
    {
        if ($this->status) {
            return $this->status;
        }

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
        return $this->calcularSaldoPendienteDesdeMovimientos(false);
    }

    public function recalcularSaldoPendiente()
    {
        $saldo = $this->calcularSaldoPendienteDesdeMovimientos(true);

        $this->update([
            'saldo_pendiente' => $saldo,
        ]);

        return $saldo;
    }

    protected function calcularSaldoPendienteDesdeMovimientos(bool $persistirFactory = false): int
    {
        if ($this->pagos()->exists()) {
            return 0;
        }

        if ($this->prontoPagos()->exists()) {
            return 0;
        }

        if ($this->esNotaCredito()) {
            return 0;
        }

        $factory = $this->factoryRegistro()->first();

        if ($factory) {
            return $this->calcularSaldoConFactory($factory, $persistirFactory);
        }

        $saldo = $this->calcularSaldoBaseDocumento();

        $saldo -= (int) $this->abonos()->sum('monto');
        $saldo -= (int) $this->cruces()->sum('monto');

        return max($saldo, 0);
    }




    protected function calcularSaldoConFactory(Factory $factory, bool $persistirFactory = false): int
    {
        $saldoBaseDocumento = $this->calcularSaldoBaseDocumento();

        /*
        |--------------------------------------------------------------------------
        | Monto cedido vigente
        |--------------------------------------------------------------------------
        | Mantiene la lógica existente: Factoring se registra sobre el saldo real
        | que existía después de abonos o cruces anteriores al registro.
        |--------------------------------------------------------------------------
        */
        $montoCedidoActual = $saldoBaseDocumento;

        if ($factory->created_at) {
            $montoCedidoActual -= (int) $this->abonos()
                ->where('created_at', '<', $factory->created_at)
                ->sum('monto');

            $montoCedidoActual -= (int) $this->cruces()
                ->where('created_at', '<', $factory->created_at)
                ->sum('monto');
        }

        $montoCedidoActual = max($montoCedidoActual, 0);

        $saldoLiquido = (int) ($factory->saldo_liquido ?? 0);

        /*
        |--------------------------------------------------------------------------
        | Compatibilidad con registros históricos
        |--------------------------------------------------------------------------
        | Los registros anteriores a la integración no tienen informado
        | monto_no_anticipado ni diferencia_precio.
        |
        | En esos casos se conserva el cálculo anterior para no inventar una
        | composición histórica que no fue registrada originalmente.
        |--------------------------------------------------------------------------
        */
        $usaNuevaEstructuraFactory =
            $factory->monto_no_anticipado !== null ||
            $factory->diferencia_precio !== null;

        if ($usaNuevaEstructuraFactory) {
            /*
            |--------------------------------------------------------------------------
            | Nueva estructura Factoring
            |--------------------------------------------------------------------------
            | diferencia_precio reemplaza funcionalmente a diferencia:
            |
            | diferencia_precio = monto - saldo_liquido - monto_no_anticipado
            |--------------------------------------------------------------------------
            */
            $montoNoAnticipado = (int) ($factory->monto_no_anticipado ?? 0);

            $diferenciaPrecioActual = max(
                $montoCedidoActual - $saldoLiquido - $montoNoAnticipado,
                0
            );

            $saldo = $diferenciaPrecioActual;

            if ($persistirFactory) {
                $factory->update([
                    'monto' => $montoCedidoActual,
                    'diferencia_precio' => $diferenciaPrecioActual,
                ]);
            }
        } else {
            /*
            |--------------------------------------------------------------------------
            | Estructura histórica
            |--------------------------------------------------------------------------
            | Se mantiene exclusivamente para registros antiguos mientras
            | diferencia aún exista en la tabla.
            |--------------------------------------------------------------------------
            */
            $diferenciaLegacy = max($montoCedidoActual - $saldoLiquido, 0);

            $saldo = $diferenciaLegacy;

            if ($persistirFactory) {
                $factory->update([
                    'monto' => $montoCedidoActual,
                    'diferencia' => $diferenciaLegacy,
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Movimientos posteriores al Factoring
        |--------------------------------------------------------------------------
        | Se mantiene la regla existente: abonos o cruces posteriores reducen
        | el saldo que todavía permanezca vigente.
        |--------------------------------------------------------------------------
        */
        if ($factory->created_at) {
            $saldo -= (int) $this->abonos()
                ->where('created_at', '>', $factory->created_at)
                ->sum('monto');

            $saldo -= (int) $this->cruces()
                ->where('created_at', '>', $factory->created_at)
                ->sum('monto');
        }

        return max($saldo, 0);
    }





    protected function calcularSaldoBaseDocumento(): int
    {
        $saldo = (int) ($this->monto_total ?? 0);

        $saldo -= (int) $this->referenciados()
            ->where('tipo_documento_id', 61)
            ->sum('monto_total');

        $saldo += (int) $this->referenciados()
            ->where('tipo_documento_id', 56)
            ->sum('monto_total');

        return max($saldo, 0);
    }

    protected function esNotaCredito(): bool
    {
        if ((int) $this->tipo_documento_id === 61) {
            return true;
        }

        if ($this->relationLoaded('tipoDocumento') && $this->tipoDocumento) {
            return str_contains(
                strtolower($this->tipoDocumento->nombre),
                'nota de crédito'
            );
        }

        return false;
    }

    public function resolverEstadoManualVigente(): array
    {
        $movimientos = collect();

        $agregarMovimiento = function (
            ?string $status,
            $fechaMovimiento,
            $createdAt,
            int $prioridad
        ) use (&$movimientos) {
            if (!$status) {
                return;
            }

            $fechaOrden = $createdAt ?: $fechaMovimiento;

            if (!$fechaOrden) {
                return;
            }

            try {
                $timestamp = Carbon::parse($fechaOrden)->timestamp;
            } catch (\Throwable $e) {
                $timestamp = 0;
            }

            $movimientos->push([
                'status' => $status,
                'fecha' => $fechaMovimiento ?: $createdAt,
                'orden' => ($timestamp * 100) + $prioridad,
            ]);
        };

        foreach ($this->abonos()->get(['id', 'fecha_abono', 'created_at']) as $abono) {
            $agregarMovimiento('Abono', $abono->fecha_abono, $abono->created_at, 10);
        }

        foreach ($this->cruces()->get(['id', 'fecha_cruce', 'created_at']) as $cruce) {
            $agregarMovimiento('Cruce', $cruce->fecha_cruce, $cruce->created_at, 20);
        }

        $factory = $this->factoryRegistro()->first(['id', 'fecha_factory', 'created_at']);

        if ($factory) {
            $agregarMovimiento('Factory', $factory->fecha_factory, $factory->created_at, 30);
        }

        foreach ($this->prontoPagos()->get(['id', 'fecha_pronto_pago', 'created_at']) as $prontoPago) {
            $agregarMovimiento('Pronto pago', $prontoPago->fecha_pronto_pago, $prontoPago->created_at, 40);
        }

        foreach ($this->pagos()->get(['id', 'fecha_pago', 'created_at']) as $pago) {
            $agregarMovimiento('Pago', $pago->fecha_pago, $pago->created_at, 50);
        }

        $estadosRespaldadosPorTabla = [
            'Abono',
            'Cruce',
            'Pago',
            'Pronto pago',
            'Factory',
        ];

        if ($this->status && !in_array($this->status, $estadosRespaldadosPorTabla, true)) {
            $agregarMovimiento(
                $this->status,
                $this->fecha_estado_manual,
                null,
                60
            );
        }

        $ultimoMovimiento = $movimientos
            ->sortByDesc('orden')
            ->first();

        return [
            'status' => $ultimoMovimiento['status'] ?? null,
            'fecha' => $ultimoMovimiento['fecha'] ?? null,
        ];
    }

    public function resolverStatusOriginalActual(): string
    {
        if (!$this->fecha_vencimiento) {
            return 'Sin cálculo';
        }

        return now()->gt(Carbon::parse($this->fecha_vencimiento))
            ? 'Vencido'
            : 'Al día';
    }

    public function sincronizarEstadosDesdeMovimientos(): ?string
    {
        $estadoManual = $this->resolverEstadoManualVigente();

        $status = $estadoManual['status'] ?? null;
        $fecha = $estadoManual['fecha'] ?? null;

        $this->update([
            'status' => $status,
            'status_original' => $this->resolverStatusOriginalActual(),
            'fecha_estado_manual' => $status
                ? ($fecha ? Carbon::parse($fecha)->toDateString() : now()->toDateString())
                : null,
        ]);

        return $status;
    }

    public function actualizarFechaVencimiento()
    {
        $this->loadMissing('cobranza');

        if (!$this->fecha_docto || !$this->cobranza) {
            return;
        }

        $creditos = $this->cobranza->creditos;

        if ($creditos === null || $creditos === '') {
            return;
        }

        $fechaVencimiento = Carbon::parse($this->fecha_docto)
            ->addDays((int) $creditos)
            ->format('Y-m-d');

        $this->fecha_vencimiento = $fechaVencimiento;

        $this->status_original = Carbon::parse($fechaVencimiento)->isPast()
            ? 'Vencido'
            : 'Al día';

        $this->save();
    }

    public function getEstadoVisibleAttribute()
    {
        if ($this->status) {
            return $this->status === 'Pago'
                ? 'Pagado'
                : $this->status;
        }

        return $this->status_final;
    }

    public function getFechaUltimaTransaccionAttribute()
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

        if ($this->factoryRegistro) {
            $fechas->push(
                $this->factoryRegistro->fecha_factory
            );
        }

        return $fechas->filter()->max();
    }
}