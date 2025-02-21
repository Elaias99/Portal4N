<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;

    protected $fillable = ['Nombre', 'Numero'];


    public function comunas()
    {
        return $this->hasMany(Comuna::class);
    }


}
