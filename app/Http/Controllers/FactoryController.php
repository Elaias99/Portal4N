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
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class FactoryController extends Controller
{
    /**
     * Registrar estado Factoring para un documento financiero CxC.
     */
    public function store(Request $request, DocumentoFinanciero $documento)
    {

        Log::info('FACTORY STORE INDIVIDUAL - ENTRÓ AL MÉTODO', [
            'documento_id' => $documento->id,
            'folio' => $documento->folio,
            'status_actual' => $documento->status,
            'saldo_pendiente_actual' => $documento->saldo_pendiente,
            'request_all' => $request->all(),
        ]);



        $validated = $request->validate([
            'banco_id' => 'required|string|max:255',
            'banco_otro' => 'nullable|string|max:255',
            'rut_factory' => 'required|string|max:20',
            'cesion' => 'required|string|max:100',
        ], [
            'banco_id.required' => 'Debe seleccionar el banco o entidad Factoring.',
            'rut_factory.required' => 'Debe ingresar el RUT del Factoring.',
            'rut_factory.max' => 'El RUT del Factoring no puede superar los 20 caracteres.',
            'cesion.required' => 'Debe ingresar la cesión del Factoring.',
        ]);

        if ((int) $documento->tipo_documento_id === 61) {
            return back()
                ->withErrors(['factory' => 'No se puede registrar Factoring sobre una nota de crédito.'])
                ->withInput();
        }

        Log::info('FACTORY STORE INDIVIDUAL - VALIDACIÓN OK', [
            'documento_id' => $documento->id,
            'validated' => $validated,
        ]);

        if ((int) $documento->tipo_documento_id === 56) {
            return back()
                ->withErrors(['factory' => 'No se puede registrar Factoring sobre una nota de débito.'])
                ->withInput();
        }

        if ($documento->factoryRegistro()->exists()) {
            return back()
                ->withErrors(['factory' => 'Este documento ya tiene un registro Factoring asociado.'])
                ->withInput();
        }

        if ($documento->pagos()->exists()) {
            return back()
                ->withErrors(['factory' => 'No se puede registrar Factoring porque el documento ya tiene un pago registrado.'])
                ->withInput();
        }

        if ($documento->prontoPagos()->exists()) {
            return back()
                ->withErrors(['factory' => 'No se puede registrar Factoring porque el documento ya tiene un pronto pago registrado.'])
                ->withInput();
        }

        $saldoAnterior = (int) $documento->saldo_pendiente;

        if ($saldoAnterior <= 0) {
            return back()
                ->withErrors(['factory' => 'No se puede registrar Factoring porque el documento no tiene saldo pendiente.'])
                ->withInput();
        }

        $bancoSeleccionado = $this->resolverBancoFactoryMasivo(
            bancoId: $validated['banco_id'],
            bancoOtro: $validated['banco_otro'] ?? null,
        );

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


                Log::info('FACTORY STORE INDIVIDUAL - ANTES DE CREAR FACTORING', [
                    'documento_id' => $documento->id,
                    'saldo_anterior' => $saldoAnterior,
                    'banco_id' => $bancoSeleccionado->id,
                    'banco_nombre' => $bancoSeleccionado->nombre,
                ]);








                $factory = FactoryRegistro::create([
                    'documento_financiero_id' => $documento->id,
                    'banco_id' => $bancoSeleccionado->id,
                    'rut_factory' => trim($validated['rut_factory']),
                    'cesion' => trim($validated['cesion']),
                    'fecha_factory' => Carbon::today()->toDateString(),
                    'monto' => $saldoAnterior,
                    'saldo_liquido' => 0,
                    'diferencia' => 0,
                    'user_id' => Auth::id(),
                ]);

                $documento->update([
                    'status' => 'Factory',
                    'fecha_estado_manual' => now(),
                    'saldo_pendiente' => 0,
                ]);

                $documento->refresh();

                Log::info('FACTORY STORE INDIVIDUAL - DOCUMENTO ACTUALIZADO', [
                    'documento_id' => $documento->id,
                    'status_nuevo' => $documento->status,
                    'saldo_pendiente_nuevo' => $documento->saldo_pendiente,
                    'factory_id' => $factory->id ?? null,
                ]);

                MovimientoDocumento::create([
                    'documento_financiero_id' => $documento->id,
                    'user_id' => Auth::id(),
                    'tipo_movimiento' => 'Registro de Factoring',
                    'descripcion' => "El documento folio {$documento->folio} fue marcado como Factoring. Saldo anterior: {$saldoAnterior}. Saldo actual: 0.",
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
                        'cesion' => $factory->cesion,
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
                ->withErrors(['factory' => 'Ocurrió un error al registrar Factoring.'])
                ->withInput();
        }

        return back()->with(
            'success',
            'Factoring registrado correctamente. El saldo pendiente del documento quedó en 0.'
        );
    }

    /**
     * Eliminar un registro Factoring y restaurar saldo/estado según movimientos restantes.
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
                'factory' => 'No se encontró el documento financiero asociado al Factoring.',
            ]);
        }

        $datosAnteriores = [
            'factory_id' => $factory->id,
            'documento_financiero_id' => $factory->documento_financiero_id,
            'banco_id' => $factory->banco_id,
            'banco' => $factory->banco?->nombre,
            'rut_factory' => $factory->rut_factory,
            'cesion' => $factory->cesion,
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
                    'tipo_movimiento' => 'Eliminación de Factoring',
                    'descripcion' => "Se eliminó el Factoring del documento folio {$documento->folio}.",
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
                ->withErrors(['factory' => 'Ocurrió un error al eliminar Factoring.'])
                ->withInput();
        }

        return back()->with('success', 'Factoring eliminado, saldo recalculado y estado actualizado correctamente.');
    }

    /**
     * Registrar Factoring masivo para documentos financieros CxC.
     */
    public function storeMasivo(Request $request)
    {
        $validated = $request->validate([
            'documentos' => 'required|array|min:1',

            'documentos.*.banco_id' => 'required|string|max:255',
            'documentos.*.banco_otro' => 'nullable|string|max:255',
            'documentos.*.rut_factory' => 'required|string|max:20',
            'documentos.*.fecha_factory' => 'required|date',

            'documentos.*.cesion' => 'required|string|max:100',
        ], [
            'documentos.required' => 'Debe seleccionar al menos un documento.',
            'documentos.array' => 'La selección de documentos no es válida.',
            'documentos.min' => 'Debe seleccionar al menos un documento.',

            'documentos.*.banco_id.required' => 'Debe seleccionar el banco o entidad Factoring.',
            'documentos.*.rut_factory.required' => 'Debe ingresar el RUT del Factoring.',
            'documentos.*.rut_factory.max' => 'El RUT del Factoring no puede superar los 20 caracteres.',
            'documentos.*.fecha_factory.required' => 'Debe ingresar la fecha Factoring.',
            'documentos.*.fecha_factory.date' => 'La fecha Factoring no es válida.',

            'documentos.*.cesion.required' => 'Debe ingresar la cesión del Factoring.',
        ]);

        $items = $validated['documentos'];

        $documentoIds = collect(array_keys($items))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($documentoIds->isEmpty()) {
            return back()
                ->withErrors(['factory_masivo' => 'Debe seleccionar al menos un documento válido.'])
                ->withInput();
        }

        $documentos = DocumentoFinanciero::with([
                'factoryRegistro',
                'pagos',
                'prontoPagos',
            ])
            ->whereIn('id', $documentoIds)
            ->get()
            ->keyBy('id');

        $errores = [];

        foreach ($documentoIds as $documentoId) {
            $documento = $documentos->get($documentoId);
            $data = $items[$documentoId] ?? null;

            if (!$documento) {
                $errores[] = "Documento ID {$documentoId}: no existe.";
                continue;
            }

            $identificador = "Folio {$documento->folio}";

            if ((int) $documento->tipo_documento_id === 61) {
                $errores[] = "{$identificador}: no se puede registrar Factoring sobre una nota de crédito.";
            }

            if ((int) $documento->tipo_documento_id === 56) {
                $errores[] = "{$identificador}: no se puede registrar Factoring sobre una nota de débito.";
            }

            if ((int) $documento->saldo_pendiente <= 0) {
                $errores[] = "{$identificador}: no tiene saldo pendiente.";
            }

            if ($documento->factoryRegistro) {
                $errores[] = "{$identificador}: ya tiene un registro Factoring asociado.";
            }

            if ($documento->pagos->isNotEmpty()) {
                $errores[] = "{$identificador}: ya tiene un pago registrado.";
            }

            if ($documento->prontoPagos->isNotEmpty()) {
                $errores[] = "{$identificador}: ya tiene un pronto pago registrado.";
            }

            if (!$data) {
                $errores[] = "{$identificador}: no tiene datos Factoring enviados.";
                continue;
            }

            $bancoId = $data['banco_id'] ?? null;
            $bancoOtro = trim((string) ($data['banco_otro'] ?? ''));

            if ($bancoId === '__otro__' && $bancoOtro === '') {
                $errores[] = "{$identificador}: debe ingresar el nombre del banco o Factoring.";
            }

            if ($bancoId !== '__otro__' && !Banco::whereKey($bancoId)->exists()) {
                $errores[] = "{$identificador}: el banco seleccionado no es válido.";
            }
        }

        if (!empty($errores)) {
            return back()
                ->withErrors(['factory_masivo' => $errores])
                ->withInput();
        }

        try {
            DB::transaction(function () use ($documentoIds, $items) {
                foreach ($documentoIds as $documentoId) {
                    $documento = DocumentoFinanciero::whereKey($documentoId)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if (
                        in_array((int) $documento->tipo_documento_id, [61, 56], true) ||
                        (int) $documento->saldo_pendiente <= 0 ||
                        $documento->factoryRegistro()->exists() ||
                        $documento->pagos()->exists() ||
                        $documento->prontoPagos()->exists()
                    ) {
                        throw ValidationException::withMessages([
                            'factory_masivo' => "El documento folio {$documento->folio} ya no cumple las condiciones para registrar Factoring.",
                        ]);
                    }

                    $data = $items[$documentoId];

                    $banco = $this->resolverBancoFactoryMasivo(
                        bancoId: $data['banco_id'],
                        bancoOtro: $data['banco_otro'] ?? null,
                    );

                    $saldoAnterior = (int) $documento->saldo_pendiente;

                    $estadoAnterior = $documento->status;
                    $statusOriginalAnterior = $documento->status_original;

                    $factory = FactoryRegistro::create([
                        'documento_financiero_id' => $documento->id,
                        'banco_id' => $banco->id,
                        'rut_factory' => trim($data['rut_factory']),
                        'cesion' => trim($data['cesion']),
                        'fecha_factory' => $data['fecha_factory'],
                        'monto' => $saldoAnterior,
                        'saldo_liquido' => 0,
                        'diferencia' => 0,
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
                        'tipo_movimiento' => 'Registro de Factoring masivo',
                        'descripcion' => "El documento folio {$documento->folio} fue marcado como Factoring masivo. Saldo anterior: {$saldoAnterior}. Saldo actual: 0.",
                        'datos_anteriores' => [
                            'estado' => $estadoAnterior,
                            'status_original' => $statusOriginalAnterior,
                            'saldo_anterior' => $saldoAnterior,
                        ],
                        'datos_nuevos' => [
                            'factory_id' => $factory->id,
                            'banco_id' => $factory->banco_id,
                            'banco' => $banco->nombre,
                            'rut_factory' => $factory->rut_factory,
                            'cesion' => $factory->cesion,
                            'fecha_factory' => $factory->fecha_factory,
                            'monto' => $factory->monto,
                            'nuevo_estado' => 'Factory',
                            'saldo_actual' => 0,
                        ],
                    ]);
                }
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withErrors(['factory_masivo' => 'Ocurrió un error al registrar Factoring masivo.'])
                ->withInput();
        }

        return back()->with('success', 'Factoring masivo registrado correctamente. Los documentos quedaron con saldo pendiente 0.');
    }

    /**
     * Define qué estado manual debe quedar después de eliminar Factoring.
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

    /**
     * Resolver banco para Factoring masivo.
     * Si viene "__otro__", crea el banco si no existe.
     */
    private function resolverBancoFactoryMasivo(string $bancoId, ?string $bancoOtro): Banco
    {
        $bancoOtro = trim((string) $bancoOtro);

        if ($bancoId === '__otro__') {
            if ($bancoOtro === '') {
                throw ValidationException::withMessages([
                    'banco_otro' => 'Debe ingresar el nombre del banco o Factoring.',
                ]);
            }

            $nombreBanco = preg_replace('/\s+/', ' ', $bancoOtro);

            $banco = Banco::whereRaw('LOWER(TRIM(nombre)) = ?', [
                mb_strtolower($nombreBanco),
            ])->first();

            if (!$banco) {
                $banco = Banco::create([
                    'nombre' => $nombreBanco,
                ]);
            }

            return $banco;
        }

        $banco = Banco::find($bancoId);

        if (!$banco) {
            throw ValidationException::withMessages([
                'banco_id' => 'El banco seleccionado no es válido.',
            ]);
        }

        return $banco;
    }
}