<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReclamoComentario extends Model
{
    use HasFactory;

    protected $fillable = ['reclamo_id', 'user_id', 'comentario','foto_comentario'];



    public function reclamo()
    {
        return $this->belongsTo(\App\Models\Reclamos::class);
    }

    public function autor()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

}
