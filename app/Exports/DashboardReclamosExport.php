<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DashboardReclamosExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return DB::table('reclamos')
            ->select(
                'area_origen.nombre as area_que_genero',
                'area_responsable.nombre as area_que_cerro',
                'reclamos.tipo_solicitud',
                'casuistica_inicials.nombre as casuistica_inicial',
                'casuisticas.nombre as casuistica_final',
                DB::raw('COUNT(*) as cantidad')
            )
            ->leftJoin('trabajadors', 'reclamos.id_trabajador', '=', 'trabajadors.id')
            ->leftJoin('areas as area_origen', 'trabajadors.area_id', '=', 'area_origen.id')
            ->leftJoin('areas as area_responsable', 'reclamos.area_id', '=', 'area_responsable.id')
            ->leftJoin('casuisticas as casuistica_inicials', 'reclamos.casuistica_inicial_id', '=', 'casuistica_inicials.id')
            ->leftJoin('casuisticas', 'reclamos.casuistica_id', '=', 'casuisticas.id')
            ->where('reclamos.estado', 'cerrado')
            ->groupBy(
                'area_origen.nombre',
                'area_responsable.nombre',
                'reclamos.tipo_solicitud',
                'casuistica_inicials.nombre',
                'casuisticas.nombre'
            )
            ->orderByDesc('cantidad')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Área que generó',
            'Área responsable',
            'Tipo de Solicitud',
            'Casuística Inicial',
            'Casuística Final',
            'Cantidad',
        ];
    }
}
