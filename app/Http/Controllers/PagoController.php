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

        $compras = $query->get();

        // Recalcula el total general con los resultados filtrados
        $totalGeneral = $this->calcularTotalGeneral($compras);

        $proveedores = Proveedor::all();
        $empresas = Empresa::all();

        return view('pagos.index', compact('compras', 'totalGeneral', 'proveedores', 'empresas'));
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

        return Excel::download(new PagosSeleccionadosExport($ids), 'pagos_seleccionados.xlsx');
    }

}
