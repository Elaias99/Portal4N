<?php

namespace App\Exports;

use App\Models\HonorarioMensualRec;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class HonorariosPagoMasivoExport implements FromCollection, WithHeadings, WithMapping
{
    protected Collection $honorarios;

    /**
     * Recibe los honorarios ya procesados
     */
    public function __construct(Collection $honorarios)
    {
        $this->honorarios = $honorarios;
    }

    /**
     * Colección base del Excel
     */
    public function collection(): Collection
    {
        return $this->honorarios;
    }

    /**
     * Encabezados del Excel
     */
    public function headings(): array
    {
        return [
            'ID',
            'Empresa',
            'RUT Contribuyente',
            'Año',
            'Mes',
            'Folio',
            'Fecha emisión',
            'Fecha vencimiento',
            'RUT Emisor',
            'Emisor',
            'Monto bruto',
            'Monto retenido',
            'Monto pagado',
            'Estado financiero',
        ];
    }

    /**
     * Mapeo de cada fila
     */
    public function map($honorario): array
    {
        return [
            $honorario->id,
            $honorario->empresa?->Nombre,
            $honorario->rut_contribuyente,
            $honorario->anio,
            $honorario->mes,
            $honorario->folio,
            optional($honorario->fecha_emision)->format('Y-m-d'),
            optional($honorario->fecha_vencimiento)->format('Y-m-d'),
            $honorario->rut_emisor,
            $honorario->razon_social_emisor,
            $honorario->monto_bruto,
            $honorario->monto_retenido,
            $honorario->monto_pagado,
            $honorario->estado_financiero_final,
        ];
    }
}
