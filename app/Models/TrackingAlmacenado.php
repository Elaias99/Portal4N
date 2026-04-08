<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackingAlmacenado extends Model
{
    use HasFactory;

    protected $table = 'trackings_almacenados';

    protected $fillable = [
        'prefijo',
        'codigo_tracking',
        'fecha_proceso',
        'destino',
    ];
}
