<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salud extends Model
{
    use HasFactory;

    protected $table = 'saluds'; // Especifica el nombre de la tabla

    protected $fillable = ['Nombre'];
}
