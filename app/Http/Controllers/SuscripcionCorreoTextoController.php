<?php

namespace App\Http\Controllers;

use App\Models\SuscripcionLiquidacionDetalle;
use App\Services\Suscripciones\SuscripcionCorreoTextoService;
use Illuminate\Http\Request;

class SuscripcionCorreoTextoController extends Controller
{
    private const MESES = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];

    /*
    * Pantalla para escribir el texto del correo de un período.
    */
    public function edit(Request $request, SuscripcionCorreoTextoService $textoService)
    {
        $request->validate([
            'anio' => 'nullable|integer|min:2020|max:2100',
            'mes' => 'nullable|integer|min:1|max:12',
        ]);

        [$anio, $mes] = $this->periodo($request);

        return view('suscripciones.correo_texto.edit', [
            'anio' => $anio,
            'mes' => $mes,
            'meses' => self::MESES,
            'mesNombre' => self::MESES[$mes],
            'texto' => $textoService->paraEditar($anio, $mes),
        ]);
    }

    public function update(Request $request, SuscripcionCorreoTextoService $textoService)
    {
        $data = $request->validate([
            'anio' => 'required|integer|min:2020|max:2100',
            'mes' => 'required|integer|min:1|max:12',
            'cuerpo' => 'required|string|max:5000',
        ], [
            'cuerpo.required' => 'Escribe el texto del correo.',
            'cuerpo.max' => 'El texto del correo es demasiado largo.',
        ]);

        if (str_contains($data['cuerpo'], 'DD/MM')) {
            return back()
                ->withInput()
                ->withErrors([
                    'cuerpo' => 'Reemplaza cada DD/MM por la fecha real antes de guardar.',
                ]);
        }

        $textoService->guardar(
            (int) $data['anio'],
            (int) $data['mes'],
            $data['cuerpo']
        );

        return redirect()
            ->route('suscripciones.correo-texto.edit', [
                'anio' => $data['anio'],
                'mes' => $data['mes'],
            ])
            ->with(
                'success',
                'Texto del correo de ' . self::MESES[(int) $data['mes']] . ' ' . $data['anio'] . ' guardado.'
            );
    }

    /*
    * Período pedido, o el último generado si no viene ninguno.
    */
    private function periodo(Request $request): array
    {
        if ($request->filled('anio') && $request->filled('mes')) {
            return [(int) $request->anio, (int) $request->mes];
        }

        $ultimo = SuscripcionLiquidacionDetalle::query()
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->first(['anio', 'mes']);

        return $ultimo
            ? [(int) $ultimo->anio, (int) $ultimo->mes]
            : [(int) now()->year, (int) now()->month];
    }
}
