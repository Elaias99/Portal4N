<?php

namespace App\Http\Controllers;
use App\Services\Ventas\HistorialMovimientosCxCService;
use App\Models\MovimientoDocumento;
use App\Models\MovimientoCompra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Exports\MovimientoExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use App\Exports\MovimientoCompraExport;


class PanelFinanzaController extends Controller
{

    public function show(Request $request, HistorialMovimientosCxCService $historialCxC)
    {
        // Control de acceso
        $usuariosFinanzas = [1, 405, 374];

        if (!in_array(Auth::id(), $usuariosFinanzas)) {
            abort(403, 'Acceso denegado.');
        }

        // === Filtros ===
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin    = $request->input('fecha_fin');
        $empresaId   = $request->input('empresa_id');
        $razonSocial = $request->input('razon_social');
        $perPage     = 10;

        // === Consulta base CxC ===
        $query = MovimientoDocumento::with([
                'documento.empresa',
                'documento.tipoDocumento',
                'user',
                'origen',
            ]);

        $historialCxC->aplicarFiltroFechaMovimiento(
            query: $query,
            fechaInicio: $fechaInicio,
            fechaFin: $fechaFin,
        );

        $query
            ->when($empresaId, fn ($q) =>
                $q->whereHas('documento', fn ($d) =>
                    $d->where('empresa_id', $empresaId)
                )
            )

            ->when($razonSocial, fn ($q) =>
                $q->whereHas('documento', fn ($d) =>
                    $d->where('razon_social', 'like', "%{$razonSocial}%")
                )
            )

            ->orderByDesc('movimientos_documentos.created_at');

        // === Paginación ===
        $movimientos = $query->paginate($perPage);

        // === Enriquecer montos y fechas reales del historial CxC ===
        $historialCxC->enriquecerPaginador($movimientos);

        // === Total mostrado según montos reales calculados ===
        $totalMontos = $historialCxC->totalizar($movimientos->getCollection());

        // === Listado de empresas para filtro ===
        $empresas = \App\Models\Empresa::orderBy('Nombre')->get();

        return view('panelfinanza.show', compact(
            'movimientos',
            'empresas',
            'totalMontos'
        ));
    }







    //Exportar información de las ventas
    public function export(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin    = $request->input('fecha_fin');

        return Excel::download(
            new MovimientoExport($fechaInicio, $fechaFin),
            'Historial_Movimientos_Cuentas_Por_Cobrar' . now()->format('Ymd_His') . '.xlsx'
        );
    }






    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// 
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////  


    public function showCompras(Request $request)
    {
        // === Control de acceso ===
        $usuariosFinanzas = [1, 405, 374];
        if (!in_array(Auth::id(), $usuariosFinanzas)) {
            abort(403, 'Acceso denegado.');
        }

        // === Filtros ===
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin    = $request->input('fecha_fin');
        $empresaId   = $request->input('empresa_id');
        $razonSocial = $request->input('razon_social');
        $perPage     = 20;

        // === Consulta base ===
        $query = \App\Models\MovimientoCompra::with(['compra.empresa', 'compra.tipoDocumento', 'user'])
            ->when($fechaInicio || $fechaFin, function ($q) use ($fechaInicio, $fechaFin) {
                $q->where(function ($sub) use ($fechaInicio, $fechaFin) {
                    // FILTRO PARA ABONOS
                    $sub->orWhereHas('compra.abonos', function ($a) use ($fechaInicio, $fechaFin) {
                        if ($fechaInicio) {
                            $a->whereDate('fecha_abono', '>=', $fechaInicio);
                        }
                        if ($fechaFin) {
                            $a->whereDate('fecha_abono', '<=', $fechaFin);
                        }
                    });

                    // FILTRO PARA CRUCES
                    $sub->orWhereHas('compra.cruces', function ($c) use ($fechaInicio, $fechaFin) {
                        if ($fechaInicio) {
                            $c->whereDate('fecha_cruce', '>=', $fechaInicio);
                        }
                        if ($fechaFin) {
                            $c->whereDate('fecha_cruce', '<=', $fechaFin);
                        }
                    });

                    // FILTRO PARA PAGOS
                    $sub->orWhereHas('compra.pagos', function ($p) use ($fechaInicio, $fechaFin) {
                        if ($fechaInicio) {
                            $p->whereDate('fecha_pago', '>=', $fechaInicio);
                        }
                        if ($fechaFin) {
                            $p->whereDate('fecha_pago', '<=', $fechaFin);
                        }
                    });

                    // FILTRO PARA PRONTO PAGO
                    $sub->orWhereHas('compra.prontoPagos', function ($pp) use ($fechaInicio, $fechaFin) {
                        if ($fechaInicio) {
                            $pp->whereDate('fecha_pronto_pago', '>=', $fechaInicio);
                        }
                        if ($fechaFin) {
                            $pp->whereDate('fecha_pronto_pago', '<=', $fechaFin);
                        }
                    });
                });
            })
            ->when($empresaId, fn($q) =>
                $q->whereHas('compra', fn($c) =>
                    $c->where('empresa_id', $empresaId)
                )
            )
            ->when($razonSocial, fn($q) =>
                $q->whereHas('compra', fn($c) =>
                    $c->where('razon_social', 'like', "%{$razonSocial}%")
                )
            )
            ->orderByDesc('movimientos_compras.created_at');

        // === Paginación ===
        $movimientos = $query->paginate($perPage);

        // === Total mostrado (flujo de egresos de compra) ===
        $totalMontos = $movimientos->getCollection()->reduce(function ($carry, $mov) {
            $montoMovimiento = 0;
            $tipo = strtolower($mov->tipo_movimiento ?? '');

            if (str_contains($tipo, 'abono')) {
                $montoMovimiento = $mov->datos_nuevos['monto']
                    ?? $mov->datos_anteriores['monto']
                    ?? 0;
            } elseif (str_contains($tipo, 'cruce')) {
                $montoMovimiento = $mov->datos_nuevos['monto']
                    ?? $mov->datos_anteriores['monto']
                    ?? 0;
            } elseif (str_contains($tipo, 'pago') || str_contains($tipo, 'pronto pago')) {
                $montoMovimiento = $mov->compra->monto_total ?? 0;
            }

            if (str_contains($tipo, 'eliminación')) {
                $montoMovimiento *= -1;
            }

            return $carry + $montoMovimiento;
        }, 0);

        // === Listado de empresas ===
        $empresas = \App\Models\Empresa::orderBy('Nombre')->get();

        // === Vista ===
        return view('panelfinanza.show_compra', compact('movimientos', 'empresas', 'totalMontos'));
    }




    public function exportCompras (Request $request)
    {

        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin    = $request->input('fecha_fin');

        return Excel::download(
            new MovimientoCompraExport($fechaInicio, $fechaFin),
            'Historial_Movimientos_Cuentas_Por_Pagar_' . now()->format('Ymd_His') . '.xlsx'
        );

    }






}
