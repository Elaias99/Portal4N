<?php

namespace App\Http\Controllers;

use App\Models\Cruce;
use App\Models\DocumentoFinanciero;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CruceController extends Controller
{

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // El método que guarda los abonos se encuentra en el controlador DocumentoFinancieroController, llamado storeCruce //
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Mostrar todos los cruces asociados a un documento.
     */
    public function index(DocumentoFinanciero $documento)
    {
        // Obtener cruces ordenados por fecha
        $cruces = $documento->cruces()->orderBy('fecha_cruce', 'asc')->get();

        // Calcular totales
        $totalCruzado = $cruces->sum('monto');
        $saldoPendiente = $documento->saldo_pendiente;

        return view('cruces.index', compact('documento', 'cruces', 'totalCruzado', 'saldoPendiente'));
    }

    /**
     * Mostrar formulario de edición de un cruce.
     */
    public function edit($id)
    {
        $cruce = Cruce::findOrFail($id);
        $documento = $cruce->documento;

        return view('cruces.edit', compact('cruce', 'documento'));
    }

    /**
     * Actualizar los datos de un cruce.
     */
    public function update(Request $request, $id)
    {
        $cruce = Cruce::with([
            'documento',
            'documentoCompra',
        ])->findOrFail($id);

        $request->validate([
            'monto' => 'required|integer|min:1',
            'fecha_cruce' => 'required|date|before_or_equal:today',
        ], [
            'fecha_cruce.before_or_equal' => 'La fecha del cruce no debe sobrepasar la fecha actual.',
            'fecha_cruce.required' => 'La fecha del cruce es obligatoria.',
        ]);

        $documentoFinanciero = $cruce->documento;
        $documentoCompra = $cruce->documentoCompra;

        $cruce->update([
            'monto' => $request->monto,
            'fecha_cruce' => $request->fecha_cruce,
        ]);

        if ($documentoFinanciero) {
            $documentoFinanciero->recalcularSaldoPendiente();
            $documentoFinanciero->refresh();

            if (method_exists($documentoFinanciero, 'sincronizarEstadosDesdeMovimientos')) {
                $documentoFinanciero->sincronizarEstadosDesdeMovimientos();
                $documentoFinanciero->refresh();
            }
        }

        if ($documentoCompra) {
            $documentoCompra->recalcularSaldoPendiente();
            $documentoCompra->refresh();
        }

        return redirect()
            ->route('cruces.index', $cruce->documento_financiero_id)
            ->with('success', 'Cruce actualizado correctamente.');
    }

    public function destroy($id)
    {
        $cruce = Cruce::with([
            'documento',
            'documentoCompra',
        ])->findOrFail($id);

        $documentoFinanciero = $cruce->documento;
        $documentoCompra = $cruce->documentoCompra;

        $documentoFinancieroId = $documentoFinanciero?->id;
        $documentoCompraId = $documentoCompra?->id;

        $datosAnteriores = [
            'cruce_id' => $cruce->id,
            'documento_financiero_id' => $cruce->documento_financiero_id,
            'documento_compra_id' => $cruce->documento_compra_id,
            'cobranza_id' => $cruce->cobranza_id,
            'cobranza_compra_id' => $cruce->cobranza_compra_id,
            'proveedor_id' => $cruce->proveedor_id,
            'monto' => $cruce->monto,
            'fecha_cruce' => $cruce->fecha_cruce,
        ];

        $estadoAnteriorFinanciero = $documentoFinanciero?->status;
        $estadoAnteriorCompra = $documentoCompra?->estado;

        try {
            DB::transaction(function () use (
                $cruce,
                $documentoFinanciero,
                $documentoCompra,
                $datosAnteriores,
                $estadoAnteriorFinanciero,
                $estadoAnteriorCompra
            ) {
                $cruce->delete();

                if ($documentoFinanciero) {
                    $documentoFinanciero->recalcularSaldoPendiente();
                    $documentoFinanciero->refresh();

                    if (method_exists($documentoFinanciero, 'sincronizarEstadosDesdeMovimientos')) {
                        $nuevoEstadoManual = $documentoFinanciero->sincronizarEstadosDesdeMovimientos();
                        $documentoFinanciero->refresh();

                        $nuevoStatusOriginal = $documentoFinanciero->status_original;
                    } else {
                        $nuevoEstadoManual = $documentoFinanciero->status;
                        $nuevoStatusOriginal = $documentoFinanciero->status_original;
                    }

                    \App\Models\MovimientoDocumento::create([
                        'documento_financiero_id' => $documentoFinanciero->id,
                        'user_id' => Auth::id(),
                        'tipo_movimiento' => 'Eliminación de cruce',
                        'descripcion' => "Se eliminó el cruce ID {$datosAnteriores['cruce_id']} por {$datosAnteriores['monto']} correspondiente al documento financiero folio {$documentoFinanciero->folio}.",
                        'datos_anteriores' => array_merge($datosAnteriores, [
                            'status_anterior' => $estadoAnteriorFinanciero,
                        ]),
                        'datos_nuevos' => [
                            'nuevo_estado_manual' => $nuevoEstadoManual,
                            'nuevo_status_original' => $nuevoStatusOriginal,
                            'saldo_actual' => $documentoFinanciero->saldo_pendiente,
                        ],
                    ]);
                }

                if ($documentoCompra) {
                    $documentoCompra->recalcularSaldoPendiente();
                    $documentoCompra->refresh();

                    $totalCruces = $documentoCompra->cruces()->sum('monto');
                    $totalAbonos = $documentoCompra->abonos()->sum('monto');
                    $tienePagos = $documentoCompra->pagos()->exists();
                    $tieneProntoPagos = $documentoCompra->prontoPagos()->exists();

                    if ($tienePagos) {
                        $nuevoEstadoManual = 'Pago';
                    } elseif ($tieneProntoPagos) {
                        $nuevoEstadoManual = 'Pronto pago';
                    } elseif ($totalCruces > 0) {
                        $nuevoEstadoManual = 'Cruce';
                    } elseif ($totalAbonos > 0) {
                        $nuevoEstadoManual = 'Abono';
                    } else {
                        $nuevoEstadoManual = null;
                    }

                    if ($documentoCompra->fecha_vencimiento) {
                        $nuevoStatusOriginal = now()->gt(Carbon::parse($documentoCompra->fecha_vencimiento))
                            ? 'Vencido'
                            : 'Al día';
                    } else {
                        $nuevoStatusOriginal = 'Sin cálculo';
                    }

                    $documentoCompra->update([
                        'estado' => $nuevoEstadoManual,
                        'status_original' => $nuevoStatusOriginal,
                        'fecha_estado_manual' => $nuevoEstadoManual ? now() : null,
                    ]);

                    $documentoCompra->refresh();

                    \App\Models\MovimientoCompra::create([
                        'documento_compra_id' => $documentoCompra->id,
                        'usuario_id' => Auth::id(),
                        'estado_anterior' => $estadoAnteriorCompra,
                        'nuevo_estado' => $nuevoEstadoManual,
                        'fecha_cambio' => now(),
                        'tipo_movimiento' => 'Eliminación de cruce',
                        'descripcion' => "Se eliminó el cruce ID {$datosAnteriores['cruce_id']} por {$datosAnteriores['monto']} correspondiente al documento de compra folio {$documentoCompra->folio}.",
                        'datos_anteriores' => array_merge($datosAnteriores, [
                            'estado_anterior' => $estadoAnteriorCompra,
                        ]),
                        'datos_nuevos' => [
                            'nuevo_estado_manual' => $nuevoEstadoManual,
                            'nuevo_status_original' => $nuevoStatusOriginal,
                            'saldo_actual' => $documentoCompra->saldo_pendiente,
                        ],
                    ]);
                }
            });
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withErrors(['cruce' => 'Ocurrió un error al eliminar el cruce.'])
                ->withInput();
        }

        $urlAnterior = url()->previous();

        if ($documentoCompraId && str_contains($urlAnterior, '/finanzas/compras')) {
            return redirect()
                ->route('finanzas_compras.show', $documentoCompraId)
                ->with('success', 'Cruce eliminado, saldos recalculados y movimientos registrados correctamente.');
        }

        if ($documentoFinancieroId) {
            return redirect()
                ->route('documentos.detalles', $documentoFinancieroId)
                ->with('success', 'Cruce eliminado, saldos recalculados y movimientos registrados correctamente.');
        }

        if ($documentoCompraId) {
            return redirect()
                ->route('finanzas_compras.show', $documentoCompraId)
                ->with('success', 'Cruce eliminado, saldos recalculados y movimientos registrados correctamente.');
        }

        return back()->with('success', 'Cruce eliminado correctamente.');
    }

    public function show()
    {
        // Traer todos los cruces con su documento asociado
        $cruces = \App\Models\Cruce::with('documento')
            ->orderByDesc('fecha_cruce')
            ->get();

        // Totales generales
        $totalCruzado = $cruces->sum('monto');
        $cantidadCruces = $cruces->count();

        return view('cruces.show', compact('cruces', 'totalCruzado', 'cantidadCruces'));
    }


}
