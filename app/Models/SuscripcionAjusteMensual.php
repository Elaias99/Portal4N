<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuscripcionAjusteMensual extends Model
{
    use HasFactory;

    protected $table = 'suscripcion_ajustes_mensuales';

    protected $fillable = [
        'suscripcion_asignacion_id',
        'anio',
        'mes',
        'tipo_ajuste',

        'punto_1',
        'origen_gasto',
        'punto_2',
        'codigo',
        'servicio',

        'suscripcion_transportista_override_id',
        'suscripcion_proveedor_facturacion_id',

        'tipo_documento',
        'detalle_documento',
        'detalle_impuesto',
        'final',

        'grupo_prefactura',

        'costo',
        'q_calendario',
        'q_inasistencia',
        'cantidad',
        'total',

        'observacion',
        'activo',

        'concepto_pago_variable_id',
        'concepto_pago_variable_manual',
        'concepto_pago_variable_snapshot',
    ];

    protected $casts = [
        'anio' => 'integer',
        'mes' => 'integer',
        'costo' => 'integer',
        'q_calendario' => 'integer',
        'q_inasistencia' => 'integer',
        'cantidad' => 'integer',
        'total' => 'integer',
        'activo' => 'boolean',
    ];

    public function asignacion()
    {
        return $this->belongsTo(Asignaciones::class, 'suscripcion_asignacion_id');
    }

    public function proveedorFacturacion()
    {
        return $this->belongsTo(SuscripcionProveedor::class, 'suscripcion_proveedor_facturacion_id');
    }

    public function transportistaOverride()
    {
        return $this->belongsTo(SuscripcionTransportista::class, 'suscripcion_transportista_override_id');
    }

    public function conceptoPagoVariable()
    {
        return $this->belongsTo(SuscripcionConceptoPagoVariable::class, 'concepto_pago_variable_id');
    }






}