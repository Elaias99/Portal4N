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
    protected $opcionales;

    public function __construct(array $opcionales = [])
    {
        $this->opcionales = $opcionales;
    }

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
        $data = [];

        foreach ($this->opcionales as $columna) {
            switch ($columna) {
                case 'banco':
                    $data[] = $proveedor->banco->nombre ?? 'Sin banco';
                    break;
                case 'tipo_cuenta':
                    $data[] = $proveedor->tipoCuenta->nombre ?? 'Sin tipo';
                    break;
                case 'tipo_pago':
                    $data[] = $proveedor->tipoPago->nombre ?? 'Sin tipo';
                    break;
                case 'comuna':
                    $data[] = $proveedor->comuna->Nombre ?? 'Sin Comuna';
                    break;
                default:
                    $data[] = $proveedor->{$columna} ?? '—';
                    break;
            }
        }

        return $data;
    }


    public function headings(): array
    {
        $etiquetas = [
            'razon_social' => 'Razón Social',
            'rut' => 'RUT',
            'nro_cuenta' => 'Número de Cuenta',
            'telefono_empresa' => 'Teléfono Empresa',
            'Nombre_RepresentanteLegal' => 'Nombre Representante Legal',
            'Rut_RepresentanteLegal' => 'RUT Representante Legal',
            'Telefono_RepresentanteLegal' => 'Teléfono Representante Legal',
            'Correo_RepresentanteLegal' => 'Correo Representante Legal',
            'contacto_nombre' => 'Contacto 1 - Nombre',
            'contacto_telefono' => 'Contacto 1 - Teléfono',
            'contacto_correo' => 'Contacto 1 - Correo',
            'giro_comercial' => 'Giro Comercial',
            'direccion_facturacion' => 'Dirección Facturación',
            'direccion_despacho' => 'Dirección Despacho',
            'nombre_contacto2' => 'Contacto 2 - Nombre',
            'telefono_contacto2' => 'Contacto 2 - Teléfono',
            'correo_contacto2' => 'Contacto 2 - Correo',
            'correo_banco' => 'Correo Banco',
            'nombre_razon_social_banco' => 'Razón Social Banco',
            'cargo_contacto1' => 'Cargo Contacto 1',
            'cargo_contacto2' => 'Cargo Contacto 2',
            'banco' => 'Banco',
            'tipo_cuenta' => 'Tipo de Cuenta',
            'tipo_pago' => 'Tipo de Documento',
            'comuna' => 'Comuna',
        ];

        return array_map(fn($campo) => $etiquetas[$campo] ?? ucfirst(str_replace('_', ' ', $campo)), $this->opcionales);
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
