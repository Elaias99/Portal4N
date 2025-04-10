<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PlantillaProveedoresExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        // Sin datos, solo encabezados
        return new Collection([]);
    }

    public function headings(): array
    {
        return [
            'razon_social',
            'rut',
            'banco',
            'tipo_cuenta',
            'nro_cuenta',
            'tipo_pago',
            'telefono_empresa',
            'nombre_representantelegal',
            'rut_representantelegal',
            'telefono_representantelegal',
            'correo_representantelegal',
            'contacto_nombre',
            'contacto_telefono',
            'contacto_correo',
            'giro_comercial',
            'direccion_facturacion',
            'direccion_despacho',
            'nombre_contacto2',
            'telefono_contacto2',
            'correo_contacto2',
            'correo_banco',
            'nombre_razon_social_banco',
            'cargo_contacto1',
            'cargo_contacto2',
            'comuna_empresa',
        ];
    }
}
