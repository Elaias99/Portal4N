<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZonaMadre extends Model
{
    use HasFactory;

    protected $table = 'zona_madres';

    protected $fillable = ['nombre'];

    public function zonas()
    {
        return $this->hasMany(Zona::class);
    }


}
