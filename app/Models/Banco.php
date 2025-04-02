<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banco extends Model
{

    protected $table = 'bancos';
    use HasFactory;
    protected $fillable = [
        'nombre'
    ];

    public function empresa()
    {
        return $this->hasMany(Empresa::class);
    }




}
