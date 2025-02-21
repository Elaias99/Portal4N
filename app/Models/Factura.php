<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    use HasFactory;

    protected $table = 'facturas';

    // Campos que se pueden asignar en masa
    protected $fillable = [
        'proveedor_id',
        'centro_costo',
        'empresa_id',
        'glosa',
        'pagador',
        'status',
        'tipo_documento',
        'comentario',
    ];

    // Relación con el modelo Proveedor
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }


    public function getFechaPagoAttribute()
    {
        $tipoPago = $this->proveedor->tipo_pago; // Obtener el tipo de pago del proveedor
        $fechaEmision = $this->created_at;

        // Calcular la fecha base según el tipo de pago
        switch ($tipoPago) {
            case 'Contado':
                $fechaBase = $fechaEmision;
                break;
            case 'Quincena':
                $dia = $fechaEmision->day <= 15 ? 15 : $fechaEmision->copy()->endOfMonth()->day;
                $fechaBase = $fechaEmision->copy()->day($dia);
                break;
            case '30 Días':
            case '45 Días':
            case '60 Días':
                $dias = (int) filter_var($tipoPago, FILTER_SANITIZE_NUMBER_INT);
                $fechaBase = $fechaEmision->addDays($dias);
                break;
            case 'Otro':
                return null; // O aplicar lógica personalizada
            default:
                $fechaBase = $fechaEmision; // Si no se reconoce el tipo de pago
        }

        // Ajustar al viernes más cercano
        $diaSemana = $fechaBase->dayOfWeek; // 0 = domingo, 5 = viernes
        if ($diaSemana != 5) {
            $diasParaViernes = ($diaSemana < 5) ? (5 - $diaSemana) : (12 - $diaSemana);
            $fechaBase->addDays($diasParaViernes);
        }

        return $fechaBase;
    }


}
