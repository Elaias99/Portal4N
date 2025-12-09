<?php

namespace App\Exports;

use App\Models\MovimientoCompra;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Str;
use Carbon\Carbon;

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
            // Filtrado por fecha de evento real (abono, pago, cruce, pronto pago)
            ->when($this->fechaInicio || $this->fechaFin, function ($q) {
                $q->where(function ($sub) {
                    // ABONOS
                    $sub->orWhereHas('compra.abonos', function ($a) {
                        if ($this->fechaInicio) {
                            $a->whereDate('fecha_abono', '>=', $this->fechaInicio);
                        }
                        if ($this->fechaFin) {
                            $a->whereDate('fecha_abono', '<=', $this->fechaFin);
                        }
                    });

                    // PAGOS
                    $sub->orWhereHas('compra.pagos', function ($p) {
                        if ($this->fechaInicio) {
                            $p->whereDate('fecha_pago', '>=', $this->fechaInicio);
                        }
                        if ($this->fechaFin) {
                            $p->whereDate('fecha_pago', '<=', $this->fechaFin);
                        }
                    });

                    // CRUCES
                    $sub->orWhereHas('compra.cruces', function ($c) {
                        if ($this->fechaInicio) {
                            $c->whereDate('fecha_cruce', '>=', $this->fechaInicio);
                        }
                        if ($this->fechaFin) {
                            $c->whereDate('fecha_cruce', '<=', $this->fechaFin);
                        }
                    });

                    // PRONTO PAGO
                    $sub->orWhereHas('compra.prontoPagos', function ($pp) {
                        if ($this->fechaInicio) {
                            $pp->whereDate('fecha_pronto_pago', '>=', $this->fechaInicio);
                        }
                        if ($this->fechaFin) {
                            $pp->whereDate('fecha_pronto_pago', '<=', $this->fechaFin);
                        }
                    });
                });
            })
            ->orderByDesc('movimientos_compras.created_at')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Fecha evento',
            'Tipo Movimiento',
            'Empresa',
            'Proveedor',
            'Folio',
            'Tipo Documento',
            'Monto Movimiento',
            'Usuario',
            'Descripción',
        ];
    }

    public function map($mov): array
    {
        $tipo = strtolower($mov->tipo_movimiento ?? '');
        $empresa = $mov->compra?->empresa?->Nombre ?? '—';
        $proveedor = $mov->compra?->razon_social ?? '—';
        $folio = $mov->compra?->folio ?? '—';
        $tipoDoc = $mov->compra?->tipoDocumento?->nombre ?? '—';
        $usuario = $mov->user?->name ?? '—';
        $descripcion = $mov->descripcion ?? '—';

        // ==============================
        // FECHA REAL DEL EVENTO
        // ==============================
        $fechaEvento = null;

        if (Str::contains($tipo, 'abono')) {
            $fechaEvento = $mov->datos_nuevos['fecha_abono']
                ?? $mov->datos_anteriores['fecha_abono']
                ?? null;
        } elseif (Str::contains($tipo, 'cruce')) {
            $fechaEvento = $mov->datos_nuevos['fecha_cruce']
                ?? $mov->datos_anteriores['fecha_cruce']
                ?? null;
        } elseif (Str::contains(Str::ascii($tipo), 'pronto pago')) {
            $fechaEvento = $mov->datos_nuevos['fecha_pronto_pago']
                ?? $mov->datos_anteriores['fecha_pronto_pago']
                ?? null;
        } elseif (Str::contains($tipo, 'pago')) {
            $fechaEvento = $mov->datos_nuevos['fecha_pago']
                ?? $mov->datos_anteriores['fecha_pago']
                ?? null;
        }

        // Fallback
        $fechaEventoFormateada = $fechaEvento
            ? Carbon::parse($fechaEvento)->format('d-m-Y')
            : Carbon::parse($mov->fecha_cambio ?? $mov->created_at)->format('d-m-Y');

        // ==============================
        // MONTO MOVIMIENTO
        // ==============================
        $monto = 0;
        if (Str::contains($tipo, 'abono') || Str::contains($tipo, 'cruce')) {
            $monto = $mov->datos_nuevos['monto']
                ?? $mov->datos_anteriores['monto']
                ?? 0;
        } elseif (Str::contains($tipo, 'pago') || Str::contains($tipo, 'pronto pago')) {
            $monto = $mov->compra->monto_total ?? 0;
        }

        // Ajuste para eliminaciones
        if (Str::contains($tipo, 'eliminación')) {
            $monto *= -1;
        }

        return [
            $fechaEventoFormateada,
            ucfirst($mov->tipo_movimiento),
            $empresa,
            $proveedor,
            $folio,
            $tipoDoc,
            '$' . number_format($monto, 0, ',', '.'),
            $usuario,
            $descripcion,
        ];
    }
}
