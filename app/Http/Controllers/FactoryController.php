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
     * Listado informativo de registros Factoring asociados a documentos CxC.
     * Muestra únicamente información almacenada, sin recalcular saldos ni montos.
     */
    public function index(Request $request)
    {
        $usuariosFinanzas = [1, 405, 374];

        if (!in_array(Auth::id(), $usuariosFinanzas)) {
            abort(403, 'Acceso denegado.');
        }

        $cesion       = trim((string) $request->input('cesion'));
        $folio        = trim((string) $request->input('folio'));
        $razonSocial  = trim((string) $request->input('razon_social'));
        $rutCliente   = trim((string) $request->input('rut_cliente'));
        $empresaId    = $request->input('empresa_id');
        $bancoId      = $request->input('banco_id');
        $fechaInicio  = $request->input('fecha_inicio');
        $fechaFin     = $request->input('fecha_fin');
        $perPage      = 10;

        $query = FactoryRegistro::query()
            ->with([
                'documentoFinanciero:id,empresa_id,tipo_documento_id,folio,razon_social,rut_cliente,status,status_original',
                'documentoFinanciero.empresa:id,Nombre',
                'documentoFinanciero.tipoDocumento:id,nombre',
                'banco:id,nombre',
                'usuario:id,name',
            ])

            ->when($cesion !== '', function ($q) use ($cesion) {
                $q->where('cesion', 'like', "%{$cesion}%");
            })

            ->when($folio !== '', function ($q) use ($folio) {
                $q->whereHas('documentoFinanciero', function ($documentoQuery) use ($folio) {
                    $documentoQuery->where('folio', 'like', "%{$folio}%");
                });
            })

            ->when($razonSocial !== '', function ($q) use ($razonSocial) {
                $q->whereHas('documentoFinanciero', function ($documentoQuery) use ($razonSocial) {
                    $documentoQuery->where('razon_social', 'like', "%{$razonSocial}%");
                });
            })

            ->when($rutCliente !== '', function ($q) use ($rutCliente) {
                $q->whereHas('documentoFinanciero', function ($documentoQuery) use ($rutCliente) {
                    $documentoQuery->where('rut_cliente', 'like', "%{$rutCliente}%");
                });
            })

            ->when($empresaId, function ($q) use ($empresaId) {
                $q->whereHas('documentoFinanciero', function ($documentoQuery) use ($empresaId) {
                    $documentoQuery->where('empresa_id', $empresaId);
                });
            })

            ->when($bancoId, function ($q) use ($bancoId) {
                $q->where('banco_id', $bancoId);
            })

            ->when($fechaInicio, function ($q) use ($fechaInicio) {
                $q->whereDate('fecha_factory', '>=', $fechaInicio);
            })

            ->when($fechaFin, function ($q) use ($fechaFin) {
                $q->whereDate('fecha_factory', '<=', $fechaFin);
            })

            ->orderByDesc('fecha_factory')
            ->orderByDesc('id');

        $factories = $query
            ->paginate($perPage)
            ->withQueryString();

        $empresas = \App\Models\Empresa::orderBy('Nombre')
            ->get(['id', 'Nombre']);

        $bancos = Banco::orderBy('nombre')
            ->get(['id', 'nombre']);

        return view('factoring.index', compact(
            'factories',
            'empresas',
            'bancos'
        ));
    }



    /**
     * Registrar estado Factoring para un documento financiero CxC.
     */
    public function store(Request $request, DocumentoFinanciero $documento)
    {

        $validated = $request->validate([
            'banco_id' => 'required|string|max:255',
            'banco_otro' => 'nullable|string|max:255',
            'rut_factory' => 'required|string|max:20',
            'cesion' => 'required|string|max:100',
            'saldo_liquido' => 'required|integer|min:0',
        ], [
            'banco_id.required' => 'Debe seleccionar el banco o entidad Factoring.',
            'rut_factory.required' => 'Debe ingresar el RUT del Factoring.',
            'rut_factory.max' => 'El RUT del Factoring no puede superar los 20 caracteres.',
            'cesion.required' => 'Debe ingresar la cesión del Factoring.',
            'saldo_liquido.required' => 'Debe ingresar el saldo líquido del Factoring.',
            'saldo_liquido.integer' => 'El saldo líquido debe ser un número entero.',
            'saldo_liquido.min' => 'El saldo líquido no puede ser negativo.',
        ]);

        if ((int) $documento->tipo_documento_id === 61) {
            return back()
                ->withErrors(['factory' => 'No se puede registrar Factoring sobre una nota de crédito.'])
                ->withInput();
        }


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
        $saldoLiquido = (int) $validated['saldo_liquido'];

        if ($saldoAnterior <= 0) {
            return back()
                ->withErrors(['factory' => 'No se puede registrar Factoring porque el documento no tiene saldo pendiente.'])
                ->withInput();
        }

        if ($saldoLiquido > $saldoAnterior) {
            return back()
                ->withErrors([
                    'saldo_liquido' => 'El saldo líquido no puede ser mayor al saldo pendiente actual del documento.',
                ])
                ->withInput();
        }

        $diferencia = max($saldoAnterior - $saldoLiquido, 0);

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
                $saldoLiquido,
                $diferencia,
                $estadoAnterior,
                $statusOriginalAnterior,
                $bancoSeleccionado
            ) {

                $factory = FactoryRegistro::create([
                    'documento_financiero_id' => $documento->id,
                    'banco_id' => $bancoSeleccionado->id,
                    'rut_factory' => trim($validated['rut_factory']),
                    'cesion' => trim($validated['cesion']),
                    'fecha_factory' => Carbon::today()->toDateString(),
                    'monto' => $saldoAnterior,
                    'saldo_liquido' => $saldoLiquido,
                    'diferencia' => $diferencia,
                    'user_id' => Auth::id(),
                ]);

                $documento->update([
                    'status' => 'Factory',
                    'fecha_estado_manual' => now(),
                    'saldo_pendiente' => $diferencia,
                ]);

                $documento->refresh();


                MovimientoDocumento::create([
                    'documento_financiero_id' => $documento->id,
                    'user_id' => Auth::id(),
                    'tipo_movimiento' => 'Registro de Factoring',
                    'descripcion' => "El documento folio {$documento->folio} fue marcado como Factoring. Saldo anterior: {$saldoAnterior}. Saldo líquido: {$saldoLiquido}. Saldo actual: {$diferencia}.",
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
                        'saldo_liquido' => $factory->saldo_liquido,
                        'diferencia' => $factory->diferencia,
                        'nuevo_estado' => 'Factory',
                        'saldo_actual' => $diferencia,
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
            'Factoring registrado correctamente. El saldo pendiente del documento fue actualizado según el saldo líquido.'
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
            'saldo_liquido' => $factory->saldo_liquido,
            'monto_no_anticipado' => $factory->monto_no_anticipado,
            'diferencia_precio' => $factory->diferencia_precio,
            'comision_total' => $factory->comision_total,
            'monto_a_recibir' => $factory->monto_a_recibir,

            /*
            |--------------------------------------------------------------------------
            | Compatibilidad histórica temporal
            |--------------------------------------------------------------------------
            */
            'diferencia_legacy' => $factory->diferencia,
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
                /*
                |--------------------------------------------------------------------------
                | Eliminar Factoring y recalcular saldo real
                |--------------------------------------------------------------------------
                | Al desaparecer el registro Factory, el saldo se vuelve a calcular
                | únicamente con los movimientos que continúan asociados al documento.
                |--------------------------------------------------------------------------
                */
                $factory->delete();

                $documento->update([
                    'saldo_pendiente' => null,
                ]);

                $documento->recalcularSaldoPendiente();
                $documento->refresh();

                /*
                |--------------------------------------------------------------------------
                | Restaurar estado manual vigente desde movimientos reales
                |--------------------------------------------------------------------------
                | Se utiliza la misma lógica central de DocumentoFinanciero aplicada
                | al eliminar Pago y Pronto pago. De esta forma, Factoring no aplica
                | una prioridad propia entre Abono y Cruce.
                |--------------------------------------------------------------------------
                */
                $nuevoEstadoManual = $documento->sincronizarEstadosDesdeMovimientos();

                $documento->refresh();

                $nuevoStatusOriginal = $documento->status_original;
                $nuevoEstadoVisible = $nuevoEstadoManual ?? $nuevoStatusOriginal;

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
                        'nuevo_estado_visible' => $nuevoEstadoVisible,
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

        return back()->with(
            'success',
            'Factoring eliminado, saldo recalculado y estado actualizado correctamente.'
        );
    }



    /**
     * Registrar Factoring masivo para documentos financieros CxC.
     */
    public function storeMasivo(Request $request)
    {
        $validated = $request->validate([
            'documentos' => 'required|array|min:1',

            /*
            |--------------------------------------------------------------------------
            | Datos generales de la operación Factoring
            |--------------------------------------------------------------------------
            | Se registran una sola vez desde el modal y se almacenan en todos los
            | documentos pertenecientes a esta misma operación masiva.
            |--------------------------------------------------------------------------
            */
            'banco_id' => 'required|string|max:255',
            'banco_otro' => 'nullable|string|max:255',
            'cesion' => 'required|string|max:255',
            'fecha_factory' => 'required|date',
            'comision_total' => 'required|integer|min:0',

            /*
            |--------------------------------------------------------------------------
            | Datos propios de cada documento
            |--------------------------------------------------------------------------
            */
            'documentos.*.saldo_liquido' => 'required|integer|min:0',
            'documentos.*.monto_no_anticipado' => 'required|integer|min:0',
        ], [
            'documentos.required' => 'Debe seleccionar al menos un documento.',
            'documentos.array' => 'La selección de documentos no es válida.',
            'documentos.min' => 'Debe seleccionar al menos un documento.',

            'banco_id.required' => 'Debe seleccionar el banco o entidad Factoring.',
            'cesion.required' => 'Debe ingresar la cesión del Factoring.',
            'fecha_factory.required' => 'Debe ingresar la fecha de operación Factoring.',
            'fecha_factory.date' => 'La fecha de operación Factoring no es válida.',

            'comision_total.required' => 'Debe ingresar la comisión total de la operación.',
            'comision_total.integer' => 'La comisión total debe ser un número entero.',
            'comision_total.min' => 'La comisión total no puede ser negativa.',

            'documentos.*.saldo_liquido.required' => 'Debe ingresar el monto líquido del Factoring.',
            'documentos.*.saldo_liquido.integer' => 'El monto líquido debe ser un número entero.',
            'documentos.*.saldo_liquido.min' => 'El monto líquido no puede ser negativo.',

            'documentos.*.monto_no_anticipado.required' => 'Debe ingresar el monto no anticipado.',
            'documentos.*.monto_no_anticipado.integer' => 'El monto no anticipado debe ser un número entero.',
            'documentos.*.monto_no_anticipado.min' => 'El monto no anticipado no puede ser negativo.',
        ]);

        $items = $validated['documentos'];

        $documentoIds = collect(array_keys($items))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($documentoIds->isEmpty()) {
            return back()
                ->withErrors([
                    'factory_masivo' => 'Debe seleccionar al menos un documento válido.',
                ])
                ->withInput();
        }

        /*
        |--------------------------------------------------------------------------
        | Validación previa para entregar errores agrupados antes de guardar
        |--------------------------------------------------------------------------
        */
        $documentos = DocumentoFinanciero::with([
                'factoryRegistro',
                'pagos',
                'prontoPagos',
            ])
            ->whereIn('id', $documentoIds)
            ->get()
            ->keyBy('id');

        $errores = [];

        $bancoId = $validated['banco_id'];
        $bancoOtro = trim((string) ($validated['banco_otro'] ?? ''));

        if ($bancoId === '__otro__' && $bancoOtro === '') {
            $errores[] = 'Debe ingresar el nombre del banco o Factoring.';
        }

        if ($bancoId !== '__otro__' && !Banco::whereKey($bancoId)->exists()) {
            $errores[] = 'El banco seleccionado no es válido.';
        }

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

            $monto = (int) $documento->saldo_pendiente;
            $saldoLiquido = (int) ($data['saldo_liquido'] ?? 0);
            $montoNoAnticipado = (int) ($data['monto_no_anticipado'] ?? 0);

            if (($saldoLiquido + $montoNoAnticipado) > $monto) {
                $errores[] = "{$identificador}: la suma del monto líquido y el monto no anticipado no puede ser mayor al monto del documento.";
            }
        }

        if (!empty($errores)) {
            return back()
                ->withErrors(['factory_masivo' => $errores])
                ->withInput();
        }

        try {
            DB::transaction(function () use ($documentoIds, $items, $validated) {
                $banco = $this->resolverBancoFactoryMasivo(
                    bancoId: $validated['banco_id'],
                    bancoOtro: $validated['banco_otro'] ?? null,
                );

                $cesion = trim($validated['cesion']);
                $fechaFactory = $validated['fecha_factory'];
                $comisionTotal = (int) $validated['comision_total'];

                /*
                |--------------------------------------------------------------------------
                | Primera pasada: bloquear y calcular la operación completa
                |--------------------------------------------------------------------------
                | monto_a_recibir depende de los totales de todos los documentos, por
                | eso se calcula antes de crear cualquier registro Factory.
                |--------------------------------------------------------------------------
                */
                $calculosPorDocumento = collect();

                $montoAnticipadoTotal = 0;
                $diferenciaPrecioTotal = 0;

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

                    /*
                    |--------------------------------------------------------------------------
                    | Valores individuales del documento
                    |--------------------------------------------------------------------------
                    | monto corresponde al saldo pendiente cedido al registrar.
                    | saldo_liquido corresponde al monto ingresado por documento.
                    |--------------------------------------------------------------------------
                    */
                    $monto = (int) $documento->saldo_pendiente;
                    $saldoLiquido = (int) $data['saldo_liquido'];
                    $montoNoAnticipado = (int) $data['monto_no_anticipado'];

                    if (($saldoLiquido + $montoNoAnticipado) > $monto) {
                        throw ValidationException::withMessages([
                            'factory_masivo' => "La suma del monto líquido y el monto no anticipado del documento folio {$documento->folio} no puede ser mayor al monto del documento.",
                        ]);
                    }

                    $diferenciaPrecio = $monto
                        - $saldoLiquido
                        - $montoNoAnticipado;

                    $montoAnticipadoTotal += $saldoLiquido;
                    $diferenciaPrecioTotal += $diferenciaPrecio;

                    $calculosPorDocumento->push([
                        'documento' => $documento,
                        'monto' => $monto,
                        'saldo_liquido' => $saldoLiquido,
                        'monto_no_anticipado' => $montoNoAnticipado,
                        'diferencia_precio' => $diferenciaPrecio,
                        'estado_anterior' => $documento->status,
                        'status_original_anterior' => $documento->status_original,
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Totales generales de la operación
                |--------------------------------------------------------------------------
                | Regla aprobada:
                |
                | Monto Líquido operación = Monto Anticipado + Diferencia de Precio
                |
                | Monto a Recibir =
                |     Monto Líquido operación
                |     - Comisión Total
                |     - Diferencia de Precio
                |--------------------------------------------------------------------------
                */
                $montoLiquidoOperacion = $montoAnticipadoTotal
                    + $diferenciaPrecioTotal;

                $montoARecibir = $montoLiquidoOperacion
                    - $comisionTotal
                    - $diferenciaPrecioTotal;

                if ($montoARecibir < 0) {
                    throw ValidationException::withMessages([
                        'comision_total' => 'La comisión total genera un monto a recibir negativo para la operación.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Segunda pasada: crear registros y movimientos
                |--------------------------------------------------------------------------
                | comision_total y monto_a_recibir son valores generales de la cesión.
                | Por decisión actual de estructura, se almacenan en cada Factory de
                | esta operación masiva.
                |--------------------------------------------------------------------------
                */
                foreach ($calculosPorDocumento as $calculo) {
                    /** @var DocumentoFinanciero $documento */
                    $documento = $calculo['documento'];

                    $factory = FactoryRegistro::create([
                        'documento_financiero_id' => $documento->id,
                        'banco_id' => $banco->id,

                        /*
                        |--------------------------------------------------------------------------
                        | Campo sin uso en el flujo masivo actual
                        |--------------------------------------------------------------------------
                        | La columna rut_factory continúa siendo NOT NULL.
                        |--------------------------------------------------------------------------
                        */
                        'rut_factory' => '',

                        'cesion' => $cesion,
                        'fecha_factory' => $fechaFactory,
                        'monto' => $calculo['monto'],
                        'saldo_liquido' => $calculo['saldo_liquido'],
                        'monto_no_anticipado' => $calculo['monto_no_anticipado'],
                        'diferencia_precio' => $calculo['diferencia_precio'],
                        'comision_total' => $comisionTotal,
                        'monto_a_recibir' => $montoARecibir,
                        'user_id' => Auth::id(),
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Saldo pendiente resultante del documento
                    |--------------------------------------------------------------------------
                    | diferencia_precio reemplaza funcionalmente al campo diferencia
                    | en los registros Factoring nuevos.
                    |--------------------------------------------------------------------------
                    */
                    $documento->update([
                        'status' => 'Factory',
                        'fecha_estado_manual' => now(),
                        'saldo_pendiente' => $calculo['diferencia_precio'],
                    ]);

                    MovimientoDocumento::create([
                        'documento_financiero_id' => $documento->id,
                        'user_id' => Auth::id(),
                        'tipo_movimiento' => 'Registro de Factoring masivo',
                        'descripcion' => "El documento folio {$documento->folio} fue marcado como Factoring masivo. Monto: {$calculo['monto']}. Monto líquido: {$calculo['saldo_liquido']}. Monto no anticipado: {$calculo['monto_no_anticipado']}. Diferencia de precio: {$calculo['diferencia_precio']}. Comisión total operación: {$comisionTotal}. Monto a recibir operación: {$montoARecibir}.",
                        'datos_anteriores' => [
                            'estado' => $calculo['estado_anterior'],
                            'status_original' => $calculo['status_original_anterior'],
                            'saldo_anterior' => $calculo['monto'],
                        ],
                        'datos_nuevos' => [
                            'factory_id' => $factory->id,
                            'banco_id' => $factory->banco_id,
                            'banco' => $banco->nombre,
                            'cesion' => $factory->cesion,
                            'fecha_factory' => $factory->fecha_factory,

                            'monto' => $factory->monto,
                            'saldo_liquido' => $factory->saldo_liquido,
                            'monto_no_anticipado' => $factory->monto_no_anticipado,
                            'diferencia_precio' => $factory->diferencia_precio,

                            'comision_total' => $factory->comision_total,
                            'monto_a_recibir' => $factory->monto_a_recibir,

                            'nuevo_estado' => 'Factory',
                            'saldo_actual' => $calculo['diferencia_precio'],
                        ],
                    ]);
                }
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withErrors([
                    'factory_masivo' => 'Ocurrió un error al registrar Factoring masivo.',
                ])
                ->withInput();
        }

        return back()->with(
            'success',
            'Factoring masivo registrado correctamente. Los saldos pendientes y los totales de la operación fueron almacenados.'
        );
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