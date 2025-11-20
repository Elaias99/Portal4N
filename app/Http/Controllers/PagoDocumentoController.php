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

        Log::info('🟢 Iniciando registro de pago', [
            'id' => $id,
            'route_name' => $request->route()->getName(),
            'previous_url' => url()->previous(),
        ]);


        // 🔍 Detectar tipo de documento
        $tipo = $request->input('tipo', 'ventas'); // Por defecto: ventas
        $esCompra = $tipo === 'compra';

        if ($esCompra) {
            $documento = \App\Models\DocumentoCompra::findOrFail($id);
        } else {
            $documento = \App\Models\DocumentoFinanciero::findOrFail($id);
        }


        // 🚫 Evitar pagos duplicados
        if ($documento->pagos()->exists()) {
            return back()->withErrors(['fecha_pago' => 'Este documento ya tiene un pago registrado.']);
        }

        // ✅ Crear el pago
        $documento->pagos()->create([
            'fecha_pago' => $request->fecha_pago,
            'user_id' => Auth::id(),
        ]);

        // ✅ Actualizar estado y saldo
        // ✅ Actualizar estado y saldo
        if ($tipo === 'ventas') {
            $documento->update([
                'status' => 'Pago',
                'fecha_estado_manual' => now(),
                'saldo_pendiente' => 0,
            ]);
        } else {
            $documento->update([
                'estado' => 'Pago',
                'fecha_estado_manual' => now(),
                'saldo_pendiente' => 0,
            ]);
        }



        Log::info('🧩 Tipo de documento detectado', [
            'esCompra' => $esCompra,
            'tipo' => $tipo ?? 'sin definir',
            'modelo' => get_class($documento),
        ]);


        // ✅ Registrar movimiento
        if ($tipo === 'ventas') {
            MovimientoDocumento::create([
                'documento_financiero_id' => $documento->id,
                'user_id' => Auth::id(),
                'tipo_movimiento' => 'Pago registrado',
                'descripcion' => "Se registró un pago el {$request->fecha_pago}.",
                'datos_nuevos' => ['fecha_pago' => $request->fecha_pago],
            ]);
        } else {
            MovimientoCompra::create([
                'documento_compra_id' => $documento->id,
                'usuario_id' => Auth::id(),
                'estado_anterior' => $documento->estado ?? 'Pendiente',
                'nuevo_estado' => 'Pago',
                'fecha_cambio' => now(),
            ]);
        }

        Log::info('✅ Pago registrado correctamente', [
            'documento_id' => $documento->id,
            'tipo' => $tipo,
            'saldo_pendiente' => $documento->saldo_pendiente ?? null,
        ]);


        return back()->with('success', 'Pago registrado correctamente y estado actualizado.');
    }




    /**
     * Eliminar un pago (revertir estado de pago).
     */
    public function destroy($id)
    {
        $pago = \App\Models\Pago::findOrFail($id);
        $documento = $pago->documentoFinanciero ?? $pago->documentoCompra;

        // 🧩 Detectar tipo de documento
        $tipoDocumento = $documento instanceof \App\Models\DocumentoCompra ? 'compra' : 'financiero';

        // 🔹 Eliminar el pago
        $pago->delete();

        // 🔹 IMPORTANTE → Recalcular saldo pendiente
        if (method_exists($documento, 'recalcularSaldoPendiente')) {
            $documento->recalcularSaldoPendiente();
        }

        // 🔹 Verificar si quedan más pagos
        if ($documento->pagos()->count() === 0) {

            // Recalcular nuevo estado automático
            $nuevoEstado = now()->gt(\Carbon\Carbon::parse($documento->fecha_vencimiento))
                ? 'Vencido'
                : 'Al día';

            // 🔹 Actualizar documento según módulo
            if ($tipoDocumento === 'compra') {
                $documento->update([
                    'estado' => null, 
                    'status_original' => $nuevoEstado,
                    'fecha_estado_manual' => null,
                ]);
            } else {
                $documento->update([
                    'status' => null,
                    'status_original' => $nuevoEstado,
                    'fecha_estado_manual' => null,
                ]);
            }
        }

        // 🔹 Redirección
        if ($tipoDocumento === 'compra') {
            return redirect()
                ->route('finanzas_compras.show', $documento->id)
                ->with('success', 'Pago eliminado y estado actualizado correctamente.');
        }

        return redirect()
            ->route('documentos.detalles', $documento->id)
            ->with('success', 'Pago eliminado y estado actualizado correctamente.');
    }








    public function storeMasivo(Request $request)
    {
        $request->validate([
            'fecha_pago' => 'required|date|before_or_equal:today',
            'documentos' => 'required|array|min:1',
            'documentos.*' => 'integer|exists:documentos_financieros,id',
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
            $documento = DocumentoFinanciero::find($id);

            // 🚫 Saltar si ya tiene un pago registrado
            if ($documento->pagos()->exists()) {
                $duplicados++;
                continue;
            }

            // ✅ Crear el pago
            $documento->pagos()->create([
                'fecha_pago' => $fechaPago,
                'user_id' => Auth::id(),
            ]);

            // ✅ Actualizar estado y saldo
            $documento->update([
                'status' => 'Pago',
                'fecha_estado_manual' => now(),
                'saldo_pendiente' => 0,
            ]);

            // ✅ Registrar movimiento
            MovimientoDocumento::create([
                'documento_financiero_id' => $documento->id,
                'user_id' => Auth::id(),
                'tipo_movimiento' => 'Pago registrado (masivo)',
                'descripcion' => "Pago masivo registrado el {$fechaPago}.",
                'datos_nuevos' => ['fecha_pago' => $fechaPago],
            ]);

            $procesados++;
        }

        $mensaje = "Pagos registrados correctamente: {$procesados}.";
        if ($duplicados > 0) {
            $mensaje .= " {$duplicados} documentos ya tenían pago registrado y fueron omitidos.";
        }

        return back()->with('success', $mensaje);
    }




    public function buscarDocumentos(Request $request)
    {
        $filtro = $request->get('filtro');

        $documentos = DocumentoFinanciero::where(function ($query) use ($filtro) {
                $query->where('folio', 'like', "%{$filtro}%")
                    ->orWhere('razon_social', 'like', "%{$filtro}%");
            })
            ->get(); // 👈 sin limit()

        return response()->json($documentos);
    }














}
