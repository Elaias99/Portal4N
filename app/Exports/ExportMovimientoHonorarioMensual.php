<?php

namespace App\Exports;

use App\Models\MovimientoHonorarioMensualRec;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ExportMovimientoHonorarioMensual implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithEvents,
    WithColumnFormatting
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
            $this->excelDate($movimiento->fecha_cambio),
            $this->excelDate($movimiento->created_at),
            $this->excelDate($movimiento->updated_at),
        ];
    }

    public function columnFormats(): array
    {
        return [
            'L' => 'dd-mm-yyyy', // Fecha Cambio
            'M' => 'dd-mm-yyyy', // Creado
            'N' => 'dd-mm-yyyy', // Actualizado
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