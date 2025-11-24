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
        return CobranzaCompra::query()
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
            'Servicio',
            'Créditos (días)',
        ];
    }

    public function map($cobranza): array
    {
        return [
            optional($cobranza->created_at)->format('d-m-Y H:i'),
            $cobranza->rut_cliente ?? '—',
            $cobranza->razon_social ?? '—',
            $cobranza->servicio ?? '—',
            $cobranza->creditos ?? '—',
        ];
    }
}
