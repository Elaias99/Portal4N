<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CesionFactory extends Model
{
    use HasFactory;

    protected $table = 'cesiones_factoring';

    protected $fillable = [
        'banco_id',
        'user_id',
        'cesion',
        'fecha_operacion',
        'comision_total',
        'monto_a_recibir',
        'estado_operacion',
    ];

    public function banco()
    {
        return $this->belongsTo(Banco::class, 'banco_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function factories()
    {
        return $this->hasMany(Factory::class, 'cesion_factoring_id');
    }

}
