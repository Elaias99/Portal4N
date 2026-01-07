<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentoFinanciero;
use Illuminate\Support\Facades\Auth;
use App\Models\MovimientoDocumento;
use App\Models\MovimientoCompra;
use App\Models\DocumentoCompra;
use App\Models\Pago;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PagosMasivosDocumentoCompraExport;


class PagoDocumentoController extends Controller
{
    //

    public function store(Request $request, $id)
    {
        $request->validate([
            'fecha_pago' => 'required|date|before_or_equal:today',
        ], [
            'fecha_pago.before_or_equal' => 'La fecha del pago no debe sobrepasar la fecha actual.',
            'fecha_pago.required' => 'La fecha del pago es obligatoria.',
        ]);

        $tipo = $request->input('tipo', 'ventas');
        $esCompra = $tipo === 'compra';

        $documento = $esCompra
            ? \App\Models\DocumentoCompra::findOrFail($id)
            : \App\Models\DocumentoFinanciero::findOrFail($id);

        // 🚫 Evitar duplicados
        if ($documento->pagos()->exists()) {
            return back()->withErrors(['fecha_pago' => 'Este documento ya tiene un pago registrado.']);
        }

        $estadoAnterior = $documento->estado ?? $documento->status;
        $saldoAnterior = $documento->saldo_pendiente;

        // ✅ Crear el pago
        $documento->pagos()->create([
            'fecha_pago' => $request->fecha_pago,
            'user_id' => Auth::id(),
        ]);

        // ✅ Actualizar estado y saldo
        $campoEstado = $esCompra ? 'estado' : 'status';
        $documento->update([
            $campoEstado => 'Pago',
            'fecha_estado_manual' => now(),
            'saldo_pendiente' => 0,
        ]);

        // ✅ Registrar movimiento
        if ($esCompra) {
            \App\Models\MovimientoCompra::create([
                'documento_compra_id' => $documento->id,
                'usuario_id' => Auth::id(),
                'estado_anterior' => $estadoAnterior,
                'nuevo_estado' => 'Pago',
                'fecha_cambio' => now(),
                'tipo_movimiento' => 'Registro de pago',
                'descripcion' => "Se registró un pago el {$request->fecha_pago}.",
                'datos_anteriores' => [
                    'estado' => $estadoAnterior,
                    'saldo_anterior' => $saldoAnterior,
                ],
                'datos_nuevos' => [
                    'fecha_pago' => $request->fecha_pago,
                    'saldo_actual' => 0,
                ],
            ]);
        } else {
            \App\Models\MovimientoDocumento::create([
                'documento_financiero_id' => $documento->id,
                'user_id' => Auth::id(),
                'tipo_movimiento' => 'Pago registrado',
                'descripcion' => "Se registró un pago el {$request->fecha_pago}.",
                'datos_nuevos' => [
                    'fecha_pago' => $request->fecha_pago,
                    'saldo_actual' => 0,
                ],
            ]);
        }

        return back()->with('success', 'Pago registrado correctamente y estado actualizado.');
    }



