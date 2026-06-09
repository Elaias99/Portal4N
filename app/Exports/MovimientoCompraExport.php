<?php

namespace App\Exports;

use App\Models\MovimientoCompra;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MovimientoCompraExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithColumnFormatting
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

        $datosNuevos = $mov->datos_nuevos ?? [];
        $datosAnteriores = $mov->datos_anteriores ?? [];

        // ==============================
        // FECHA REAL DEL EVENTO
        // ==============================
        $fechaEvento = null;

        if (Str::contains($tipo, 'abono')) {
            $fechaEvento = $datosNuevos['fecha_abono']
                ?? $datosAnteriores['fecha_abono']
                ?? null;
        } elseif (Str::contains($tipo, 'cruce')) {
            $fechaEvento = $datosNuevos['fecha_cruce']
                ?? $datosAnteriores['fecha_cruce']
                ?? null;
        } elseif (Str::contains(Str::ascii($tipo), 'pronto pago')) {
            $fechaEvento = $datosNuevos['fecha_pronto_pago']
                ?? $datosAnteriores['fecha_pronto_pago']
                ?? null;
        } elseif (Str::contains($tipo, 'pago')) {
            $fechaEvento = $datosNuevos['fecha_pago']
                ?? $datosAnteriores['fecha_pago']
                ?? null;
        }

        // Fallback
        $fechaEventoExcel = $this->excelDate(
            $fechaEvento ?? $mov->fecha_cambio ?? $mov->created_at
        );

        // ==============================
        // MONTO MOVIMIENTO
        // ==============================
        $monto = 0;

        if (Str::contains($tipo, 'abono') || Str::contains($tipo, 'cruce')) {
            $monto = $datosNuevos['monto']
                ?? $datosAnteriores['monto']
                ?? 0;
        } elseif (Str::contains($tipo, 'pago') || Str::contains($tipo, 'pronto pago')) {
            $monto = $mov->compra->monto_total ?? 0;
        }

        // Ajuste para eliminaciones
        if (Str::contains($tipo, 'eliminación')) {
            $monto *= -1;
        }

        return [
            $fechaEventoExcel,
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

    public function columnFormats(): array
    {
        return [
            'A' => 'dd-mm-yyyy', // Fecha evento
        ];
    }

    private function excelDate($date)
    {
        if (!$date) {
            return null;
        }

        try {
            return ExcelDate::dateTimeToExcel(Carbon::parse($date));
        } catch (\Throwable $e) {
            return null;
        }
    }
}