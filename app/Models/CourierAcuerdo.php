<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * Un pago fijo del mes a un proveedor (hoja Base Acuerdos).
 * total = costo × cantidad × factor, donde
 * cantidad = días del calendario − inasistencias + adicionales.
 */
class CourierAcuerdo extends Model
{
    public const TIPO_PAGO = 'Acuerdos';

    protected $table = 'courier_acuerdos';

    protected $fillable = [
        'courier_periodo_id',
        'fila_origen',
        'proveedor',
        'rut_proveedor',
        'courier_proveedor_id',
        'zona',
        'agencia',
        'tipo_servicio',
        'marca',
        'servicio',
        'costo',
        'dias_calendario',
        'inasistencias',
        'adicionales',
        'cantidad',
        'glosa_factor',
        'factor',
        'total',
        'razon_social_cliente',
        'comerciante',
        'rut_cliente',
        'nombre_comercial',
        'empresa_mandante',
        'archivo_origen',
    ];

    protected $casts = [
        'costo' => 'integer',
        'dias_calendario' => 'integer',
        'inasistencias' => 'integer',
        'adicionales' => 'integer',
        'cantidad' => 'integer',
        'factor' => 'integer',
        'total' => 'integer',
    ];

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(CourierPeriodo::class, 'courier_periodo_id');
    }

    public function proveedorCatalogo(): BelongsTo
    {
        return $this->belongsTo(CourierProveedor::class, 'courier_proveedor_id');
    }

    public function scopeDelPeriodo(Builder $query, int $periodoId): Builder
    {
        return $query->where('courier_periodo_id', $periodoId);
    }
}
