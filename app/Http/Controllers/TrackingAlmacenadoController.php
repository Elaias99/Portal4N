<?php

namespace App\Http\Controllers;

use App\Models\TrackingAlmacenado;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrackingAlmacenadoController extends Controller
{
    public function create()
    {
        $destinos = $this->obtenerDestinosPermitidos();

        return view('tracking-almacenado.create', compact('destinos'));
    }

    public function store(Request $request)
    {
        $destinos = $this->obtenerDestinosPermitidos();

        $request->merge([
            'codigo_tracking' => collect($request->codigo_tracking)
                ->map(fn ($codigo) => trim((string) $codigo))
                ->filter(fn ($codigo) => $codigo !== '')
                ->values()
                ->all(),
        ]);

        $request->validate([
            'prefijo' => ['required', 'regex:/^\d{3}$/'],
            'codigo_tracking' => ['required', 'array', 'min:1'],
            'codigo_tracking.*' => ['required', 'regex:/^\d{8}$/', 'distinct'],
            'fecha_proceso' => ['required', 'date'],
            'destino' => ['required', 'string'],
            'destino_otro' => ['nullable', 'string', 'max:100'],
        ], [
            'codigo_tracking.*.distinct' => 'No se permiten códigos tracking repetidos.',
        ]);

        if ($request->destino !== 'OTRO' && !in_array($request->destino, $destinos, true)) {
            return back()
                ->withErrors(['destino' => 'El destino seleccionado no es válido.'])
                ->withInput();
        }

        if ($request->destino === 'OTRO' && blank($request->destino_otro)) {
            return back()
                ->withErrors(['destino_otro' => 'Debe escribir el destino manual.'])
                ->withInput();
        }

        $destinoFinal = $request->destino === 'OTRO'
            ? trim($request->destino_otro)
            : $request->destino;

        foreach ($request->codigo_tracking as $codigoTracking) {
            $codigoTracking = trim($codigoTracking);

            if ($codigoTracking === '') {
                continue;
            }

            TrackingAlmacenado::create([
                'prefijo' => $request->prefijo,
                'codigo_tracking' => $codigoTracking,
                'fecha_proceso' => $request->fecha_proceso,
                'destino' => $destinoFinal,
            ]);
        }

        return redirect()
            ->route('tracking-almacenado.create')
            ->with('success', 'Trackings guardados correctamente.');
    }

    private function obtenerDestinosPermitidos(): array
    {
        return [
            'SCL ARICA',
            'SCL IQUIQUE',
            'SCL ANTOFAGASTA',
            'SCL CALAMA',
            'SCL PUNTA ARENAS',
            'SCL BALMACEDA',
            'SCL ISLA DE PASCUA',
            'OTRO',
        ];
    }
}