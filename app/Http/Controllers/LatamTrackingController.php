<?php

namespace App\Http\Controllers;

use App\Models\TrackingAlmacenado;
use Illuminate\Http\Request;

class LatamTrackingController extends Controller
{
    public function index(Request $request)
    {
        $query = TrackingAlmacenado::query();

        if ($request->filled('prefijo')) {
            $query->where('prefijo', 'like', '%' . trim($request->prefijo) . '%');
        }

        if ($request->filled('codigo_tracking')) {
            $query->where('codigo_tracking', 'like', '%' . trim($request->codigo_tracking) . '%');
        }

        if ($request->filled('destino')) {
            $query->where('destino', 'like', '%' . trim($request->destino) . '%');
        }

        if ($request->filled('fecha_proceso')) {
            $query->whereDate('fecha_proceso', $request->fecha_proceso);
        }

        $rows = $query->orderByDesc('id')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'prefix' => $item->prefijo,
                    'code' => $item->codigo_tracking,
                    'destino' => $item->destino,
                    'fecha_proceso' => $item->fecha_proceso,
                    'url' => 'https://www.latamcargo.com/en/trackshipment?docNumber='
                        . $item->codigo_tracking
                        . '&docPrefix='
                        . $item->prefijo
                        . '&soType=SO',
                ];
            });

        return view('latam-tracking.index', compact('rows'));
    }
}