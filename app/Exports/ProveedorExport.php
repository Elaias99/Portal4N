<?php

namespace App\Exports;

use App\Models\Proveedor;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;

use Illuminate\Contracts\Queue\ShouldQueue;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProveedorExport implements FromQuery, WithHeadings, WithStyles, ShouldAutoSize, WithMapping, WithChunkReading, ShouldQueue
{
    public function query()
    {
        return Proveedor::with(['banco', 'tipoCuenta', 'tipoPago', 'comuna']);
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function map($proveedor): array
    {
        return [
            $proveedor->razon_social,
            $proveedor->rut,
            $proveedor->banco->nombre ?? 'Sin Registro',
            $proveedor->tipoCuenta->nombre ?? 'Sin Registro',
            $proveedor->nro_cuenta,
            $proveedor->tipoPago->nombre ?? 'Sin Registro',
            $proveedor->telefono_empresa,
            $proveedor->Nombre_RepresentanteLegal,
            $proveedor->Rut_RepresentanteLegal,
            $proveedor->Telefono_RepresentanteLegal,
            $proveedor->Correo_RepresentanteLegal,
            $proveedor->contacto_nombre,
            $proveedor->contacto_telefono,
            $proveedor->contacto_correo,
            $proveedor->giro_comercial,
            $proveedor->direccion_facturacion,
            $proveedor->direccion_despacho,
            $proveedor->nombre_contacto2,
            $proveedor->telefono_contacto2,
            $proveedor->correo_contacto2,
            $proveedor->correo_banco,
            $proveedor->nombre_razon_social_banco,
            $proveedor->cargo_contacto1,
            $proveedor->cargo_contacto2,
            $proveedor->comuna->Nombre ?? 'Sin Comuna',
        ];
    }

    public function headings(): array
    {
        return [
            'Razón Social',
            'RUT',
            'Banco',
            'Tipo de Cuenta',
            'Número de Cuenta',
            'Tipo de Documento',
            'Teléfono Empresa',
            'Nombre Representante Legal',
            'RUT Representante Legal',
            'Teléfono Representante Legal',
            'Correo Representante Legal',
            'Contacto 1 - Nombre',
            'Contacto 1 - Teléfono',
            'Contacto 1 - Correo',
            'Giro Comercial',
            'Dirección Facturación',
            'Dirección Despacho',
            'Contacto 2 - Nombre',
            'Contacto 2 - Teléfono',
            'Contacto 2 - Correo',
            'Correo Banco',
            'Razón Social Banco',
            'Cargo Contacto 1',
            'Cargo Contacto 2',
            'Comuna',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => '020201'],
                ],
            ],
            'A:Z' => [
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center',
                ],
            ],
        ];
    }
}
