<?php

namespace App\Http\Controllers;
use App\Models\Abono;
use App\Models\Cruce;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PanelFinanzaController extends Controller
{
    //
        /**
     * Mostrar historial combinado de Abonos y Cruces.
     */
    public function show(Request $request)
    {
        // 🚫 Acceso
        $usuariosFinanzas = [1, 405, 374];
        if (!in_array(Auth::id(), $usuariosFinanzas)) {
            abort(403, 'Acceso denegado. No tienes permiso para ingresar a este módulo.');
        }

        // Rango de fechas (opcional)
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin    = $request->input('fecha_fin');

        // Queries base
        $abonosQuery = \App\Models\Abono::with('documento');
        $crucesQuery = \App\Models\Cruce::with('documento');

        // Filtro por rango de fechas usando los campos reales
        if ($fechaInicio && $fechaFin) {
            $abonosQuery->whereBetween('fecha_abono', [$fechaInicio, $fechaFin]);
            $crucesQuery->whereBetween('fecha_cruce', [$fechaFin ? $fechaInicio : $fechaInicio, $fechaFin]);
        } elseif ($fechaInicio) {
            $abonosQuery->whereDate('fecha_abono', '>=', $fechaInicio);
            $crucesQuery->whereDate('fecha_cruce', '>=', $fechaInicio);
        } elseif ($fechaFin) {
            $abonosQuery->whereDate('fecha_abono', '<=', $fechaFin);
            $crucesQuery->whereDate('fecha_cruce', '<=', $fechaFin);
        }

        // Traer datos filtrados
        $abonos = $abonosQuery->orderByDesc('fecha_abono')->get();
        $cruces = $crucesQuery->orderByDesc('fecha_cruce')->get();

        // Unificar en memoria: creo clave "fecha" SOLO para ordenar/mostrar
        $movimientos = $abonos->map(function ($a) {
            return [
                'tipo'       => 'Abono',
                'fecha'      => $a->fecha_abono, // <-- campo real
                'monto'      => $a->monto,
                'documento'  => $a->documento,
                'raw'        => $a,              // por si necesitas el modelo original
            ];
        })->merge(
            $cruces->map(function ($c) {
                return [
                    'tipo'       => 'Cruce',
                    'fecha'      => $c->fecha_cruce, // <-- campo real
                    'monto'      => $c->monto,
                    'documento'  => $c->documento,
                    'raw'        => $c,
                ];
            })
        )->sortByDesc('fecha')->values();

        return view('panelfinanza.show', compact('movimientos'));
    }

}
