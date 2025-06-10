<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subzona extends Model
{
    use HasFactory;

    protected $table = 'subzonas';

    protected $fillable = ['nombre'];

    public function clasificaciones()
    {
        return $this->hasMany(ComunaClasificacionOperativa::class);
    }



}
