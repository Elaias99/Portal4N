<?php

namespace App\Exports;

use App\Models\MovimientoHonorarioMensualRec;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class ExportMovimientoHonorarioMensual implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithEvents
{
    public function collection()
    {
        return MovimientoHonorarioMensualRec::with([
                'user',
                'honorario.empresa',
            ])
            ->orderBy('fecha_cambio', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Empresa',
            'Honorario ID',
            'Usuario ID',
            'Usuario',
            'Estado Anterior',
            'Nuevo Estado',
            'Tipo Movimiento',
            'Descripción',
            'Saldo Anterior',
            'Saldo Nuevo',
            'Fecha Cambio',
            'Creado',
            'Actualizado',
        ];
    }

    public function map($movimiento): array
    {
        $datosAnteriores = $movimiento->datos_anteriores ?? [];
        $datosNuevos     = $movimiento->datos_nuevos ?? [];

        return [
            $movimiento->id,
            $movimiento->honorario?->empresa?->Nombre,
            $movimiento->honorario_mensual_rec_id,
            $movimiento->usuario_id,
            $movimiento->user?->name,
            $movimiento->estado_anterior,
            $movimiento->nuevo_estado,
            $movimiento->tipo_movimiento,
            $movimiento->descripcion,
            $datosAnteriores['saldo'] ?? null,
            $datosNuevos['saldo'] ?? null,
            $this->format($movimiento->fecha_cambio),
            $this->format($movimiento->created_at),
            $this->format($movimiento->updated_at),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                // Encabezados en negrita
                $sheet->getStyle('A1:N1')->getFont()->setBold(true);
            },
        ];
    }

    private function format($date)
    {
        return $date ? Carbon::parse($date)->format('d-m-Y') : null;
    }
}