    public function destroy($id)
    {
        $pago = \App\Models\Pago::findOrFail($id);
        $documento = $pago->documentoFinanciero ?? $pago->documentoCompra;

        $tipoDocumento = $documento instanceof \App\Models\DocumentoCompra ? 'compra' : 'financiero';

        $estadoAnterior = $documento->estado ?? $documento->status;
        $saldoAnterior = $documento->saldo_pendiente;

        $datosAnteriores = [
            'fecha_pago' => $pago->fecha_pago,
            'user_id' => $pago->user_id,
        ];

        // 🔹 Eliminar pago
        $pago->delete();

        // 🔹 Recalcular saldo
        if (method_exists($documento, 'recalcularSaldoPendiente')) {
            $documento->recalcularSaldoPendiente();
        }

        // 🔹 Determinar nuevo estado
        if ($documento->pagos()->count() === 0) {
            $nuevoEstado = now()->gt(\Carbon\Carbon::parse($documento->fecha_vencimiento))
                ? 'Vencido'
                : 'Al día';

            $campoEstado = $tipoDocumento === 'compra' ? 'estado' : 'status';
            $documento->update([
                $campoEstado => null,
                'status_original' => $nuevoEstado,
                'fecha_estado_manual' => null,
            ]);
        } else {
            $nuevoEstado = 'Pago';
        }

        // 🔹 Registrar movimiento
        if ($tipoDocumento === 'financiero') {
            \App\Models\MovimientoDocumento::create([
                'documento_financiero_id' => $documento->id,
                'user_id' => Auth::id(),
                'tipo_movimiento' => 'Eliminación de pago',
                'descripcion' => "Se eliminó un pago registrado el {$datosAnteriores['fecha_pago']} correspondiente al documento folio {$documento->folio}.",
                'datos_anteriores' => $datosAnteriores,
                'datos_nuevos' => [
                    'nuevo_estado' => $nuevoEstado,
                    'saldo_actual' => $documento->saldo_pendiente,
                ],
            ]);
        } else {
            \App\Models\MovimientoCompra::create([
                'documento_compra_id' => $documento->id,
                'usuario_id' => Auth::id(),
                'estado_anterior' => $estadoAnterior,
                'nuevo_estado' => $nuevoEstado,
                'fecha_cambio' => now(),
                'tipo_movimiento' => 'Eliminación de pago',
                'descripcion' => "Se eliminó un pago registrado el {$datosAnteriores['fecha_pago']} correspondiente al documento de compra folio {$documento->folio}.",
                'datos_anteriores' => array_merge($datosAnteriores, [
                    'estado_anterior' => $estadoAnterior,
                    'saldo_anterior' => $saldoAnterior,
                ]),
                'datos_nuevos' => [
                    'nuevo_estado' => $nuevoEstado,
                    'saldo_actual' => $documento->saldo_pendiente,
                ],
            ]);
        }

        // 🔹 Redirección
        $route = $tipoDocumento === 'compra' ? 'finanzas_compras.show' : 'documentos.detalles';
        return redirect()
            ->route($route, $documento->id)
            ->with('success', 'Pago eliminado, movimiento registrado y estado actualizado correctamente.');
    }











