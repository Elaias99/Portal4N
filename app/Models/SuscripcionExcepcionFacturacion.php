<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuscripcionExcepcionFacturacion extends Model
{
    use HasFactory;

    protected $table = 'suscripcion_excepciones_facturacion';

    protected $fillable = [
        'suscripcion_asignacion_id',
        'fecha',

        'suscripcion_proveedor_facturacion_id',
        'suscripcion_transportista_override_id',

        'costo',

        'tipo_documento',
        'detalle_documento',
        'detalle_impuesto',
        'final',

        'observacion',
        'activo',
    ];

    protected $casts = [
        'suscripcion_asignacion_id' => 'integer',
        'suscripcion_proveedor_facturacion_id' => 'integer',
        'suscripcion_transportista_override_id' => 'integer',

        'fecha' => 'date',
        'costo' => 'integer',
        'activo' => 'boolean',
    ];

    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(
            Asignaciones::class,
            'suscripcion_asignacion_id'
        );
    }

    public function proveedorFacturacion(): BelongsTo
    {
        return $this->belongsTo(
            SuscripcionProveedor::class,
            'suscripcion_proveedor_facturacion_id'
        );
    }

    public function transportistaOverride(): BelongsTo
    {
        return $this->belongsTo(
            SuscripcionTransportista::class,
            'suscripcion_transportista_override_id'
        );
    }
}