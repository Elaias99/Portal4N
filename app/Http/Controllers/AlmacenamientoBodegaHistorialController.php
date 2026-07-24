<?php

namespace App\Http\Controllers;

use App\Models\AlmacenamientoBodegaHistorial;
use Illuminate\Http\Request;

class AlmacenamientoBodegaHistorialController extends Controller
{
    public function index(Request $request)
    {
        $anio = (int) $request->input('anio', now()->year);
        $mes = (int) $request->input('mes', now()->month);

        $historial = AlmacenamientoBodegaHistorial::query()
            ->whereYear('created_at', $anio)
            ->whereMonth('created_at', $mes)
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view(
            'almacenamiento.historial.index',
            compact('historial', 'anio', 'mes')
        );
    }
}