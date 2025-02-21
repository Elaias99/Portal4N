<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    use HasFactory;

    protected $fillable = ['Nombre'];


    public function trabajadors(){

        return $this->hasMany(Trabajador::class, 'cargo_id');

    }
}
