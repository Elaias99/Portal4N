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
            'Fecha Movimiento',
            'Empresa',
            'Proveedor',
            'Folio',
            'Tipo Documento',
            'Fecha ingreso estado',
            'Cambio de Estado',
            'Usuario',
        ];
    }

    public function map($mov): array
    {
        $fechaMovimiento = optional($mov->created_at)->format('d-m-Y H:i');
        $empresa = $mov->compra?->empresa?->Nombre ?? '—';
        $proveedor = $mov->compra?->razon_social ?? '—';
        $folio = $mov->compra?->folio ?? '—';
        $tipoDoc = $mov->compra?->tipoDocumento?->nombre ?? '—';
        $usuario = $mov->user?->name ?? '—';

        // ============================================================
        // OBTENER LA FECHA REAL DEL ESTADO (abono/pago/cruce/pronto pago)
        // ============================================================
        $fechaEstado = null;
        $ts = optional($mov->created_at)->toDateTimeString();

        // Abono
        $abono = $mov->compra->abonos()
            ->where('created_at', $ts)
            ->first();
        if ($abono) {
            $fechaEstado = $abono->fecha_abono;
        }

        // Pago
        $pago = $mov->compra->pagos()
            ->where('created_at', $ts)
            ->first();
        if ($pago) {
            $fechaEstado = $pago->fecha_pago;
        }

        // Cruce
        $cruce = $mov->compra->cruces()
            ->where('created_at', $ts)
            ->first();
        if ($cruce) {
            $fechaEstado = $cruce->fecha_cruce;
        }

        // Pronto pago
        $pp = $mov->compra->prontoPagos()
            ->where('created_at', $ts)
            ->first();
        if ($pp) {
            $fechaEstado = $pp->fecha_pronto_pago;
        }

        $fechaEstadoFormateada = $fechaEstado
            ? \Carbon\Carbon::parse($fechaEstado)->format('d-m-Y')
            : '—';

        // ============================================================
        // TEXTO DEL MOVIMIENTO
        // ============================================================
        $estadoAnterior = $mov->estado_anterior;
        $nuevoEstado = $mov->nuevo_estado;

        if (is_null($estadoAnterior) && is_null($nuevoEstado)) {
            $cambio = "Eliminación de pago";
        } elseif ($estadoAnterior === $nuevoEstado) {
            $cambio = "Movimiento: {$nuevoEstado}";
        } else {
            $cambio = "De '{$estadoAnterior}' a '{$nuevoEstado}'";
        }

        return [
            $fechaMovimiento,
            $empresa,
            $proveedor,
            $folio,
            $tipoDoc,
            $fechaEstadoFormateada,
            $cambio,
            $usuario,
        ];
    }
}
