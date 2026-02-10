<?php

namespace App\Exports;

use App\Models\Cobranza;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CobranzasExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    /**
     *  Retorna la colección de datos a exportar.
     */
    public function collection()
    {
        // Puedes agregar filtros si deseas limitar los datos
        return Cobranza::orderBy('id', 'desc')->get();
    }

    /**
     *  Define las columnas (encabezados) del Excel.
     */
    public function headings(): array
    {
        return [
            'ID',
            'RUT Cliente',
            'Razón Social',
            'Servicio',
            'Créditos',
            'Fecha de Creación',
            'Última Actualización',
        ];
    }

    /**
     *  Define cómo se mapea cada fila de datos.
     */
    public function map($cobranza): array
    {
        return [
            $cobranza->id,
            $cobranza->rut_cliente,
            $cobranza->razon_social,
            $cobranza->servicio,
            $cobranza->creditos,
            $cobranza->created_at ? $cobranza->created_at->format('d-m-Y H:i') : '',
            $cobranza->updated_at ? $cobranza->updated_at->format('d-m-Y H:i') : '',
        ];
    }
}
