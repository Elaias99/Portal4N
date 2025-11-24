<?php

namespace App\Http\Controllers;

use App\Models\Cruce;
use App\Models\DocumentoFinanciero;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $cruce = Cruce::findOrFail($id);

        $request->validate([
            'monto' => 'required|integer|min:1',
            'fecha_cruce' => 'required|date|before_or_equal:today',
        ], [
            'fecha_cruce.before_or_equal' => 'La fecha del cruce no debe sobrepasar la fecha actual.',
            'fecha_cruce.required' => 'La fecha del cruce es obligatoria.',
        ]);

        $cruce->update([
            'monto' => $request->monto,
            'fecha_cruce' => $request->fecha_cruce,
        ]);

        return redirect()
            ->route('cruces.index', $cruce->documento_financiero_id)
            ->with('success', 'Cruce actualizado correctamente.');
    }

    /**
     * Eliminar un cruce.
     */
    // public function destroy($id)
    // {
    //     $cruce = \App\Models\Cruce::findOrFail($id);
    //     $documento = $cruce->documento ?? $cruce->documentoCompra;

    //     // 🧩 Detectar tipo de documento
    //     $tipoDocumento = $documento instanceof \App\Models\DocumentoCompra ? 'compra' : 'financiero';

    //     // 🔹 Eliminar el cruce
    //     $cruce->delete();


    //      // 🔹 Recalcular saldo pendiente en la BD
    //     if (method_exists($documento, 'recalcularSaldoPendiente')) {
    //         $documento->recalcularSaldoPendiente(); 
    //     }

    //     // 🔹 Recalcular totales
    //     $totalCruces = $documento->cruces()->sum('monto');
    //     $totalAbonos = $documento->abonos()->sum('monto');

    //     // 🔹 Determinar nuevo estado
    //     if ($totalCruces > 0) {
    //         $nuevoEstado = 'Cruce';
    //     } elseif ($totalAbonos > 0) {
    //         $nuevoEstado = 'Abono';
    //     } else {
    //         $nuevoEstado = now()->gt(\Carbon\Carbon::parse($documento->fecha_vencimiento))
    //             ? 'Vencido'
    //             : 'Al día';
    //     }

    //     // 🔹 Actualizar el documento según tipo
    //     if ($tipoDocumento === 'compra') {
    //         $documento->update([
    //             'estado' => in_array($nuevoEstado, ['Vencido', 'Al día']) ? null : $nuevoEstado,
    //             'status_original' => $nuevoEstado,
    //             'fecha_estado_manual' => in_array($nuevoEstado, ['Vencido', 'Al día']) ? null : now(),
    //         ]);
    //     } else {
    //         $documento->update([
    //             'status' => in_array($nuevoEstado, ['Vencido', 'Al día']) ? null : $nuevoEstado,
    //             'status_original' => $nuevoEstado,
    //             'fecha_estado_manual' => in_array($nuevoEstado, ['Vencido', 'Al día']) ? null : now(),
    //         ]);
    //     }

    //     // 🔹 Redirección inteligente
    //     if ($tipoDocumento === 'compra') {
    //         return redirect()
    //             ->route('finanzas_compras.show', $documento->id)
    //             ->with('success', 'Cruce eliminado y estado actualizado correctamente.');
    //     }

    //     return redirect()
    //         ->route('documentos.detalles', $documento->id)
    //         ->with('success', 'Cruce eliminado y estado actualizado correctamente.');
    // }

    public function destroy($id)
    {
        $cruce = \App\Models\Cruce::findOrFail($id);
        $documento = $cruce->documento ?? $cruce->documentoCompra;

        // 🧩 Detectar tipo de documento
        $tipoDocumento = $documento instanceof \App\Models\DocumentoCompra ? 'compra' : 'financiero';

        // 📝 Guardar datos antes de eliminar
        $datosAnteriores = [
            'monto' => $cruce->monto,
            'fecha_cruce' => $cruce->fecha_cruce,
            'proveedor_id' => $cruce->proveedor_id,
        ];

        // 🔹 Eliminar el cruce
        $cruce->delete();

        // 🔹 Recalcular saldo pendiente
        if (method_exists($documento, 'recalcularSaldoPendiente')) {
            $documento->recalcularSaldoPendiente(); 
        }

        // 🔹 Recalcular totales
        $totalCruces = $documento->cruces()->sum('monto');
        $totalAbonos = $documento->abonos()->sum('monto');

        // 🔹 Determinar nuevo estado
        if ($totalCruces > 0) {
            $nuevoEstado = 'Cruce';
        } elseif ($totalAbonos > 0) {
            $nuevoEstado = 'Abono';
        } else {
            $nuevoEstado = now()->gt(\Carbon\Carbon::parse($documento->fecha_vencimiento))
                ? 'Vencido'
                : 'Al día';
        }

        // 🔹 Actualizar documento según tipo
        if ($tipoDocumento === 'compra') {
            $documento->update([
                'estado' => in_array($nuevoEstado, ['Vencido', 'Al día']) ? null : $nuevoEstado,
                'status_original' => $nuevoEstado,
                'fecha_estado_manual' => in_array($nuevoEstado, ['Vencido', 'Al día']) ? null : now(),
            ]);
        } else {
            $documento->update([
                'status' => in_array($nuevoEstado, ['Vencido', 'Al día']) ? null : $nuevoEstado,
                'status_original' => $nuevoEstado,
                'fecha_estado_manual' => in_array($nuevoEstado, ['Vencido', 'Al día']) ? null : now(),
            ]);
        }

        // 🔹 Registrar movimiento según tipo de documento
        if ($tipoDocumento === 'financiero') {
            \App\Models\MovimientoDocumento::create([
                'documento_financiero_id' => $documento->id,
                'user_id' => Auth::id(),
                'tipo_movimiento' => 'Eliminación de cruce',
                'descripcion' => "Se eliminó un cruce de {$datosAnteriores['monto']} correspondiente al documento folio {$documento->folio}.",
                'datos_anteriores' => $datosAnteriores,
            ]);
        } elseif ($tipoDocumento === 'compra') {
            \App\Models\MovimientoCompra::create([
                'documento_compra_id' => $documento->id,
                'usuario_id' => Auth::id(),
                'tipo_movimiento' => 'Eliminación de cruce',
                'descripcion' => "Se eliminó un cruce de {$datosAnteriores['monto']} correspondiente al documento de compra folio {$documento->folio}.",
                'datos_anteriores' => $datosAnteriores,
                'fecha_cambio' => now(),
            ]);
        }

        // 🔹 Redirección inteligente
        if ($tipoDocumento === 'compra') {
            return redirect()
                ->route('finanzas_compras.show', $documento->id)
                ->with('success', 'Cruce eliminado, movimiento registrado y estado actualizado correctamente.');
        }

        return redirect()
            ->route('documentos.detalles', $documento->id)
            ->with('success', 'Cruce eliminado, movimiento registrado y estado actualizado correctamente.');
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
