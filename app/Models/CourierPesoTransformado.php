<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourierPesoTransformado extends Model
{
    use HasFactory;

    public const ORIGEN_CATALOGO = 'catalogo';
    public const ORIGEN_REGLA = 'regla';

    protected $table = 'courier_pesos_transformados';

    protected $fillable = [
        'texto',
        'peso',
        'origen',
    ];

    protected $casts = [
        'peso' => 'integer',
    ];

    /*
     * Regla para un texto que no está en el catálogo. Replica
     * ControlPesosPendientes!B: =MAX(1;VALOR(IZQUIERDA(A2;ENCONTRAR(".";A2)-1)))
     *
     * "18.22 kg" → 18   "0.34 kg" → 1   "X" → 1
     *
     * Se trunca, no se redondea: es lo que hace el catálogo en 580 de 582 casos.
     */
    public static function porRegla(string $texto): int
    {
        if (preg_match('/^\s*(\d+)/', $texto, $m) !== 1) {
            return 1;
        }

        return max(1, (int) $m[1]);
    }
}