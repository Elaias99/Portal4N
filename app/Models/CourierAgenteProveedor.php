<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierAgenteProveedor extends Model
{
    //
    use HasFactory;

    protected $table = 'courier_agente_proveedors';

    protected $fillable = [
        'courier_agente_id',
        'nombre_proveedor',
        'principal',
    ];

    protected $casts = [
        'principal' => 'boolean',
    ];

    public function agente(): BelongsTo
    {
        return $this->belongsTo(
            CourierAgentes::class,
            'courier_agente_id'
        );
    }





}
