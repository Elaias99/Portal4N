<?php

namespace App\Exports;

use App\Models\CobranzaCompra;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CobranzaCompraExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $fechaInicio;
    protected $fechaFin;

    public function __construct($fechaInicio = null, $fechaFin = null)
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
    }

    public function collection()
    {
        return CobranzaCompra::with(['banco', 'tipoCuenta'])
            ->when($this->fechaInicio, fn($q) =>
                $q->whereDate('created_at', '>=', $this->fechaInicio)
            )
            ->when($this->fechaFin, fn($q) =>
                $q->whereDate('created_at', '<=', $this->fechaFin)
            )
            ->orderByDesc('created_at')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Fecha de Creación',
            'RUT Cliente',
            'Razón Social',
            'Servicio / Detalle',
            'Créditos (días)',

            // Nuevos campos
            'Tipo',
            'Facturación',
            'Forma de Pago',
            'Zona',
            'Importancia',
            'Responsable',

            // Datos bancarios
            'Nombre Cuenta',
            'RUT Cuenta',
            'Banco',
            'Tipo Cuenta',
            'Número Cuenta',
        ];
    }

    public function map($c): array
    {
        return [
            optional($c->created_at)->format('d-m-Y H:i'),
            $c->rut_cliente ?? '—',
            $c->razon_social ?? '—',
            $c->servicio ?? '—',
            $c->creditos ?? '—',

            // Nuevos campos
            $c->tipo ?? '—',
            $c->facturacion ?? '—',
            $c->forma_pago ?? '—',
            $c->zona ?? '—',
            $c->importancia ?? '—',
            $c->responsable ?? '—',

            // Bancarios
            $c->nombre_cuenta ?? '—',
            $c->rut_cuenta ?? '—',
            $c->banco->nombre ?? '—',
            $c->tipoCuenta->nombre ?? '—',
            $c->numero_cuenta ?? '—',
        ];
    }
}
