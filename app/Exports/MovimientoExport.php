<?php

namespace App\Exports;

use App\Models\MovimientoDocumento;
use App\Services\Ventas\HistorialMovimientosCxCService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

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
     * Colección de movimientos CxC filtrados por fecha real del evento.
     */
    public function collection()
    {
        $historialCxC = app(HistorialMovimientosCxCService::class);

        $query = MovimientoDocumento::with([
            'documento.empresa',
            'documento.tipoDocumento',
            'documento.referenciados',
            'user',
            'origen',
        ]);

        $historialCxC->aplicarFiltroFechaMovimiento(
            query: $query,
            fechaInicio: $this->fechaInicio,
            fechaFin: $this->fechaFin,
        );

        $movimientos = $query
            ->orderByDesc('movimientos_documentos.created_at')
            ->get();

        return $historialCxC->enriquecerColeccion($movimientos);
    }

    /**
     * Encabezados del Excel.
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
     * Mapeo fila por fila.
     */
    public function map($mov): array
    {
        $fechaMovimiento = $mov->created_at
            ? Carbon::parse($mov->created_at)->format('d-m-Y')
            : '—';

        $tipoOriginal = $mov->tipo_movimiento ?? '—';

        $folio = $mov->documento?->folio ?? '—';

        $tipoDoc = $mov->documento?->tipoDocumento?->nombre ?? '—';

        $cliente = $mov->documento?->razon_social ?? '—';

        $empresa = $mov->documento?->empresa?->Nombre ?? '—';

        $usuario = $mov->user?->name ?? '—';

        $descripcion = $mov->descripcion ?? '—';

        $fechaEstado = $mov->fecha_estado_historial ?? null;

        $fechaEstadoFormateada = $fechaEstado
            ? Carbon::parse($fechaEstado)->format('d-m-Y')
            : '—';

        $montoFirmado = (int) ($mov->monto_movimiento_historial ?? 0);

        $signo = $montoFirmado < 0 ? '-' : '+';

        $montoFormateado = $signo . '$' . number_format(abs($montoFirmado), 0, ',', '.');

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