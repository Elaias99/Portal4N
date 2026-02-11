<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\ExportMovimientoHonorarioMensual;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\MovimientoHonorarioMensualRec;
use App\Models\Empresa;


class MovimientoHonorarioMensualRecController extends Controller
{
    public function historial(Request $request)
    {
        $query = MovimientoHonorarioMensualRec::with([
                'user',
                'honorario.empresa',
            ]);


        // =========================
        // FILTRO: USUARIO
        // =========================
        if ($request->filled('usuario')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->usuario . '%');
            });
        }

            // =========================
            // FILTRO: EMPRESA
            // =========================
            if ($request->filled('empresa_id')) {
                $query->whereHas('honorario', function ($q) use ($request) {
                    $q->where('empresa_id', $request->empresa_id);
                });
            }

        // =========================
        // FILTRO: TIPO MOVIMIENTO
        // =========================
        if ($request->filled('tipo')) {
            $query->where('tipo_movimiento', $request->tipo);
        }

        // =========================
        // FILTRO: FECHA
        // =========================
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_cambio', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_cambio', '<=', $request->fecha_hasta);
        }

        $movimientos = $query
            ->orderBy('fecha_cambio', 'desc')
            ->paginate(30)
            ->appends($request->query());

        $empresas = Empresa::orderBy('Nombre')
            ->get(['id', 'Nombre']);


        return view('boleta_mensual.historial', compact('movimientos', 'empresas'));
    }




    //
    public function export()
    {
        return Excel::download(
            new ExportMovimientoHonorarioMensual,
            'movimientos_honorarios_mensuales_rec.xlsx'
        );
    }
}
