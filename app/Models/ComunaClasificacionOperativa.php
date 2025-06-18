<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComunaClasificacionOperativa extends Model
{
    use HasFactory;

    protected $table = 'comuna_clasificacion_operativa';

    protected $fillable = [
        'comuna_id',
        'zona_id',
        'tipo_zona_id',
        'subzona_id',
        'comuna_matriz',
        'proveedor_id',
        'zona_ruta_geografica_id',
        'cobertura_id',
        'provincia_id',
        'iata_id',
        

    ];

    public function comuna()
    {
        return $this->belongsTo(Comuna::class);
    }

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }

    public function tipoZona()
    {
        return $this->belongsTo(TipoZona::class);
    }

    public function subzona()
    {
        return $this->belongsTo(Subzona::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(\App\Models\Proveedor::class);
    }

    public function frecuenciaDistribucion()
    {
        return $this->hasOne(\App\Models\FrecuenciaDistribucion::class, 'comuna_id', 'comuna_id')
                    ->whereColumn('proveedor_id', 'proveedor_id');
    }

    public function zonaRutaGeografica()
    {
        return $this->belongsTo(RutaGeografica::class, 'zona_ruta_geografica_id');
    }

    public function cobertura()
    {
        return $this->belongsTo(Cobertura::class);
    }

    public function provincia()
    {
        return $this->belongsTo(Provincia::class);
    }

    public function codigoiata()
    {
        return $this->belongsTo(CodigoIata::class, 'iata_id');
    }





}
