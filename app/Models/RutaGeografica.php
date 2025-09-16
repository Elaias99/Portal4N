<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RutaGeografica extends Model
{
    use HasFactory;


    protected $table = 'zona_ruta_geograficas';

    protected $fillable = [
        'nombre',
        'tipo_zona_id',
        'transporte_id',
        'origen_comuna_id',
        'destino_comuna_id',
        'nombre_ruta'

    ];

    public function tipoZona()
    {
        return $this->belongsTo(TipoZona::class);
    }


    public function origen()
    {
        return $this->belongsTo(Comuna::class, 'origen_comuna_id');
    }

    public function destino()
    {
        return $this->belongsTo(Comuna::class, 'destino_comuna_id');
    }






}
