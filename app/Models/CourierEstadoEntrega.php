<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourierEstadoEntrega extends Model
{
    use HasFactory;

    public const PAGAR = 'PAGAR';
    public const DESCONTAR = 'DESCONTAR';

    protected $table = 'courier_estados_entrega';

    protected $fillable = [
        'estado',
        'considerar',
    ];

    public function descuenta(): bool
    {
        return $this->considerar === self::DESCONTAR;
    }
}