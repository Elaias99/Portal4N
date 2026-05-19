<?php

namespace App\Http\Controllers;

use App\Models\Factory as FactoryRegistro;
use App\Models\DocumentoFinanciero;
use App\Models\MovimientoDocumento;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Banco;
use Illuminate\Validation\ValidationException;

class FactoryController extends Controller
{
    /**
     * Registrar estado Factory para un documento financiero CxC.
     */
    public function store(Request $request, DocumentoFinanciero $documento)
    {
        $validated = $request->validate([
            'banco_id' => 'required|string|max:255',
            'banco_otro' => 'nullable|string|max:255',
            'rut_factory' => 'required|string|max:20',
        ], [
            'banco_id.required' => 'Debe seleccionar el banco o entidad Factory.',
            'rut_factory.required' => 'Debe ingresar el RUT del Factory.',
            'rut_factory.max' => 'El RUT del Factory no puede superar los 20 caracteres.',
        ]);

        if ((int) $documento->tipo_documento_id === 61) {
            return back()
                ->withErrors(['factory' => 'No se puede registrar Factory sobre una nota de crédito.'])
                ->withInput();
        }

        if ($documento->factoryRegistro()->exists()) {
            return back()
                ->withErrors(['factory' => 'Este documento ya tiene un registro Factory asociado.'])
                ->withInput();
        }

        if ($documento->pagos()->exists()) {
            return back()
                ->withErrors(['factory' => 'No se puede registrar Factory porque el documento ya tiene un pago registrado.'])
                ->withInput();
        }

        if ($documento->prontoPagos()->exists()) {
            return back()
                ->withErrors(['factory' => 'No se puede registrar Factory porque el documento ya tiene un pronto pago registrado.'])
                ->withInput();
        }

        $saldoAnterior = (int) $documento->saldo_pendiente;

        if ($saldoAnterior <= 0) {
            return back()
                ->withErrors(['factory' => 'No se puede registrar Factory porque el documento no tiene saldo pendiente.'])
                ->withInput();
        }

        /*
        |--------------------------------------------------------------------------
        | Resolver banco / Factory
        |--------------------------------------------------------------------------
        | Si viene "__otro__", se crea el banco si no existe.
        | Si viene un ID normal, se valida contra la tabla bancos.
        */
        $bancoId = $validated['banco_id'];
        $bancoOtro = trim((string) ($validated['banco_otro'] ?? ''));

        if ($bancoId === '__otro__') {
            if ($bancoOtro === '') {
                throw ValidationException::withMessages([
                    'banco_otro' => 'Debe ingresar el nombre del banco o Factory.',
                ]);
            }

            $nombreBanco = preg_replace('/\s+/', ' ', $bancoOtro);

            $bancoSeleccionado = Banco::whereRaw('LOWER(TRIM(nombre)) = ?', [
                mb_strtolower($nombreBanco),
            ])->first();

            if (!$bancoSeleccionado) {
                $bancoSeleccionado = Banco::create([
                    'nombre' => $nombreBanco,
                ]);
            }
        } else {
            $bancoSeleccionado = Banco::find($bancoId);

            if (!$bancoSeleccionado) {
                throw ValidationException::withMessages([
                    'banco_id' => 'El banco seleccionado no es válido.',
                ]);
            }
        }

        $estadoAnterior = $documento->status;
        $statusOriginalAnterior = $documento->status_original;

        try {
            DB::transaction(function () use (
                $validated,
                $documento,
                $saldoAnterior,
                $estadoAnterior,
                $statusOriginalAnterior,
                $bancoSeleccionado
            ) {
                $factory = FactoryRegistro::create([
                    'documento_financiero_id' => $documento->id,
                    'banco_id' => $bancoSeleccionado->id,
                    'rut_factory' => trim($validated['rut_factory']),
                    'fecha_factory' => Carbon::today()->toDateString(),
                    'monto' => $saldoAnterior,
                    'user_id' => Auth::id(),
                ]);

                $documento->update([
                    'status' => 'Factory',
                    'fecha_estado_manual' => now(),
                    'saldo_pendiente' => 0,
                ]);

                MovimientoDocumento::create([
                    'documento_financiero_id' => $documento->id,
                    'user_id' => Auth::id(),
                    'tipo_movimiento' => 'Registro de Factory',
                    'descripcion' => "El documento folio {$documento->folio} fue marcado como Factory por un monto de {$saldoAnterior}.",
                    'datos_anteriores' => [
                        'estado' => $estadoAnterior,
                        'status_original' => $statusOriginalAnterior,
                        'saldo_anterior' => $saldoAnterior,
                    ],
                    'datos_nuevos' => [
                        'factory_id' => $factory->id,
                        'banco_id' => $factory->banco_id,
                        'banco' => $bancoSeleccionado->nombre,
                        'rut_factory' => $factory->rut_factory,
                        'fecha_factory' => $factory->fecha_factory,
                        'monto' => $factory->monto,
                        'nuevo_estado' => 'Factory',
                        'saldo_actual' => 0,
                    ],
                ]);
            });
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withErrors(['factory' => 'Ocurrió un error al registrar Factory.'])
                ->withInput();
        }

        return back()->with('success', 'Factory registrado correctamente y saldo pendiente actualizado a 0.');
    }

