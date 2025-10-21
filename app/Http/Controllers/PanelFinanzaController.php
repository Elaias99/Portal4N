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
        // 🚫 Control de acceso
        $usuariosFinanzas = [1, 405, 374];
        if (!in_array(Auth::id(), $usuariosFinanzas)) {
            abort(403, 'Acceso denegado. No tienes permiso para ingresar a este módulo.');
        }

        // Rango de fechas (opcional)
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin    = $request->input('fecha_fin');

        // === Queries base ===
        $abonosQuery = \App\Models\Abono::with('documento');
        $crucesQuery = \App\Models\Cruce::with('documento');
        $pagosQuery  = \App\Models\DocumentoFinanciero::where('status', 'Pago');

        // === Filtros por fecha ===
        if ($fechaInicio && $fechaFin) {
            $abonosQuery->whereBetween('fecha_abono', [$fechaInicio, $fechaFin]);
            $crucesQuery->whereBetween('fecha_cruce', [$fechaInicio, $fechaFin]);
            $pagosQuery->whereBetween('fecha_estado_manual', [$fechaInicio, $fechaFin]);
        } elseif ($fechaInicio) {
            $abonosQuery->whereDate('fecha_abono', '>=', $fechaInicio);
            $crucesQuery->whereDate('fecha_cruce', '>=', $fechaInicio);
            $pagosQuery->whereDate('fecha_estado_manual', '>=', $fechaInicio);
        } elseif ($fechaFin) {
            $abonosQuery->whereDate('fecha_abono', '<=', $fechaFin);
            $crucesQuery->whereDate('fecha_cruce', '<=', $fechaFin);
            $pagosQuery->whereDate('fecha_estado_manual', '<=', $fechaFin);
        }

        // === Obtener datos ===
        $abonos = $abonosQuery->orderByDesc('fecha_abono')->get();
        $cruces = $crucesQuery->orderByDesc('fecha_cruce')->get();
        $pagos  = $pagosQuery->orderByDesc('fecha_estado_manual')->get();

        // === Unificar ===
        $movimientos = collect()
            ->merge($abonos->map(function ($a) {
                return [
                    'tipo' => 'Abono',
                    'fecha' => $a->fecha_abono,
                    'monto' => $a->monto,
                    'documento' => $a->documento,
                    'raw' => $a,
                ];
            }))
            ->merge($cruces->map(function ($c) {
                return [
                    'tipo' => 'Cruce',
                    'fecha' => $c->fecha_cruce,
                    'monto' => $c->monto,
                    'documento' => $c->documento,
                    'raw' => $c,
                ];
            }))
            ->merge($pagos->map(function ($p) {
                return [
                    'tipo' => 'Pago',
                    'fecha' => $p->fecha_estado_manual,
                    'monto' => $p->monto_total ?? 0,
                    'documento' => $p,
                    'raw' => $p,
                ];
            }))
            ->sortByDesc('fecha')
            ->values();

        return view('panelfinanza.show', compact('movimientos'));
    }


}
