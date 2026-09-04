<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierTarifaTramo extends Model
{
    use HasFactory;

    protected $table = 'courier_tarifa_tramos';

    public $timestamps = false;

    protected $fillable = [
        'courier_tarifa_id',
        'peso',
        'valor',
    ];

    protected $casts = [
        'peso' => 'integer',
        'valor' => 'integer',
    ];

    public function tarifa(): BelongsTo
    {
        return $this->belongsTo(
            CourierTarifa::class,
            'courier_tarifa_id'
        );
    }
}