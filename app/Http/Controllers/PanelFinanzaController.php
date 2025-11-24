<?php

namespace App\Http\Controllers;
use App\Models\Abono;
use App\Models\Cruce;
use App\Models\MovimientoDocumento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Exports\MovimientoExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use App\Exports\MovimientoCompraExport;


class PanelFinanzaController extends Controller
{
    //
        /**
     * Mostrar historial combinado de Abonos y Cruces para RCV_VENTAS.
     */
    // public function show(Request $request)
    // {
    //     // 🚫 Control de acceso
    //     $usuariosFinanzas = [1, 405, 374];
    //     if (!in_array(Auth::id(), $usuariosFinanzas)) {
    //         abort(403, 'Acceso denegado.');
    //     }

    //     // === Filtros ===
    //     $fechaInicio = $request->input('fecha_inicio');
    //     $fechaFin    = $request->input('fecha_fin');
    //     $empresaId   = $request->input('empresa_id');
    //     $razonSocial = $request->input('razon_social');
    //     $perPage     = 20;

    //     // === Consulta base ===
    //     $query = \App\Models\MovimientoDocumento::with(['documento.empresa', 'documento.tipoDocumento', 'user'])
    //         ->when($fechaInicio, fn($q) =>
    //             $q->whereDate('movimientos_documentos.created_at', '>=', $fechaInicio)
    //         )
    //         ->when($fechaFin, fn($q) =>
    //             $q->whereDate('movimientos_documentos.created_at', '<=', $fechaFin)
    //         )
    //         ->when($empresaId, fn($q) =>
    //             $q->whereHas('documento', fn($d) =>
    //                 $d->where('empresa_id', $empresaId)
    //             )
    //         )
    //         ->when($razonSocial, fn($q) =>
    //             $q->whereHas('documento', fn($d) =>
    //                 $d->where('razon_social', 'like', "%{$razonSocial}%")
    //             )
    //         )
    //         ->orderByDesc('movimientos_documentos.created_at');

    //     // === Paginación ===
    //     $movimientos = $query->paginate($perPage);

    //     /**
    //      * === TOTAL MOSTRADO ===
    //      * Basado en el saldo pendiente del documento financiero,
    //      * igual que en la vista principal.
    //      */
    //     $totalMontos = $movimientos->getCollection()
    //         ->reduce(function ($carry, $mov) {
    //             if ($mov->documento) {
    //                 // Usa el accessor dinámico del modelo DocumentoFinanciero
    //                 $saldoPendiente = $mov->documento->saldo_pendiente ?? 0;
    //                 return $carry + $saldoPendiente;
    //             }
    //             return $carry;
    //         }, 0);

    //     // === Listado de empresas para filtro ===
    //     $empresas = \App\Models\Empresa::orderBy('Nombre')->get();

    //     // === Vista ===
    //     return view('panelfinanza.show', compact('movimientos', 'empresas', 'totalMontos'));
    // }

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
        $razonSocial = $request->input('razon_social');
        $perPage     = 10;

        // === Consulta base ===
        $query = \App\Models\MovimientoDocumento::with(['documento.empresa', 'documento.tipoDocumento', 'user'])
            ->when($fechaInicio, fn($q) =>
                $q->whereDate('movimientos_documentos.created_at', '>=', $fechaInicio)
            )
            ->when($fechaFin, fn($q) =>
                $q->whereDate('movimientos_documentos.created_at', '<=', $fechaFin)
            )
            ->when($empresaId, fn($q) =>
                $q->whereHas('documento', fn($d) =>
                    $d->where('empresa_id', $empresaId)
                )
            )
            ->when($razonSocial, fn($q) =>
                $q->whereHas('documento', fn($d) =>
                    $d->where('razon_social', 'like', "%{$razonSocial}%")
                )
            )
            ->orderByDesc('movimientos_documentos.created_at');

        // === Paginación ===
        $movimientos = $query->paginate($perPage);

        /**
         * === TOTAL MOSTRADO (flujo de caja) ===
         * Se calcula según el tipo de movimiento:
         *  - Abono / Cruce → suma el monto registrado
         *  - Pago / Pronto pago → suma el monto total del documento
         *  - Eliminaciones → se restan
         */
        $totalMontos = $movimientos->getCollection()->reduce(function ($carry, $mov) {
            $montoMovimiento = 0;
            $tipo = strtolower($mov->tipo_movimiento ?? '');

            // Determinar monto según tipo de movimiento
            if (str_contains($tipo, 'abono')) {
                $montoMovimiento = $mov->datos_nuevos['monto']
                    ?? $mov->datos_anteriores['monto']
                    ?? 0;
            } elseif (str_contains($tipo, 'cruce')) {
                $montoMovimiento = $mov->datos_nuevos['monto']
                    ?? $mov->datos_anteriores['monto']
                    ?? 0;
            } elseif (str_contains($tipo, 'pago') || str_contains($tipo, 'pronto pago')) {
                $montoMovimiento = $mov->documento->monto_total ?? 0;
            }

            // Si es eliminación → monto negativo
            if (str_contains($tipo, 'eliminación')) {
                $montoMovimiento = $montoMovimiento * -1;
            }

            return $carry + $montoMovimiento;
        }, 0);

