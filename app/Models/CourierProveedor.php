<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierProveedor extends Model
{
    use HasFactory;

    protected $table = 'courier_proveedores';

    protected $fillable = [
        'operador',
        'usuario',
        'llave',
        'transportista',
        'razon_social',
        'rut',
        'empresa',
        'tipo_documento',
        'titular_banco',
        'rut_titular_banco',
        'banco',
        'tipo_cuenta',
        'nro_cuenta',
        'cobranza_compra_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function cobranzaCompra(): BelongsTo
    {
        return $this->belongsTo(
            CobranzaCompra::class,
            'cobranza_compra_id'
        );
    }

    /*
     * Replica DatosProveedores!C: =+A2&B2
     * Sin separador y sin recortar espacios, igual que Excel.
     */
    public static function llave(string $operador, string $usuario): string
    {
        return mb_strtolower($operador . $usuario);  
    }

    /*
     * Banco!E: el IVA se agrega sólo cuando el tipo es exactamente
     * "Factura". "Factura Exenta" queda fuera a propósito.
     */
    public function emiteFactura(): bool
    {
        return $this->tipo_documento === 'Factura';
    }
}