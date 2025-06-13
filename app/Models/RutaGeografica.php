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
    ];

    public function tipoZona()
    {
        return $this->belongsTo(TipoZona::class);
    }

    public function transporte()
    {
        return $this->belongsTo(Transporte::class);
    }





}
