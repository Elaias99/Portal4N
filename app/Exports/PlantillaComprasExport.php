<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class PlantillaComprasExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return new Collection([
            [
                'Puede ingresar el ID (Ej: 1) o el nombre exacto en la tabla empresas (Ej: 4Nortes SPA)',
                'Puede ingresar el ID del proveedor o el RUT (Ej: 12345678-9)',
                'Puede ingresar el ID o razón social exacta del proveedor',
                'Puede ingresar el ID o nombre exacto en tabla centro_costos (Ej: Courier)',
                'Descripción breve del gasto (Ej: Transporte, Arriendo)',
                'Campo opcional para comentarios adicionales',
                'Debe coincidir con nombre en tipo_documentos o ingresar su ID (Ej: FACTURA o 1)',
                'Debe coincidir con nombre en plazo_pagos o su ID (Ej: 30 Días o 2)',
                'Debe coincidir con nombre en forma_pagos o su ID (Ej: 4N - Transferencia o 1)',
                'Monto total del documento, solo números (Ej: 250000)',
                'Fecha de vencimiento (Ej: 2025-01-30)',
                'Año numérico (Ej: 2025)',
                'Mes en texto (Ej: Enero)',
                'Fecha del documento (Ej: 2024-12-30)',
                'Número del documento (Ej: F123456)',
                'Orden de compra si aplica (Ej: OC-2025-01)',
                'Estado inicial: Pendiente, Pagado, Abonado, No Pagar',
                'Nombre del usuario que figura en el sistema (Ej: B.Rojas)', 
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'empresa',
            'rut',
            'proveedor',
            'centro_costo',
            'glosa',
            'observacion',
            'tipo_de_documento',
            'plazo_pago',
            'forma_pago',
            'pago_total',
            'fecha_vencimiento',
            'ano',
            'mes',
            'fecha_documento',
            'numero_documento',
            'oc',
            'status',
            'usuario',
        ];
    }
}
