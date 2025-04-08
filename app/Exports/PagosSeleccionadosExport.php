<?php

namespace App\Exports;

use App\Models\Compra;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PagosSeleccionadosExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithMapping
{
    protected $compras;

    protected $acumulado = 0;


    public function __construct($comprasSeleccionadas)
    {
        $this->compras = Compra::with(['proveedor.banco', 'empresa', 'tipoPago'])
            ->whereIn('id', $comprasSeleccionadas)
            ->get();
    }

    public function collection()
    {
        return $this->compras;
    }

    public function map($compra): array
    {
        $proveedor = $compra->proveedor;

        $nombreBanco = isset($proveedor->banco->id)
            ? str_pad($proveedor->banco->id, 4, '0', STR_PAD_LEFT)
            : 'N/A';

        $nombreTipoPago = $compra->tipoPago->nombre ?? 'N/A';

        $glosa = "Pago {$nombreTipoPago} {$compra->numero_documento}";
        $clp = "CLP";

        $ctaEmpresa = str_pad($compra->empresa->cta_corriente ?? '', 15, '0', STR_PAD_LEFT);
        $ctaProveedor = str_pad($proveedor->nro_cuenta ?? '', 22, '0', STR_PAD_LEFT);

        $rutLimpio = str_pad(preg_replace('/[.\-]/', '', $proveedor->rut), 10, '0', STR_PAD_LEFT);


        // Acumular el monto progresivamente
        $this->acumulado += $compra->pago_total;

        return [
            $ctaEmpresa,
            $clp,
            $ctaProveedor,
            $clp,
            $nombreBanco,
            $rutLimpio,
            $proveedor->razon_social,
            $compra->pago_total,

            $glosa,
            $proveedor->correo_banco,
            $glosa,
            $glosa,
            $glosa,
        ];
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
