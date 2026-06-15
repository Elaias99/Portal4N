<?php

namespace App\Http\Controllers;

use App\Models\Asignaciones;
use App\Models\SuscripcionCantidadMensual;
use Illuminate\Http\Request;

class SuscripcionCantidadMensualController extends Controller
{
    public function create(Request $request)
    {
        $anio = (int) $request->input('anio', now()->year);
        $mes = (int) $request->input('mes', now()->month);

        $asignaciones = Asignaciones::with([
            'suscripcionProveedor.cobranzaCompra',
            'transportista',
        ])
        ->where('generar_automaticamente', 0)
        ->whereRaw("UPPER(TRIM(codigo)) NOT LIKE '%.COM'")
        ->whereRaw("UPPER(TRIM(codigo)) NOT LIKE '%COMISION%'")
        ->orderBy('codigo')
        ->get();

        return view('suscripciones.cantidades_mensuales.create', compact(
            'anio',
            'mes',
            'asignaciones'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'suscripcion_asignacion_id' => 'required|exists:suscripcion_asignaciones,id',
            'anio' => 'required|integer|min:2020|max:2100',
            'mes' => 'required|integer|min:1|max:12',
            'cantidad' => 'required|integer|min:1',
            'observacion' => 'nullable|string|max:1000',
        ]);

        $asignacion = Asignaciones::with([
            'suscripcionProveedor.cobranzaCompra',
            'transportista',
        ])->findOrFail($data['suscripcion_asignacion_id']);

        $codigo = mb_strtoupper(trim((string) $asignacion->codigo));

        $esComision = str_ends_with($codigo, '.COM')
            || str_contains($codigo, 'COMISION');

        if ($esComision) {
            return back()
                ->withInput()
                ->withErrors([
                    'suscripcion_asignacion_id' => 'Esta asignación corresponde a una comisión, no a una cantidad mensual.',
                ]);
        }

        $existe = SuscripcionCantidadMensual::where('suscripcion_asignacion_id', $asignacion->id)
            ->where('anio', $data['anio'])
            ->where('mes', $data['mes'])
            ->exists();

        if ($existe) {
            return back()
                ->withInput()
                ->withErrors([
                    'suscripcion_asignacion_id' => 'Esta cantidad mensual ya existe para la asignación, año y mes seleccionado.',
                ]);
        }

        $costo = (int) $asignacion->costo;
        $cantidad = (int) $data['cantidad'];
        $total = $costo * $cantidad;

        SuscripcionCantidadMensual::create([
            'suscripcion_asignacion_id' => $asignacion->id,
            'anio' => (int) $data['anio'],
            'mes' => (int) $data['mes'],
            'codigo' => $asignacion->codigo,
            'costo' => $costo,
            'cantidad' => $cantidad,
            'total' => $total,
            'observacion' => $data['observacion'] ?? null,
        ]);

        return redirect()
            ->route('suscripciones.comisiones-mensuales.create', [
                'anio' => $data['anio'],
                'mes' => $data['mes'],
            ])
            ->with('success', 'Cantidad mensual registrada correctamente.');
    }
}
