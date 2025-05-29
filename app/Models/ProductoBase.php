<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoBase extends Model
{
    use HasFactory;

    protected $fillable = ['codigo','nombre','peso','altura','ancho','profundidad'];
}
