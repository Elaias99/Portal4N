<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TipoDocumento;


class Compra extends Model
{
    use HasFactory;

    protected $table = 'compras'; // Nombre de la tabla

    // Campos que se pueden asignar en masa
    protected $fillable = [
        'empresa_id',
        'proveedor_id',

        'centro_costo_id',


        'glosa',
        'observacion',
        'tipo_pago_id',
        // 'tipo_pago',
        'plazo_pago_id',


        'forma_pago_id',

        
        'pago_total',
        'fecha_vencimiento',
        'año',
        'mes',

        'fecha_documento',
        'numero_documento',
        'oc',
        'archivo_oc',
        'archivo_documento',

  
        'user_id',
        'status',
    ];

    // Relación con el modelo Proveedor
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tipoPago()
    {
        return $this->belongsTo(TipoDocumento ::class, 'tipo_pago_id');
    }

    public function centroCosto()
    {
        return $this->belongsTo(CentroCosto::class, 'centro_costo_id');
    }

    public function formaPago()
    {
        return $this->belongsTo(FormaPago::class, 'forma_pago_id');
    }

    public function plazoPago()
    {
        return $this->belongsTo(PlazoPago::class, 'plazo_pago_id');
    }







}
