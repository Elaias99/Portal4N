<?php

namespace App\Http\Controllers;

use App\Models\TrackingAlmacenado;
use Illuminate\Http\Request;

class LatamTrackingController extends Controller
{
    public function index()
    {
        $rows = TrackingAlmacenado::orderByDesc('id')
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

    public function process(Request $request)
    {
        return redirect()->route('latam.tracking.index');
    }
}