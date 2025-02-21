<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model {
    
    use HasFactory;

    protected $fillable = ['trabajador_id', 'fecha', 'asistio'];

    public function trabajador() {
        return $this->belongsTo(Trabajador::class);
    }
}
