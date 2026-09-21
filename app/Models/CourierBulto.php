<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierBulto extends Model
{
    use HasFactory;

    protected $table = 'courier_bultos';

    protected $fillable = [
        'courier_periodo_id',
        'archivo_origen',

        // A. Geolice
        'seguimiento',
        'codigo',
        'peso_declarado',
        'largo',
        'ancho',
        'alto',
        'codigo_externo',
        'centro_costo',
        'orden_compra',
        'guia_despacho',
        'estado_entrega',
        'intentos_entrega',
        'comerciante',
        'servicio',
        'campana',
        'destinatario_nombre',
        'destinatario_empresa',
        'direccion',
        'comuna_destino',
        'destinatario_telefono',
        'destinatario_email',
        'valor_envio',
        'fecha_recepcion',
        'entrega_estimada',
        'fecha_entrega',
        'retiro_en_comerciante',
        'bodega_retiro',
        'ruta_entrega',
        'repartidor_nombre',
        'repartidor_telefono',
        'usuario_entrega',

        // B. Cálculo
        'courier_agente_id',
        'zona',
        'courier_configuracion_id',
        'considerar_pago',
        'tabla',
        'peso_bodega',
        'peso_pago',
        'origen_peso',
        'valor',
        'estado_pago',
        'motivo',
        'calculado_at',
    ];

    protected $casts = [
        'peso_declarado' => 'decimal:2',
        'largo' => 'decimal:2',
        'ancho' => 'decimal:2',
        'alto' => 'decimal:2',
        'intentos_entrega' => 'integer',
        'valor_envio' => 'decimal:2',
        'fecha_recepcion' => 'datetime',
        'entrega_estimada' => 'date',
        'fecha_entrega' => 'datetime',
        'retiro_en_comerciante' => 'boolean',
        'tabla' => 'integer',
        'peso_bodega' => 'integer',
        'peso_pago' => 'integer',
        'valor' => 'integer',
        'calculado_at' => 'datetime',
    ];

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(
            CourierPeriodo::class,
            'courier_periodo_id'
        );
    }

    public function agente(): BelongsTo
    {
        return $this->belongsTo(
            CourierAgentes::class,
            'courier_agente_id'
        );
    }

    public function configuracion(): BelongsTo
    {
        return $this->belongsTo(
            CourierConfiguracion::class,
            'courier_configuracion_id'
        );
    }

    /*
     * Cruce por texto con el catálogo de estados (no es FK: Geolice
     * puede informar estados nuevos).
     */
    public function estadoEntrega(): BelongsTo
    {
        return $this->belongsTo(
            CourierEstadoEntrega::class,
            'estado_entrega',
            'estado'
        );
    }

    /*
     * Clave con que este bulto busca su comuna en
     * courier_cobertura_comunas. Misma regla que el catálogo.
     */
    public function claveComuna(): ?string
    {
        return $this->comuna_destino === null
            ? null
            : CourierCoberturaComuna::clave($this->comuna_destino);
    }

    public function scopeDelPeriodo(Builder $query, int $periodoId): Builder
    {
        return $query->where('courier_periodo_id', $periodoId);
    }

    public function scopePagables(Builder $query): Builder
    {
        return $query->where('estado_pago', 'PAGAR');
    }

    public function scopeSinCalcular(Builder $query): Builder
    {
        return $query->whereNull('calculado_at');
    }
}