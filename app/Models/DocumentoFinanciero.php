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




    public function factories()
    {
        return $this->hasMany(Factory::class, 'documento_financiero_id');
    }

    /**
     * Compatibilidad temporal con código que aún muestra un único Factoring.
     * Devuelve el Factoring más reciente mientras se migran vistas y controladores.
     */
    public function factoryRegistro()
    {
        return $this->hasOne(Factory::class, 'documento_financiero_id')
            ->latestOfMany();
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
        /*
        |--------------------------------------------------------------------------
        | Movimientos de cierre
        |--------------------------------------------------------------------------
        | Se conserva la regla vigente: Pago y Pronto pago dejan el documento
        | completamente cerrado, independientemente de movimientos anteriores.
        |--------------------------------------------------------------------------
        */
        if ($this->pagos()->exists()) {
            return 0;
        }

        if ($this->prontoPagos()->exists()) {
            return 0;
        }

        /*
        |--------------------------------------------------------------------------
        | Nota de Crédito propia
        |--------------------------------------------------------------------------
        | Una Nota de Crédito no mantiene saldo pendiente propio.
        |--------------------------------------------------------------------------
        */
        if ($this->esNotaCredito()) {
            return 0;
        }

        /*
        |--------------------------------------------------------------------------
        | Saldo inicial del documento
        |--------------------------------------------------------------------------
        | Incluye monto original menos Notas de Crédito referenciadas
        | más Notas de Débito referenciadas.
        |--------------------------------------------------------------------------
        */
        $saldo = $this->calcularSaldoBaseDocumento();

        /*
        |--------------------------------------------------------------------------
        | Movimientos que modifican progresivamente el saldo
        |--------------------------------------------------------------------------
        | No se utiliza MovimientoDocumento para este cálculo, porque esa tabla
        | corresponde a trazabilidad histórica. Aquí se consideran únicamente
        | registros operativos que continúan existiendo.
        |--------------------------------------------------------------------------
        */
        $movimientos = collect();

        $obtenerOrden = function ($createdAt, $fechaMovimiento): int {
            $fechaOrden = $createdAt ?: $fechaMovimiento;

            if (!$fechaOrden) {
                return 0;
            }

            try {
                return Carbon::parse($fechaOrden)->timestamp;
            } catch (\Throwable $e) {
                return 0;
            }
        };

        foreach ($this->abonos()->get([
            'id',
            'monto',
            'fecha_abono',
            'created_at',
        ]) as $abono) {
            $movimientos->push([
                'tipo' => 'Abono',
                'registro_id' => (int) $abono->id,
                'orden' => $obtenerOrden($abono->created_at, $abono->fecha_abono),
                'prioridad_desempate' => 10,
                'monto' => (int) $abono->monto,
                'registro' => $abono,
            ]);
        }

        foreach ($this->cruces()->get([
            'id',
            'monto',
            'fecha_cruce',
            'created_at',
        ]) as $cruce) {
            $movimientos->push([
                'tipo' => 'Cruce',
                'registro_id' => (int) $cruce->id,
                'orden' => $obtenerOrden($cruce->created_at, $cruce->fecha_cruce),
                'prioridad_desempate' => 20,
                'monto' => (int) $cruce->monto,
                'registro' => $cruce,
            ]);
        }

        foreach ($this->factories()->get([
            'id',
            'documento_financiero_id',
            'fecha_factory',
            'monto',
            'saldo_liquido',
            'monto_no_anticipado',
            'diferencia_precio',
            'created_at',
        ]) as $factory) {
            $movimientos->push([
                'tipo' => 'Factory',
                'registro_id' => (int) $factory->id,
                'orden' => $obtenerOrden($factory->created_at, $factory->fecha_factory),
                'prioridad_desempate' => 30,
                'monto' => null,
                'registro' => $factory,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Orden de aplicación
        |--------------------------------------------------------------------------
        | Se mantiene la regla ya utilizada por Factoring: el created_at define
        | qué movimientos existían antes o después de registrar una operación.
        |
        | La prioridad solo resuelve empates de timestamp manteniendo el criterio
        | vigente: Abono, Cruce y luego Factoring.
        |--------------------------------------------------------------------------
        */
        $movimientos = $movimientos
            ->sort(function (array $a, array $b): int {
                if ($a['orden'] !== $b['orden']) {
                    return $a['orden'] <=> $b['orden'];
                }

                if ($a['prioridad_desempate'] !== $b['prioridad_desempate']) {
                    return $a['prioridad_desempate'] <=> $b['prioridad_desempate'];
                }

                return $a['registro_id'] <=> $b['registro_id'];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Aplicar únicamente movimientos existentes
        |--------------------------------------------------------------------------
        | Abono y Cruce reducen el saldo vigente.
        | Cada Factoring recibe el saldo que queda justo antes de su registro
        | y lo convierte en su diferencia_precio pendiente.
        |--------------------------------------------------------------------------
        */
        foreach ($movimientos as $movimiento) {
            if (in_array($movimiento['tipo'], ['Abono', 'Cruce'], true)) {
                $saldo -= (int) $movimiento['monto'];
                $saldo = max($saldo, 0);

                continue;
            }

            if ($movimiento['tipo'] === 'Factory') {
                $saldo = $this->calcularSaldoConFactory(
                    $movimiento['registro'],
                    $saldo,
                    $persistirFactory
                );
            }
        }

        return max((int) $saldo, 0);
    }




    protected function calcularSaldoConFactory( Factory $factory, int $saldoVigente, bool $persistirFactory = false): int 
    {
        /*
        |--------------------------------------------------------------------------
        | Monto cedido vigente
        |--------------------------------------------------------------------------
        | Cada operación Factoring se aplica sobre el saldo real disponible
        | inmediatamente antes de ese registro.
        |--------------------------------------------------------------------------
        */
        $montoCedidoActual = max($saldoVigente, 0);

        $saldoLiquido = (int) ($factory->saldo_liquido ?? 0);
        $montoNoAnticipado = (int) ($factory->monto_no_anticipado ?? 0);

        /*
        |--------------------------------------------------------------------------
        | Diferencia de Precio
        |--------------------------------------------------------------------------
        | Regla vigente única:
        |
        | diferencia_precio =
        |     monto cedido vigente
        |     - saldo líquido
        |     - monto no anticipado
        |--------------------------------------------------------------------------
        */
        $diferenciaPrecioActual = max(
            $montoCedidoActual - $saldoLiquido - $montoNoAnticipado,
            0
        );

        /*
        |--------------------------------------------------------------------------
        | Persistencia del recálculo
        |--------------------------------------------------------------------------
        | Cuando desaparece o cambia un movimiento anterior, los Factorings
        | posteriores reconstruyen su monto cedido y diferencia_precio.
        |--------------------------------------------------------------------------
        */
        if (
            $persistirFactory &&
            (
                (int) ($factory->monto ?? 0) !== $montoCedidoActual ||
                (int) ($factory->diferencia_precio ?? 0) !== $diferenciaPrecioActual
            )
        ) {
            $factory->update([
                'monto' => $montoCedidoActual,
                'diferencia_precio' => $diferenciaPrecioActual,
            ]);
        }

        return $diferenciaPrecioActual;
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

        foreach ($this->factories()->get(['id', 'fecha_factory', 'created_at']) as $factory) {
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

        if ($this->factories->isNotEmpty()) {
            $fechas->push(
                $this->factories->max('fecha_factory')
            );
        }

        return $fechas->filter()->max();
    }




}