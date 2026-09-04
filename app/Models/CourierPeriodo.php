<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourierPeriodo extends Model
{
    use HasFactory;

    protected $table = 'courier_periodos';

    protected $fillable = [
        'codigo',
        'anio',
        'mes',
        'estado',
        'observacion',
    ];

    protected $casts = [
        'anio' => 'integer',
        'mes' => 'integer',
    ];

    /*
     * Etiqueta legible para el selector de período.
     * Ejemplo: "Agosto 2026".
     */
    public function getNombreAttribute(): string
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre',
            12 => 'Diciembre',
        ];

        return ($meses[$this->mes] ?? $this->mes) . ' ' . $this->anio;
    }

    public function estaCerrado(): bool
    {
        return $this->estado === 'cerrado';
    }
}