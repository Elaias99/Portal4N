<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierControl extends Model
{
    use HasFactory;

    public const ESPECIAL = 'especial';
    public const RETORNO = 'retorno';
    public const BLUE = 'blue';
    public const PAGADO_MES_ANTERIOR = 'pagado_mes_anterior';

    /* Etiqueta legible para las pantallas. */
    public const NOMBRES = [
        self::ESPECIAL => 'Pago especial autorizado',
        self::RETORNO => 'Retorno',
        self::BLUE => 'Enviado por Blue Express',
        self::PAGADO_MES_ANTERIOR => 'Pagado el mes anterior',
    ];

    protected $table = 'courier_controles';

    protected $fillable = [
        'courier_periodo_id',
        'tipo',
        'seguimiento',
        'valor',
        'archivo_origen',
    ];

    protected $casts = [
        'valor' => 'integer',
    ];

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(CourierPeriodo::class, 'courier_periodo_id');
    }

    public function scopeDelPeriodo(Builder $query, int $periodoId): Builder
    {
        return $query->where('courier_periodo_id', $periodoId);
    }

    public function scopeDeTipo(Builder $query, string $tipo): Builder
    {
        return $query->where('tipo', $tipo);
    }
}
