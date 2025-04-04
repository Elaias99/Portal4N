<?php

namespace App\Exports;

use App\Models\Proveedor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProveedorExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithMapping
{
    public function collection()
    {
        // Cargamos también relaciones necesarias
        return Proveedor::with(['compras.tipoPago', 'banco'])->get();
    }

    public function map($proveedor): array
    {
        $filas = [];

        foreach ($proveedor->compras as $compra) {
            $nombreBanco = $proveedor->banco->id ?? 'N/A';
            $nombreTipoPago = $compra->tipoPago->nombre ?? 'N/A';

            $glosa = "Pago  {$nombreTipoPago}  {$compra->numero_documento}";
            $clp = "CLP";

            $cuentaEmpresa = $compra->empresa->cta_corriente ?? '';
            $ctaCorrienteFormateada = str_pad($cuentaEmpresa, 15, '0', STR_PAD_LEFT);



            $filas[] = [
                $ctaCorrienteFormateada,

                $clp,

                str_pad($proveedor->nro_cuenta, 22, '0', STR_PAD_LEFT),

                $clp,

                $nombreBanco,

                preg_replace('/[.\-]/', '', $proveedor->rut),

                $proveedor->razon_social,

                $compra->pago_total,

                $glosa,

                $proveedor->correo_banco,
                
                
                $glosa,
                $glosa,
                $glosa,


            ];
        }

        if (empty($filas)) {
            $nombreBanco = $proveedor->banco->nombre ?? 'N/A';

            $filas[] = [
                $ctaCorrienteFormateada,

                $clp,

                $proveedor->nro_cuenta,

                $clp,

                $nombreBanco,

                $proveedor->rut,

                $proveedor->razon_social,

                $proveedor->correo_banco,
                'N/A',
                'N/A',
                'N/A',
                'N/A',
                'N/A',

            ];
            
        }

        return $filas[0];
    }

    public function headings(): array
    {
        return [
            'Cuenta Origen',
            'Moneda Cuenta de Origen',
            'Número de Cuenta',
            'Moneda Cuenta de Destino',
            'Banco',
            'RUT',
            'Razón Social',
            'Pago Total',

            'Glosa Transferencia',
            'Correo Banco',
  
            
            'Glosa Correo Beneficiario',
            'Glosa Cartola Cliente',
            'Glosa Cartola Beneficiario',
            
            
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
