<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class PlantillaComprasExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        // Devuelve una colección vacía para que solo se muestren los encabezados
        return new Collection([]);
    }

    public function headings(): array
    {
        return [
            'empresa', 'proveedor', 'centro_costo', 'glosa', 'observacion', 'tipo_de_documento',
            'plazo_pago', 'forma_pago', 'pago_total', 'fecha_vencimiento', 'ano', 'mes',
            'fecha_documento', 'numero_documento', 'oc', 'status'
        ];
        
    }
}
