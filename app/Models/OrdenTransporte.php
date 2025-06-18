<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenTransporte extends Model
{
    use HasFactory;

    protected $table = 'ordenes_transporte';

    protected $fillable = [
        'comuna_id',
        'zona_ruta_geografica_id',
        'orden',
    ];

    public function comuna()
    {
        return $this->belongsTo(Comuna::class);
    }

    public function rutaGeografica()
    {
        return $this->belongsTo(RutaGeografica::class, 'zona_ruta_geografica_id');
    }


}
