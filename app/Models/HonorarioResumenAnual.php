<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HonorarioResumenAnual extends Model
{
    use HasFactory;

    protected $table = 'honorarios_resumen_anual';

    protected $fillable = [
            'rut_contribuyente',
            'razon_social',
            'anio',
            'mes',
            'mes_nombre',
            'folio_inicial',
            'folio_final',
            'boletas_vigentes',
            'boletas_nulas',
            'honorario_bruto',
            'retenciones',
            'total_liquido',
    ];

}
