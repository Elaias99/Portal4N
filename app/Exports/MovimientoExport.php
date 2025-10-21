<?php

namespace App\Exports;

use App\Models\Abono;
use App\Models\Cruce;
use App\Models\DocumentoFinanciero;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class MovimientoExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        // === ABONOS ===
        $abonosQuery = Abono::with('documento');
        // === CRUCES ===
        $crucesQuery = Cruce::with('documento');
        // === PAGOS (estado manual = Pago) ===
        $pagosQuery = DocumentoFinanciero::where('status', 'Pago');

        // === FILTROS POR FECHA ===
        if ($this->fechaInicio && $this->fechaFin) {
            $abonosQuery->whereBetween('fecha_abono', [$this->fechaInicio, $this->fechaFin]);
            $crucesQuery->whereBetween('fecha_cruce', [$this->fechaInicio, $this->fechaFin]);
            $pagosQuery->whereBetween('fecha_estado_manual', [$this->fechaInicio, $this->fechaFin]);
        } elseif ($this->fechaInicio) {
            $abonosQuery->whereDate('fecha_abono', '>=', $this->fechaInicio);
            $crucesQuery->whereDate('fecha_cruce', '>=', $this->fechaInicio);
            $pagosQuery->whereDate('fecha_estado_manual', '>=', $this->fechaInicio);
        } elseif ($this->fechaFin) {
            $abonosQuery->whereDate('fecha_abono', '<=', $this->fechaFin);
            $crucesQuery->whereDate('fecha_cruce', '<=', $this->fechaFin);
            $pagosQuery->whereDate('fecha_estado_manual', '<=', $this->fechaFin);
        }

        $abonos = $abonosQuery->get();
        $cruces = $crucesQuery->get();
        $pagos  = $pagosQuery->get();

        // === UNIFICAR DATOS ===
        $movimientos = collect();

        foreach ($abonos as $a) {
            $movimientos->push([
                'tipo' => 'Abono',
                'fecha' => $a->fecha_abono,
                'monto' => $a->monto,
                'folio' => $a->documento->folio ?? '-',
                'cliente' => $a->documento->razon_social ?? '-',
                'estado' => $a->documento->status_original ?? '-',
            ]);
        }

        foreach ($cruces as $c) {
            $movimientos->push([
                'tipo' => 'Cruce',
                'fecha' => $c->fecha_cruce,
                'monto' => $c->monto,
                'folio' => $c->documento->folio ?? '-',
                'cliente' => $c->documento->razon_social ?? '-',
                'estado' => $c->documento->status_original ?? '-',
            ]);
        }

        foreach ($pagos as $p) {
            $movimientos->push([
                'tipo' => 'Pago',
                'fecha' => $p->fecha_estado_manual,
                'monto' => $p->monto_total ?? 0,
                'folio' => $p->folio ?? '-',
                'cliente' => $p->razon_social ?? '-',
                'estado' => $p->status_original ?? '-',
            ]);
        }

        return $movimientos->sortByDesc('fecha');
    }

    public function headings(): array
    {
        return [
            'Tipo de Movimiento',
            'Fecha',
            'Monto ($)',
            'Folio Documento',
            'Cliente / Razón Social',
            'Estado del Documento',
        ];
    }

    public function map($movimiento): array
    {
        // Normalizar el monto para asegurar que sea numérico
        $montoLimpio = floatval(
            str_replace(['.', ','], ['', '.'], $movimiento['monto'])
        );

        // Formatear como pesos chilenos (CLP)
        $montoFormateado = '$' . number_format($montoLimpio, 0, ',', '.');

        return [
            $movimiento['tipo'],
            \Carbon\Carbon::parse($movimiento['fecha'])->format('d-m-Y'),
            $montoFormateado,
            $movimiento['folio'],
            $movimiento['cliente'],
            $movimiento['estado'],
        ];
    }

}
