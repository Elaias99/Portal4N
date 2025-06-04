<?php

namespace App\Http\Controllers;

use App\Exports\DashboardReclamosExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class ReclamoDashboardController extends Controller
{
    //
    public function index()
    {
        $reclamosDetallados = DB::table('reclamos')
            ->select(
                'reclamos.id as reclamo_id',
                'bultos.codigo_bulto',
                'tp.fecha_retiro',
                'tp.fecha_entrega',
                'trabajadors.Nombre as nombre_chofer',
                'trabajadors.ApellidoPaterno as apellido_chofer',
                'area_origen.nombre as area_que_genero',
                'area_responsable.nombre as area_que_cerro',
                'reclamos.tipo_solicitud',
                'casuistica_inicials.nombre as casuistica_inicial',
                'casuisticas.nombre as casuistica_final'
            )
            ->leftJoin('bultos', 'reclamos.id_bulto', '=', 'bultos.id')

            // Subconsulta de tracking resumido
            ->leftJoin(DB::raw("(
                SELECT 
                    codigo,
                    MIN(CASE WHEN estado = 'Retiro' THEN created_at END) as fecha_retiro,
                    MAX(CASE WHEN estado = 'En Ruta' THEN created_at END) as fecha_entrega,
                    MAX(CASE WHEN estado = 'En Ruta' THEN chofer_id END) as chofer_id
                FROM tracking_productos
                GROUP BY codigo
            ) as tp"), 'bultos.codigo_bulto', '=', 'tp.codigo')

            ->leftJoin('trabajadors', 'tp.chofer_id', '=', 'trabajadors.id')
            ->leftJoin('trabajadors as trabajador_origen', 'reclamos.id_trabajador', '=', 'trabajador_origen.id')
            ->leftJoin('areas as area_origen', 'trabajador_origen.area_id', '=', 'area_origen.id')
            ->leftJoin('areas as area_responsable', 'reclamos.area_id', '=', 'area_responsable.id')
            ->leftJoin('casuisticas as casuistica_inicials', 'reclamos.casuistica_inicial_id', '=', 'casuistica_inicials.id')
            ->leftJoin('casuisticas', 'reclamos.casuistica_id', '=', 'casuisticas.id')
            ->where('reclamos.estado', 'cerrado')
            ->orderBy('tp.fecha_entrega', 'desc')
            ->get();

        return view('reclamos.dashboard', compact('reclamosDetallados'));
    }



    public function exportarExcel()
    {
        return Excel::download(new DashboardReclamosExport, 'dashboard_reclamos.xlsx');
    }




}
