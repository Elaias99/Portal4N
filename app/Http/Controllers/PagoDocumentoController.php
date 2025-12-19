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
        Log::info('🧪 PAGOS MASIVOS RECIBIDOS', [
            'fecha_pago' => $request->fecha_pago,
            'documentos' => $request->documentos,
        ]);

        $request->validate([
            'fecha_pago' => 'required|date|before_or_equal:today',
            'documentos' => 'required|array|min:1',
            'documentos.*' => 'integer|exists:documentos_compras,id',
        ], [
            'fecha_pago.before_or_equal' => 'La fecha del pago no debe sobrepasar la fecha actual.',
            'fecha_pago.required' => 'La fecha del pago es obligatoria.',
            'documentos.required' => 'Debes seleccionar al menos un documento.',
        ]);

        $ids = $request->input('documentos');
        $fechaPago = $request->input('fecha_pago');

        $procesados = 0;
        $duplicados = 0;

        foreach ($ids as $id) {
            $documento = \App\Models\DocumentoCompra::find($id);

            if (!$documento || $documento->pagos()->exists()) {
                $duplicados++;
                continue;
            }

            $estadoAnterior = $documento->estado;
            $saldoAnterior  = $documento->saldo_pendiente;

            // ✅ Crear pago
            $documento->pagos()->create([
                'fecha_pago' => $fechaPago,
                'user_id' => Auth::id(),
            ]);

            // ✅ Actualizar documento
            $documento->update([
                'estado' => 'Pago',
                'fecha_estado_manual' => now(),
                'saldo_pendiente' => 0,
            ]);

            // ✅ Movimiento
            \App\Models\MovimientoCompra::create([
                'documento_compra_id' => $documento->id,
                'usuario_id' => Auth::id(),
                'estado_anterior' => $estadoAnterior,
                'nuevo_estado' => 'Pago',
                'fecha_cambio' => now(),
                'tipo_movimiento' => 'Pago registrado (masivo)',
                'descripcion' => "Pago masivo registrado el {$fechaPago}.",
                'datos_anteriores' => [
                    'estado' => $estadoAnterior,
                    'saldo_anterior' => $saldoAnterior,
                ],
                'datos_nuevos' => [
                    'fecha_pago' => $fechaPago,
                    'saldo_actual' => 0,
                ],
            ]);

            $procesados++;
        }

        Log::info('✅ PAGOS MASIVOS PROCESADOS', [
            'procesados' => $procesados,
            'duplicados' => $duplicados,
        ]);

        // 📤 EXPORTAR EXCEL AUTOMÁTICAMENTE
        $nombreArchivo = 'pagos_masivos_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new PagosMasivosDocumentoCompraExport($ids),
            $nombreArchivo
        );
    }







    public function buscarDocumentos(Request $request)
    {
        $filtro = $request->get('filtro');

        $documentos = DocumentoCompra::where(function ($query) use ($filtro) {
                $query->where('folio', 'like', "%{$filtro}%")
                    ->orWhere('razon_social', 'like', "%{$filtro}%")
                    ->orWhere('rut_proveedor', 'like', "%{$filtro}%");
            })
            ->where('saldo_pendiente', '>', 0)     // 🔴 SOLO PENDIENTES
            ->whereDoesntHave('pagos')             // 🔴 SIN PAGO REGISTRADO
            ->orderBy('fecha_vencimiento', 'asc')
            ->get();

        // \Log::info('🧪 BUSQUEDA PAGOS MASIVOS', [
        //     'filtro' => $filtro,
        //     'resultados' => $documentos->count(),
        // ]);

        return response()->json($documentos);
    }
















}