    public function storeMasivo(Request $request)
    {
        Log::info('[PAGOS_MASIVOS] storeMasivo iniciado', [
            'user_id'   => Auth::id(),
            'session_id'=> session()->getId(),
            'payload'   => $request->all(),
        ]);

        $request->validate([
            'fecha_pago'      => 'required|date|before_or_equal:today',
            'documentos'      => 'required|array|min:1',
            'documentos.*'    => 'integer|exists:documentos_compras,id',

            'operaciones'     => 'required|array|min:1',
            'operaciones.*'   => 'required|in:pago,abono',

            'montos'          => 'nullable|array',
            'montos.*'        => 'integer|min:1',
        ]);

        $ids       = $request->documentos;
        $fechaPago = $request->fecha_pago;
        $montos    = $request->montos ?? [];

        $procesados = 0;
        $duplicados = 0;
        $errores    = [];

        // Aquí se guardan PAGOS y ABONOS para export
        $operacionesExport = [];

        foreach ($ids as $id) {

            Log::info('[PAGOS_MASIVOS] Procesando documento', [
                'documento_id' => $id,
            ]);

            $documento = \App\Models\DocumentoCompra::find($id);

            if (!$documento) {
                Log::warning('[PAGOS_MASIVOS] Documento no encontrado', [
                    'documento_id' => $id,
                ]);
                $duplicados++;
                continue;
            }

            if ($documento->pagos()->exists()) {
                Log::warning('[PAGOS_MASIVOS] Documento con pago previo, omitido', [
                    'documento_id' => $id,
                ]);
                $duplicados++;
                continue;
            }

            $operacion = $request->operaciones[$id] ?? null;

            if (!$operacion) {
                Log::warning('[PAGOS_MASIVOS] Operación no definida para documento', [
                    'documento_id' => $id,
                ]);
                $errores[] = [
                    'documento_id' => $id,
                    'error'        => 'Operación no definida',
                ];
                continue;
            }

            $estadoAnterior = $documento->estado;
            $saldoAnterior  = $documento->saldo_pendiente;

            Log::info('[PAGOS_MASIVOS] Estado inicial documento', [
                'documento_id'   => $id,
                'estado'         => $estadoAnterior,
                'saldo_anterior' => $saldoAnterior,
                'operacion'      => $operacion,
            ]);

            /**
             * =====================================================
             * PAGO TOTAL
             * =====================================================
             */
            if ($operacion === 'pago') {

                $documento->pagos()->create([
                    'fecha_pago' => $fechaPago,
                    'user_id'    => Auth::id(),
                ]);

                $documento->update([
                    'estado'              => 'Pago',
                    'fecha_estado_manual' => now(),
                    'saldo_pendiente'     => 0,
                ]);

                \App\Models\MovimientoCompra::create([
                    'documento_compra_id' => $documento->id,
                    'usuario_id'          => Auth::id(),
                    'estado_anterior'     => $estadoAnterior,
                    'nuevo_estado'        => 'Pago',
                    'fecha_cambio'        => now(),
                    'tipo_movimiento'     => 'Pago registrado (masivo)',
                    'descripcion'         => "Pago masivo registrado el {$fechaPago}.",
                    'datos_anteriores'    => [
                        'estado'         => $estadoAnterior,
                        'saldo_anterior' => $saldoAnterior,
                    ],
                    'datos_nuevos' => [
                        'fecha_pago'   => $fechaPago,
                        'saldo_actual' => 0,
                    ],
                ]);

                $operacionesExport[] = [
                    'documento_id' => $documento->id,
                    'tipo'         => 'pago',
                    'monto'        => $documento->monto_total,
                    'fecha'        => $fechaPago,
                ];

                Log::info('[PAGOS_MASIVOS] Pago masivo procesado', [
                    'documento_id' => $documento->id,
                    'monto'        => $documento->monto_total,
                ]);

                $procesados++;
                continue;
            }

            /**
             * =====================================================
             * ABONO MASIVO
             * =====================================================
             */
            if ($operacion === 'abono') {

                $monto = (int) ($montos[$id] ?? 0);

                if ($monto <= 0 || $monto > $saldoAnterior) {
                    Log::warning('[PAGOS_MASIVOS] Monto de abono inválido', [
                        'documento_id' => $id,
                        'monto'        => $monto,
                        'saldo'        => $saldoAnterior,
                    ]);

                    $errores[] = [
                        'documento_id' => $id,
                        'error'        => 'Monto de abono inválido',
                    ];
                    continue;
                }

                $documento->abonos()->create([
                    'monto'       => $monto,
                    'fecha_abono' => $fechaPago,
                ]);

                $documento->recalcularSaldoPendiente();

                $documento->update([
                    'estado'              => 'Abono',
                    'fecha_estado_manual' => now(),
                ]);

                \App\Models\MovimientoCompra::create([
                    'documento_compra_id' => $documento->id,
                    'usuario_id'          => Auth::id(),
                    'estado_anterior'     => $estadoAnterior,
                    'nuevo_estado'        => 'Abono',
                    'fecha_cambio'        => now(),
                    'tipo_movimiento'     => 'Registro de abono (masivo)',
                    'descripcion'         => "Abono masivo de {$monto} registrado el {$fechaPago}.",
                    'datos_anteriores'    => [
                        'estado'         => $estadoAnterior,
                        'saldo_anterior' => $saldoAnterior,
                    ],
                    'datos_nuevos' => [
                        'monto'       => $monto,
                        'nuevo_saldo' => $documento->saldo_pendiente,
                    ],
                ]);

                $operacionesExport[] = [
                    'documento_id' => $documento->id,
                    'tipo'         => 'abono',
                    'monto'        => $monto,
                    'fecha'        => $fechaPago,
                ];

                Log::info('[PAGOS_MASIVOS] Abono masivo procesado', [
                    'documento_id' => $documento->id,
                    'monto'        => $monto,
                    'nuevo_saldo'  => $documento->saldo_pendiente,
                ]);

                $procesados++;
            }
        }

        // Guardar en sesión para export
        Log::info('[PAGOS_MASIVOS] Guardando operaciones para export en sesión', [
            'cantidad'   => count($operacionesExport),
            'session_id' => session()->getId(),
        ]);

        session([
            'pagos_masivos_export' => $operacionesExport
        ]);

        Log::info('[PAGOS_MASIVOS] Operaciones guardadas en sesión', [
            'session_data' => session('pagos_masivos_export'),
            'session_id'   => session()->getId(),
        ]);

        return response()->json([
            'ok'          => true,
            'procesados'  => $procesados,
            'duplicados'  => $duplicados,
            'errores'     => $errores,
        ]);
    }






