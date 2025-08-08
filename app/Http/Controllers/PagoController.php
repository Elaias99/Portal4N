<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Compra;
use App\Models\Proveedor;
use App\Models\Empresa;

use App\Exports\PagosSeleccionadosExport;
use Maatwebsite\Excel\Facades\Excel;


class PagoController extends Controller
{
    //
    public function index(Request $request)
    {
        
        $query = Compra::with(['proveedor', 'empresa'])
            ->where('status', 'pendiente')
            ->whereBetween('fecha_vencimiento', [now()->startOfWeek(), now()->endOfWeek()])
            ->orderBy('fecha_vencimiento', 'asc');


        // Filtro por empresa
        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->input('empresa_id'));
        }

        // Filtro por razón social del proveedor
        if ($request->filled('proveedor_id')) {
            $query->where('proveedor_id', $request->input('proveedor_id'));
        }

        // Filtro por fecha del documento
        if ($request->filled('fecha_documento')) {
            $query->whereDate('fecha_documento', $request->input('fecha_documento'));
        }

        $compras = $query->paginate(10)->appends($request->query());

        $totalGeneral = $this->calcularTotalGeneral($compras);

        $proveedores = Proveedor::all();
        $empresas = Empresa::all();


        $proximosPagos = Compra::with('proveedor')
            ->where('status', 'pendiente')
            ->whereDate('fecha_vencimiento', '>', now())
            ->orderBy('fecha_vencimiento', 'asc')
            ->get()
            ->groupBy(function($compra) {
                // Normaliza la fecha al viernes de esa semana
                return \Carbon\Carbon::parse($compra->fecha_vencimiento)->next(\Carbon\Carbon::FRIDAY)->format('Y-m-d');
            })
            ->map(function($grupo) {
                return [
                    'fecha' => \Carbon\Carbon::parse($grupo->first()->fecha_vencimiento)
                                ->next(\Carbon\Carbon::FRIDAY)
                                ->format('Y-m-d'),
                    'cantidad' => $grupo->count(),
                    'total' => $grupo->sum('pago_total')
                ];
            })
            ->sortBy('fecha')
            ->values();



        // ✅ Detectar si se acaba de exportar
        $mensaje = null;
        if ($request->query('exportado') === 'ok') {
            $mensaje = 'Pagos exportados correctamente y marcados como Pagado.';
        }

        return view('pagos.index', compact('compras', 'totalGeneral', 'proveedores', 'empresas', 'mensaje','proximosPagos'));
    }




    private function calcularTotalGeneral($compras)
    {
        return $compras->sum('pago_total');
    }

    public function exportarSeleccionados(Request $request)
    {
        $rawIds = $request->input('ids');
        $ids = is_array($rawIds) ? $rawIds : json_decode($rawIds, true);

        if (!is_array($ids) || empty($ids)) {
            return back()->with('error', 'No se seleccionaron compras para exportar.');
        }

        // ✅ Paso nuevo: actualizar el estado a "Pagado"
        Compra::whereIn('id', $ids)->update(['status' => 'Pagado']);

        // Luego, proceder con la exportación
        return Excel::download(new PagosSeleccionadosExport($ids), 'pagos_seleccionados.xlsx');
    }


    public function toggleImportante($id)
    {
        $compra = Compra::findOrFail($id);
        $compra->importante = !$compra->importante;
        $compra->save();

        return redirect()->back()->with('success', 'Compra marcada como importante');
    }




}
