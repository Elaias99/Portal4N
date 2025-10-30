<?php

namespace App\Http\Controllers;
use App\Models\Abono;
use App\Models\Cruce;
use App\Models\DocumentoFinanciero;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Exports\MovimientoExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
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
            abort(403, 'Acceso denegado.');
        }

        // === Filtros ===
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin    = $request->input('fecha_fin');
        $empresaId   = $request->input('empresa_id');
        $razonSocial = $request->input('razon_social'); // 👈 nuevo filtro
        $perPage     = 15;

        // === Subconsulta ABONOS ===
        $abonosQuery = DB::table('abonos')
            ->selectRaw("
                'Abono' AS tipo,
                fecha_abono AS fecha,
                monto,
                documento_financiero_id
            ")
            ->when($fechaInicio, fn($q) => $q->whereDate('fecha_abono', '>=', $fechaInicio))
            ->when($fechaFin, fn($q) => $q->whereDate('fecha_abono', '<=', $fechaFin))
            ->when($empresaId, function ($q) use ($empresaId) {
                $q->whereIn('documento_financiero_id', function ($sub) use ($empresaId) {
                    $sub->select('id')->from('documentos_financieros')->where('empresa_id', $empresaId);
                });
            })
            ->when($razonSocial, function ($q) use ($razonSocial) {
                $q->whereIn('documento_financiero_id', function ($sub) use ($razonSocial) {
                    $sub->select('id')
                        ->from('documentos_financieros')
                        ->where('razon_social', 'like', "%{$razonSocial}%");
                });
            });

        // === Subconsulta CRUCES ===
        $crucesQuery = DB::table('cruces')
            ->selectRaw("
                'Cruce' AS tipo,
                fecha_cruce AS fecha,
                monto,
                documento_financiero_id
            ")
            ->when($fechaInicio, fn($q) => $q->whereDate('fecha_cruce', '>=', $fechaInicio))
            ->when($fechaFin, fn($q) => $q->whereDate('fecha_cruce', '<=', $fechaFin))
            ->when($empresaId, function ($q) use ($empresaId) {
                $q->whereIn('documento_financiero_id', function ($sub) use ($empresaId) {
                    $sub->select('id')->from('documentos_financieros')->where('empresa_id', $empresaId);
                });
            })
            ->when($razonSocial, function ($q) use ($razonSocial) {
                $q->whereIn('documento_financiero_id', function ($sub) use ($razonSocial) {
                    $sub->select('id')
                        ->from('documentos_financieros')
                        ->where('razon_social', 'like', "%{$razonSocial}%");
                });
            });

        // === Subconsulta PAGOS ===
        $pagosQuery = DB::table('pagos as p')
            ->join('documentos_financieros as df', 'p.documento_financiero_id', '=', 'df.id')
            ->selectRaw("
                'Pago' AS tipo,
                p.fecha_pago AS fecha,
                df.monto_total AS monto,
                p.documento_financiero_id
            ")
            ->when($fechaInicio, fn($q) => $q->whereDate('p.fecha_pago', '>=', $fechaInicio))
            ->when($fechaFin, fn($q) => $q->whereDate('p.fecha_pago', '<=', $fechaFin))
            ->when($empresaId, fn($q) => $q->where('df.empresa_id', $empresaId))
            ->when($razonSocial, fn($q) => $q->where('df.razon_social', 'like', "%{$razonSocial}%"));



        // === UNION ===
        $union = $abonosQuery->unionAll($crucesQuery)->unionAll($pagosQuery);

        // === TOTAL FILTRADO ===
        $totalMontos = DB::table(DB::raw("({$union->toSql()}) as movimientos"))
            ->mergeBindings($union)
            ->sum('monto');


        $movimientos = DB::table(DB::raw("({$union->toSql()}) as movimientos"))
            ->mergeBindings($union)
            ->orderByDesc('fecha')
            ->paginate($perPage);

        // === Cargar documentos y usuarios ===
        $documentos = \App\Models\DocumentoFinanciero::with('empresa')
            ->whereIn('id', $movimientos->pluck('documento_financiero_id'))
            ->get()
            ->keyBy('id');

        $ultimoMovimientoPorDoc = \App\Models\MovimientoDocumento::with('user')
            ->whereIn('documento_financiero_id', $movimientos->pluck('documento_financiero_id'))
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy('documento_financiero_id')
            ->map(fn($items) => $items->first());

        $movimientos->getCollection()->transform(function ($m) use ($documentos, $ultimoMovimientoPorDoc) {
            $m->documento = $documentos->get($m->documento_financiero_id);
            $m->usuario   = optional($ultimoMovimientoPorDoc->get($m->documento_financiero_id)?->user);
            return $m;
        });

        

        $empresas = \App\Models\Empresa::orderBy('Nombre')->get();

        return view('panelfinanza.show', compact('movimientos', 'empresas', 'totalMontos'));
    }





    public function export(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin    = $request->input('fecha_fin');

        return Excel::download(
            new MovimientoExport($fechaInicio, $fechaFin),
            'Historial_Movimientos_' . now()->format('Ymd_His') . '.xlsx'
        );
    }


}
