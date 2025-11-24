<?php

namespace App\Exports;

use App\Models\MovimientoCompra;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class MovimientoCompraExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        return MovimientoCompra::with(['compra.empresa', 'compra.tipoDocumento', 'user'])
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
            'Fecha del Movimiento',
            'Empresa',
            'Proveedor',
            'Folio Documento',
            'Tipo Documento',
            'Cambio de Estado',
            'Usuario Responsable',
        ];
    }

    public function map($mov): array
    {
        $fecha = optional($mov->created_at)->format('d-m-Y H:i');
        $empresa = $mov->compra?->empresa?->Nombre ?? '—';
        $proveedor = $mov->compra?->razon_social ?? '—';
        $folio = $mov->compra?->folio ?? '—';
        $tipoDoc = $mov->compra?->tipoDocumento?->nombre ?? '—';
        $usuario = $mov->user?->name ?? '—';

        $estadoAnterior = $mov->estado_anterior;
        $nuevoEstado = $mov->nuevo_estado;

        // === Texto claro y entendible para el usuario final ===
        if (is_null($estadoAnterior) && is_null($nuevoEstado)) {
            $cambio = "El estado que estaba ingresado fue eliminado";
        } elseif ($estadoAnterior === $nuevoEstado) {
            $cambio = "Se registró un movimiento de tipo '{$nuevoEstado}'";
        } else {
            $cambio = "Cambio de estado de '{$estadoAnterior}' a '{$nuevoEstado}'";
        }

        return [
            $fecha,
            $empresa,
            $proveedor,
            $folio,
            $tipoDoc,
            $cambio,
            $usuario,
        ];
    }
}