    public function exportPagosMasivos()
    {
        Log::info('[PAGOS_MASIVOS] exportPagosMasivos iniciado', [
            'user_id'    => Auth::id(),
            'session_id' => session()->getId(),
            'session_all'=> session()->all(),
        ]);

        $ids = session('pagos_masivos_export');

        Log::info('[PAGOS_MASIVOS] Datos leídos desde sesión', [
            'session_id'   => session()->getId(),
            'export_data'  => $ids,
            'is_array'     => is_array($ids),
            'count'        => is_array($ids) ? count($ids) : null,
        ]);

        if (!$ids || !is_array($ids) || count($ids) === 0) {

            Log::warning('[PAGOS_MASIVOS] Export abortado: sesión vacía o inválida', [
                'session_id'  => session()->getId(),
                'session_all' => session()->all(),
            ]);

            abort(404, 'No hay pagos masivos para exportar.');
        }

        Log::info('[PAGOS_MASIVOS] Export válido, preparando descarga', [
            'cantidad'   => count($ids),
            'session_id' => session()->getId(),
        ]);

        // Limpiar sesión para evitar descargas duplicadas
        session()->forget('pagos_masivos_export');

        Log::info('[PAGOS_MASIVOS] Sesión limpiada después de export', [
            'session_id' => session()->getId(),
        ]);

        $nombreArchivo = 'pagos_masivos_' . now()->format('Ymd_His') . '.xlsx';

        Log::info('[PAGOS_MASIVOS] Descarga Excel iniciada', [
            'archivo'    => $nombreArchivo,
            'session_id' => session()->getId(),
        ]);

        return Excel::download(
            new \App\Exports\PagosMasivosDocumentoCompraExport($ids),
            $nombreArchivo
        );
    }










    public function buscarDocumentos(Request $request)
    {
        $filtro = trim($request->get('filtro'));

        $documentos = DocumentoCompra::where(function ($query) use ($filtro) {

                if (ctype_digit($filtro)) {
                    // 🔹 Búsqueda EXACTA por folio
                    $query->where('folio', $filtro);
                } else {
                    // 🔹 Búsqueda por texto
                    $query->where('razon_social', 'like', "%{$filtro}%")
                        ->orWhere('rut_proveedor', 'like', "%{$filtro}%");
                }

            })
            ->where('saldo_pendiente', '>', 0)     // SOLO PENDIENTES
            ->whereDoesntHave('pagos')             // SIN PAGO REGISTRADO
            ->orderBy('fecha_vencimiento', 'asc')
            ->get();

        return response()->json($documentos);
    }

















}
