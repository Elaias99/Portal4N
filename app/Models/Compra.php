<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    use HasFactory;

    protected $table = 'compras'; // Nombre de la tabla

    // Campos que se pueden asignar en masa
    protected $fillable = [
        'empresa_id',
        'proveedor_id',
        'centro_costo',
        'glosa',
        'observacion',
        'tipo_pago',
        'forma_pago',
        'pago_total',
        'fecha_vencimiento',
        'año',
        'mes',

        'fecha_documento',
        'numero_documento',
        'oc',
        'archivo_oc',
        'archivo_documento',

        'tipo_documento',
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





}
