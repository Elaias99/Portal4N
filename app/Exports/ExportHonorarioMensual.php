<?php

namespace App\Exports;

use App\Models\HonorarioMensualRec;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ExportHonorarioMensual implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithEvents,
    WithColumnFormatting
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = HonorarioMensualRec::with([
            'empresa',
            'cobranzaCompra',
        ]);

        // =========================
        // FILTRO: EMPRESA
        // =========================
        if ($this->request->filled('empresa_id')) {
            $query->where('empresa_id', $this->request->empresa_id);
        }

        // =========================
        // FILTRO: AÑO
        // =========================
        if ($this->request->filled('anio')) {
            $query->where('anio', $this->request->anio);
        }

        // =========================
        // FILTRO: MES
        // =========================
        if ($this->request->filled('mes')) {
            $query->where('mes', $this->request->mes);
        }

        // =========================
        // FILTRO: RUT EMISOR
        // =========================
        if ($this->request->filled('rut_emisor')) {
            $query->where('rut_emisor', 'like', '%' . $this->request->rut_emisor . '%');
        }

        // =========================
        // FILTRO: RAZÓN SOCIAL EMISOR
        // =========================
        if ($this->request->filled('razon_social_emisor')) {
            $query->where(
                'razon_social_emisor',
                'like',
                '%' . $this->request->razon_social_emisor . '%'
            );
        }

        // =========================
        // FILTRO: FOLIO
        // =========================
        if ($this->request->filled('folio')) {
            $query->where('folio', $this->request->folio);
        }

        // =========================
        // FILTRO: FECHA EMISIÓN
        // =========================
        if ($this->request->filled('fecha_emision_desde')) {
            $query->whereDate(
                'fecha_emision',
                '>=',
                $this->request->fecha_emision_desde
            );
        }

        if ($this->request->filled('fecha_emision_hasta')) {
            $query->whereDate(
                'fecha_emision',
                '<=',
                $this->request->fecha_emision_hasta
            );
        }

        return $query
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->orderBy('fecha_emision', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Empresa',
            'Año',
            'Mes',
            'Folio',
            'Fecha Emisión',
            'Fecha Vencimiento',
            'Estado Tributario',
            'Estado Financiero Inicial',
            'Estado Financiero Actual',
            'RUT Emisor',
            'Razón Social Emisor',
            'Sociedad Profesional',
            'Servicio (Final)',
            'Monto Bruto',
            'Monto Retenido',
            'Monto Pagado (SII)',
            'Saldo Pendiente',
            'Fecha Estado Financiero',
            'Fecha Anulación',
            'Creado',
            'Actualizado',
        ];
    }

    public function map($h): array
    {
        return [
            $h->empresa?->Nombre,
            $h->anio,
            $h->mes,
            $h->folio,
            $this->excelDate($h->fecha_emision),
            $this->excelDate($h->fecha_vencimiento),
            $h->estado,
            $h->estado_financiero_inicial,
            $h->estado_financiero_final,
            $h->rut_emisor,
            $h->razon_social_emisor,
            $h->sociedad_profesional,
            $h->servicio_final,
            $h->monto_bruto,
            $h->monto_retenido,
            $h->monto_pagado,
            $h->saldo_pendiente,
            $this->excelDate($h->fecha_estado_financiero),
            $this->excelDate($h->fecha_anulacion),
            $this->excelDate($h->created_at),
            $this->excelDate($h->updated_at),
        ];
    }

    public function columnFormats(): array
    {
        return [
            'E' => 'dd-mm-yyyy', // Fecha Emisión
            'F' => 'dd-mm-yyyy', // Fecha Vencimiento
            'R' => 'dd-mm-yyyy', // Fecha Estado Financiero
            'S' => 'dd-mm-yyyy', // Fecha Anulación
            'T' => 'dd-mm-yyyy', // Creado
            'U' => 'dd-mm-yyyy', // Actualizado
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Encabezados en negrita
                $sheet->getStyle('A1:U1')->getFont()->setBold(true);
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