        // === Listado de empresas para filtro ===
        $empresas = \App\Models\Empresa::orderBy('Nombre')->get();

        // === Vista ===
        return view('panelfinanza.show', compact('movimientos', 'empresas', 'totalMontos'));
    }








    //Exportar información de las ventas
    public function export(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin    = $request->input('fecha_fin');

        return Excel::download(
            new MovimientoExport($fechaInicio, $fechaFin),
            'Historial_Movimientos_' . now()->format('Ymd_His') . '.xlsx'
        );
    }






    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// 
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////  

    /**
 * Mostrar historial combinado de Abonos, Cruces y Pagos para RCV_COMPRAS.
 */
    // public function showCompras(Request $request)
    // {
    //     // 🚫 Control de acceso
    //     $usuariosFinanzas = [1, 405, 374];
    //     if (!in_array(Auth::id(), $usuariosFinanzas)) {
    //         abort(403, 'Acceso denegado.');
    //     }

    //     // === Filtros ===
    //     $fechaInicio = $request->input('fecha_inicio');
    //     $fechaFin    = $request->input('fecha_fin');
    //     $empresaId   = $request->input('empresa_id');
    //     $razonSocial = $request->input('razon_social'); // proveedor
    //     $perPage     = 20;

    //     // === Consulta base ===
    //     $query = \App\Models\MovimientoCompra::with(['compra.empresa', 'user'])
    //         ->when($fechaInicio, fn($q) =>
    //             $q->whereDate('movimientos_compras.created_at', '>=', $fechaInicio)
    //         )
    //         ->when($fechaFin, fn($q) =>
    //             $q->whereDate('movimientos_compras.created_at', '<=', $fechaFin)
    //         )
    //         ->when($empresaId, fn($q) =>
    //             $q->whereHas('compra', fn($d) =>
    //                 $d->where('empresa_id', $empresaId)
    //             )
    //         )
    //         ->when($razonSocial, fn($q) =>
    //             $q->whereHas('compra', fn($d) =>
    //                 $d->where('razon_social', 'like', "%{$razonSocial}%")
    //             )
    //         )
    //         ->orderByDesc('movimientos_compras.created_at');

    //     // === Paginación ===
    //     $movimientos = $query->paginate($perPage);

    //     // === Total montos ===
    //     $totalMontos = (clone $query)
    //         ->join('documentos_compras as dc', 'dc.id', '=', 'movimientos_compras.documento_compra_id')
    //         ->sum('dc.monto_total');

    //     // === Listado de empresas para filtro ===
    //     $empresas = \App\Models\Empresa::orderBy('Nombre')->get();

    //     // === Vista ===
    //     return view('panelfinanza.show_compra', compact('movimientos', 'empresas', 'totalMontos'));
    // }
    public function showCompras(Request $request)
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
        $razonSocial = $request->input('razon_social');
        $perPage     = 20;

        // === Consulta base ===
        $query = \App\Models\MovimientoCompra::with(['compra.empresa', 'user'])
            ->when($fechaInicio, fn($q) =>
                $q->whereDate('movimientos_compras.created_at', '>=', $fechaInicio)
            )
            ->when($fechaFin, fn($q) =>
                $q->whereDate('movimientos_compras.created_at', '<=', $fechaFin)
            )
            ->when($empresaId, fn($q) =>
                $q->whereHas('compra', fn($d) =>
                    $d->where('empresa_id', $empresaId)
                )
            )
            ->when($razonSocial, fn($q) =>
                $q->whereHas('compra', fn($d) =>
                    $d->where('razon_social', 'like', "%{$razonSocial}%")
                )
            )
            ->orderByDesc('movimientos_compras.created_at');

        $movimientos = $query->paginate($perPage);

        // === Listado de empresas para filtro ===
        $empresas = \App\Models\Empresa::orderBy('Nombre')->get();

        return view('panelfinanza.show_compra', compact('movimientos', 'empresas'));
    }

    public function exportCompras (Request $request)
    {

        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin    = $request->input('fecha_fin');

        return Excel::download(
            new MovimientoCompraExport($fechaInicio, $fechaFin),
            'Historial_Movimientos_Compras_' . now()->format('Ymd_His') . '.xlsx'
        );

    }






}
