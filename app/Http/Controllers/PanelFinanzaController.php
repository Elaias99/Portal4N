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
    public function show()
    {
        // 🚫 Restricción de acceso
        $usuariosFinanzas = [1, 405, 374];
        if (!in_array(Auth::id(), $usuariosFinanzas)) {
            abort(403, 'Acceso denegado. No tienes permiso para ingresar a este módulo.');
        }

        // 🔹 Obtener datos de ambos modelos
        $abonos = Abono::with('documento')->orderByDesc('fecha_abono')->get();
        $cruces = Cruce::with('documento')->orderByDesc('fecha_cruce')->get();

        // 🔸 Combinar y ordenar por fecha más reciente
        $movimientos = $abonos->map(function($a) {
            return [
                'tipo' => 'Abono',
                'fecha' => $a->fecha_abono,
                'monto' => $a->monto,
                'documento' => $a->documento,
            ];
        })->merge(
            $cruces->map(function($c) {
                return [
                    'tipo' => 'Cruce',
                    'fecha' => $c->fecha_cruce,
                    'monto' => $c->monto,
                    'documento' => $c->documento,
                ];
            })
        )->sortByDesc('fecha');

        return view('panelfinanza.show', compact('movimientos'));
    }
}
