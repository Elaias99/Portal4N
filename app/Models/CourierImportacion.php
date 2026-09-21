<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierImportacion extends Model
{
    use HasFactory;

    public const TIPO_GEOLICE = 'geolice';
    public const TIPO_PESAJES = 'pesajes';

    protected $table = 'courier_importaciones';

    protected $fillable = [
        'courier_periodo_id',
        'tipo',
        'archivo',
        'user_id',
        'filas',
        'nuevos',
        'actualizados',
        'duracion_seg',
        'resumen',
    ];

    protected $casts = [
        'filas' => 'integer',
        'nuevos' => 'integer',
        'actualizados' => 'integer',
        'duracion_seg' => 'integer',
        'resumen' => 'array',
    ];

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(
            CourierPeriodo::class,
            'courier_periodo_id'
        );
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    /*
     * Acceso cómodo a las partes del resumen guardado.
     */
    public function conteos(): array
    {
        return $this->resumen['resumen'] ?? [];
    }

    public function estados(): array
    {
        return $this->resumen['estados'] ?? [];
    }

    public function estadosDesconocidos(): array
    {
        return $this->resumen['estados_desconocidos'] ?? [];
    }

    public function comunasFueraDeCatalogo(): array
    {
        return $this->resumen['comunas_fuera_de_catalogo'] ?? [];
    }

    public function sinConfiguracion(): array
    {
        return $this->resumen['sin_configuracion'] ?? [];
    }
}
