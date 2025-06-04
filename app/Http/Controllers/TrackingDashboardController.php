<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TrackingProducto;
use App\Models\Trabajador;
use App\Models\Bultos;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TrackingDashboardController extends Controller
{

    public function index()
    {

        $choferes = Trabajador::where('area_id', 5)->get();

        $razonesSociales = Bultos::distinct()
            ->orderBy('razon_social')
            ->pluck('razon_social')
            ->filter()
            ->values();



        // Traer todos los registros ordenados por código y fecha
        $registros = TrackingProducto::select('codigo', 'estado', 'updated_at')
            ->orderBy('codigo')
            ->orderBy('updated_at')
            ->get();

        // Agrupar por código
        $agrupados = $registros->groupBy('codigo');

        // Preparar estructura: códigos con sus fechas por estado
        $historial = [];



        foreach ($agrupados as $codigo => $eventos) {
            // Obtener último chofer asignado (puede estar en Recepcionado o En Ruta)
            $ultimoChoferId = $eventos->reverse()->first(fn($e) => !is_null($e->chofer_id))?->chofer_id;

            // Obtener razon_social desde Bultos
            $bulto = Bultos::where('codigo_bulto', $codigo)->first();
            $razonSocial = $bulto?->razon_social;

            $historial[] = [
                'codigo' => $codigo,
                'retiro' => optional($eventos->firstWhere('estado', 'Retiro'))->updated_at,
                'recepcionado' => optional($eventos->firstWhere('estado', 'Recepcionado'))->updated_at,
                'en_ruta' => optional($eventos->firstWhere('estado', 'En Ruta'))->updated_at,
                'estado_final' => $eventos->last()->estado,
                'chofer_id' => $ultimoChoferId,
                'razon_social' => $razonSocial,
            ];
        }


        // Contadores
        $countRetiro = collect($historial)->where('estado_final', 'Retiro')->count();
        $countRecepcionado = collect($historial)->where('estado_final', 'Recepcionado')->count();
        $countEnRuta = collect($historial)->where('estado_final', 'En Ruta')->count();

        return view('tracking_productos.dashboard', compact(
            'countRetiro',
            'countRecepcionado',
            'countEnRuta',
            'historial',
            'choferes', 
            'razonesSociales',
        ));
    }

}
