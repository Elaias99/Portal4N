<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comuna extends Model
{
    use HasFactory;

    protected $fillable = ['Nombre',
    'region_id',];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function empresa()
    {
        return $this->hasMany(Empresa::class);
    }

    public function clasificacionOperativa()
    {
        return $this->hasOne(ComunaClasificacionOperativa::class);
    }



}
