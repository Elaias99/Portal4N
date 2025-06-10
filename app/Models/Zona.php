<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zona extends Model
{
    use HasFactory;

    protected $table = 'zonas';

    protected $fillable = ['nombre', 'zona_madre_id'];

    public function zonaMadre()
    {
        return $this->belongsTo(ZonaMadre::class);
    }

    public function clasificaciones()
    {
        return $this->hasMany(ComunaClasificacionOperativa::class);
    }


}