    /**
     * Eliminar un registro Factory y restaurar saldo/estado según movimientos restantes.
     */
    public function destroy(FactoryRegistro $factory)
    {
        $factory->load([
            'documentoFinanciero.abonos',
            'documentoFinanciero.cruces',
            'documentoFinanciero.pagos',
            'documentoFinanciero.prontoPagos',
            'banco',
        ]);

        $documento = $factory->documentoFinanciero;

        if (!$documento) {
            return back()->withErrors([
                'factory' => 'No se encontró el documento financiero asociado al Factory.',
            ]);
        }

        $datosAnteriores = [
            'factory_id' => $factory->id,
            'documento_financiero_id' => $factory->documento_financiero_id,
            'banco_id' => $factory->banco_id,
            'banco' => $factory->banco?->nombre,
            'rut_factory' => $factory->rut_factory,
            'fecha_factory' => $factory->fecha_factory,
            'monto' => $factory->monto,
        ];

        $estadoAnterior = $documento->status;
        $statusOriginalAnterior = $documento->status_original;
        $saldoAnterior = $documento->saldo_pendiente;

        try {
            DB::transaction(function () use (
                $factory,
                $documento,
                $datosAnteriores,
                $estadoAnterior,
                $statusOriginalAnterior,
                $saldoAnterior
            ) {
                $factory->delete();

                /*
                 * Liberar saldo persistido para que el modelo recalcule desde:
                 * monto_total, notas, abonos y cruces restantes.
                 */
                $documento->update([
                    'saldo_pendiente' => null,
                ]);

                if (method_exists($documento, 'recalcularSaldoPendiente')) {
                    $documento->recalcularSaldoPendiente();
                }

                $documento->refresh();

                $nuevoEstadoManual = $this->resolverEstadoManualDespuesDeEliminarFactory($documento);
                $nuevoStatusOriginal = $this->resolverStatusOriginal($documento);

                $documento->update([
                    'status' => $nuevoEstadoManual,
                    'status_original' => $nuevoStatusOriginal,
                    'fecha_estado_manual' => $nuevoEstadoManual ? now() : null,
                ]);

                $documento->refresh();

                MovimientoDocumento::create([
                    'documento_financiero_id' => $documento->id,
                    'user_id' => Auth::id(),
                    'tipo_movimiento' => 'Eliminación de Factory',
                    'descripcion' => "Se eliminó el Factory del documento folio {$documento->folio}.",
                    'datos_anteriores' => array_merge($datosAnteriores, [
                        'estado_anterior' => $estadoAnterior,
                        'status_original_anterior' => $statusOriginalAnterior,
                        'saldo_anterior' => $saldoAnterior,
                    ]),
                    'datos_nuevos' => [
                        'nuevo_estado_manual' => $nuevoEstadoManual,
                        'nuevo_status_original' => $nuevoStatusOriginal,
                        'saldo_actual' => $documento->saldo_pendiente,
                    ],
                ]);
            });
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withErrors(['factory' => 'Ocurrió un error al eliminar Factory.'])
                ->withInput();
        }

        return back()->with('success', 'Factory eliminado, saldo recalculado y estado actualizado correctamente.');
    }

    /**
     * Define qué estado manual debe quedar después de eliminar Factory.
     */
    private function resolverEstadoManualDespuesDeEliminarFactory(DocumentoFinanciero $documento): ?string
    {
        if ($documento->pagos()->exists()) {
            return 'Pago';
        }

        if ($documento->prontoPagos()->exists()) {
            return 'Pronto pago';
        }

        if ($documento->cruces()->sum('monto') > 0) {
            return 'Cruce';
        }

        if ($documento->abonos()->sum('monto') > 0) {
            return 'Abono';
        }

        return null;
    }

    /**
     * Mantiene status_original separado del estado manual.
     */
    private function resolverStatusOriginal(DocumentoFinanciero $documento): string
    {
        if (!$documento->fecha_vencimiento) {
            return 'Sin cálculo';
        }

        return now()->gt(Carbon::parse($documento->fecha_vencimiento))
            ? 'Vencido'
            : 'Al día';
    }
}