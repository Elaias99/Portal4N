<?php

namespace App\Exports;

use App\Models\MovimientoDocumento;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Str;

class MovimientoExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $fechaInicio;
    protected $fechaFin;

    public function __construct($fechaInicio = null, $fechaFin = null)
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin    = $fechaFin;
    }

    /**
     * Colección de movimientos con filtros aplicados
     */
    public function collection()
    {
        return MovimientoDocumento::with(['documento.empresa', 'user', 'documento.tipoDocumento'])
            ->when($this->fechaInicio, fn($q) =>
                $q->whereDate('created_at', '>=', $this->fechaInicio)
            )
            ->when($this->fechaFin, fn($q) =>
                $q->whereDate('created_at', '<=', $this->fechaFin)
            )
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Encabezados del archivo Excel
     */
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
            'Fecha ingreso estado',
            'Monto Movimiento ($)',
            'Usuario',
        ];
    }

    /**
     * Mapeo de cada registro hacia las columnas del Excel
     */
    public function map($mov): array
    {
        $fecha = optional($mov->created_at)->format('d-m-Y H:i');
        $tipoOriginal = $mov->tipo_movimiento ?? '—';
        $tipo = strtolower($tipoOriginal);
        $fechaEstado = null;
        $descripcion = $mov->descripcion ?? '—';
        $folio = $mov->documento->folio ?? '—';
        $tipoDoc = $mov->documento->tipoDocumento->nombre ?? '—';
        $cliente = $mov->documento->razon_social ?? '—';
        $empresa = $mov->documento->empresa->Nombre ?? '—';
        $usuario = $mov->user->name ?? '—';

        // === Cálculo coherente del monto ===
        $montoMovimiento = 0;

        if (Str::contains($tipo, 'abono') || Str::contains($tipo, 'cruce')) {
            $montoMovimiento = $mov->datos_nuevos['monto']
                ?? $mov->datos_anteriores['monto']
                ?? 0;
        } elseif (Str::contains($tipo, 'pago') || Str::contains($tipo, 'pronto pago')) {
            $montoMovimiento = $mov->documento->monto_total ?? 0;
        }

        // Eliminaciones → monto negativo
        if (Str::contains($tipo, 'eliminación')) {
            $montoMovimiento *= -1;
        }

        if (Str::contains($tipo, 'abono')) {
            $fechaEstado = $mov->datos_nuevos['fecha_abono']
                ?? $mov->datos_anteriores['fecha_abono']
                ?? null;
        }
        elseif (Str::contains($tipo, 'cruce')) {
            $fechaEstado = $mov->datos_nuevos['fecha_cruce']
                ?? $mov->datos_anteriores['fecha_cruce']
                ?? null;
        }
        elseif (Str::contains($tipo, 'pago')) {
            $fechaEstado = $mov->datos_nuevos['fecha_pago']
                ?? $mov->datos_anteriores['fecha_pago']
                ?? null;
        }
        elseif (Str::contains($tipo, 'pronto pago')) {
            $fechaEstado = $mov->datos_nuevos['fecha_pronto_pago']
                ?? $mov->datos_anteriores['fecha_pronto_pago']
                ?? null;
        }

        // Formato del monto con signo
        $montoFormateado = '$' . number_format(abs($montoMovimiento), 0, ',', '.');

        $fechaEstadoFormateada = $fechaEstado
            ? \Carbon\Carbon::parse($fechaEstado)->format('d-m-Y')
            : '—';


        return [
            $fecha,
            ucfirst($tipoOriginal),
            $descripcion,
            $folio,
            $tipoDoc,
            $cliente,
            $empresa,
            $fechaEstadoFormateada,
            $montoFormateado,
            $usuario,
        ];
    }
}
