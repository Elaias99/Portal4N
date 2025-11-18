<?php

namespace App\Exports;

use App\Models\MovimientoDocumento;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Carbon;

class MovimientoExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $fechaInicio;
    protected $fechaFin;

    public function __construct($fechaInicio = null, $fechaFin = null)
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin    = $fechaFin;
    }

    public function collection()
    {
        $query = MovimientoDocumento::with(['documento.empresa', 'user', 'documento.tipoDocumento'])
            ->when($this->fechaInicio, fn($q) =>
                $q->whereDate('created_at', '>=', $this->fechaInicio)
            )
            ->when($this->fechaFin, fn($q) =>
                $q->whereDate('created_at', '<=', $this->fechaFin)
            )
            ->orderByDesc('created_at');

        $movimientos = $query->get();

        return $movimientos;
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Tipo Movimiento',
            'Descripción',
            'Folio Documento',
            'Tipo Documento',
            'Cliente / Razón Social',
            'Empresa',
            'Monto Total ($)',
            'Saldo Pendiente ($)',
            'Usuario',
        ];
    }

    public function map($mov): array
    {
        $fecha = optional($mov->created_at)->format('d-m-Y H:i');
        $tipo = $mov->tipo_movimiento ?? '—';
        $descripcion = $mov->descripcion ?? '—';
        $folio = $mov->documento->folio ?? '—';
        $tipoDoc = $mov->documento->tipoDocumento->nombre ?? '—';
        $cliente = $mov->documento->razon_social ?? '—';
        $empresa = $mov->documento->empresa->Nombre ?? '—';
        $montoTotal = '$' . number_format($mov->documento->monto_total ?? 0, 0, ',', '.');
        $saldoPendiente = '$' . number_format($mov->documento->saldo_pendiente ?? 0, 0, ',', '.');
        $usuario = $mov->user->name ?? '—';

        return [
            $fecha,
            $tipo,
            $descripcion,
            $folio,
            $tipoDoc,
            $cliente,
            $empresa,
            $montoTotal,
            $saldoPendiente,
            $usuario,
        ];
    }
}
