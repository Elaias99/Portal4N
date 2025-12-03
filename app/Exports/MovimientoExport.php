<?php

namespace App\Exports;

use App\Models\MovimientoDocumento;
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
     * Colección de movimientos filtrados
     */
    public function collection()
    {
        return MovimientoDocumento::with(['documento.empresa', 'documento.tipoDocumento', 'user'])
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
     * Encabezados del Excel (alineados a la vista)
     */
    public function headings(): array
    {
        return [
            'Fecha movimiento',
            'Tipo Movimiento',
            'Folio Documento',
            'Tipo Documento',
            'Cliente / Razón Social',
            'Empresa',
            'Fecha ingreso estado',
            'Monto Movimiento ($)',
            'Usuario',
            'Descripción',
        ];
    }

    /**
     * Mapeo fila por fila
     */
    public function map($mov): array
    {
        $fechaMovimiento = optional($mov->created_at)->format('d-m-Y H:i');
        $tipoOriginal    = $mov->tipo_movimiento ?? '—';
        $tipo            = strtolower($tipoOriginal);

        $folio      = $mov->documento->folio ?? '—';
        $tipoDoc    = $mov->documento->tipoDocumento->nombre ?? '—';
        $cliente    = $mov->documento->razon_social ?? '—';
        $empresa    = $mov->documento->empresa->Nombre ?? '—';
        $usuario    = $mov->user->name ?? '—';
        $descripcion = $mov->descripcion ?? '—';

        // ===== MONTO DEL MOVIMIENTO =====
        $monto = 0;

        if (Str::contains($tipo, 'abono') || Str::contains($tipo, 'cruce')) {
            $monto = $mov->datos_nuevos['monto']
                ?? $mov->datos_anteriores['monto']
                ?? 0;
        }
        elseif (Str::contains(Str::ascii($tipo), 'pronto pago') || Str::contains($tipo, 'pago')) {
            $monto = $mov->documento->monto_total ?? 0;
        }

        // Eliminación → signo negativo
        if (Str::contains($tipo, 'eliminación')) {
            $monto *= -1;
        }

        // ===== FECHA DEL ESTADO =====
        $fechaEstado = null;

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
        elseif (Str::contains(Str::ascii($tipo), 'pronto pago')) {
            $fechaEstado = $mov->datos_nuevos['fecha_pronto_pago']
                ?? $mov->datos_anteriores['fecha_pronto_pago']
                ?? null;
        }
        elseif (Str::contains($tipo, 'pago')) {
            $fechaEstado = $mov->datos_nuevos['fecha_pago']
                ?? $mov->datos_anteriores['fecha_pago']
                ?? null;
        }

        $fechaEstadoFormateada = $fechaEstado
            ? \Carbon\Carbon::parse($fechaEstado)->format('d-m-Y')
            : '—';

        $montoFormateado = '$' . number_format(abs($monto), 0, ',', '.');

        return [
            $fechaMovimiento,
            ucfirst($tipoOriginal),
            $folio,
            $tipoDoc,
            $cliente,
            $empresa,
            $fechaEstadoFormateada,
            $montoFormateado,
            $usuario,
            $descripcion,
        ];
    }
}
