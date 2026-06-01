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
     *
     * Presenta la información almacenada agrupada por mes y por operación
     * Factoring, sin modificar ni recalcular el saldo pendiente de los documentos.
     *
     * La operación se identifica, con la estructura actualmente disponible, por:
     * fecha_factory + cesion + banco_id.
     */
    public function index(Request $request)
    {
        $usuariosFinanzas = [1, 405, 374];

        if (!in_array(Auth::id(), $usuariosFinanzas, true)) {
            abort(403, 'Acceso denegado.');
        }

        $cesion        = trim((string) $request->input('cesion'));
        $folio         = trim((string) $request->input('folio'));
        $razonSocial   = trim((string) $request->input('razon_social'));
        $rutCliente    = trim((string) $request->input('rut_cliente'));
        $empresaId     = $request->input('empresa_id');
        $bancoId       = $request->input('banco_id');
        $mesOperacion  = trim((string) $request->input('mes_operacion'));

        /*
        |--------------------------------------------------------------------------
        | Compatibilidad temporal con filtros anteriores
        |--------------------------------------------------------------------------
        | La nueva vista usará mes_operacion, pero se mantienen fecha_inicio y
        | fecha_fin mientras conviva con filtros antiguos.
        |--------------------------------------------------------------------------
        */
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin    = $request->input('fecha_fin');

        $perPage = 10;

        /*
        |--------------------------------------------------------------------------
        | Resolver período de operación
        |--------------------------------------------------------------------------
        | Importante:
        | El período se usa después de consolidar cesiones, para que si una cesión
        | tiene un movimiento en el mes filtrado, se muestre la cesión completa
        | con todos sus movimientos/documentos relacionados.
        |--------------------------------------------------------------------------
        */
        $periodoInicio = null;
        $periodoFin = null;

        if (
            $mesOperacion !== '' &&
            preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mesOperacion)
        ) {
            $mes = \Carbon\Carbon::createFromFormat(
                'Y-m-d',
                $mesOperacion . '-01'
            )->startOfMonth();

            $periodoInicio = $mes->toDateString();
            $periodoFin = $mes->copy()->endOfMonth()->toDateString();
        } else {
            if ($fechaInicio) {
                $periodoInicio = \Carbon\Carbon::parse($fechaInicio)->toDateString();
            }

            if ($fechaFin) {
                $periodoFin = \Carbon\Carbon::parse($fechaFin)->toDateString();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Consulta base
        |--------------------------------------------------------------------------
        | Aquí solo se filtra por atributos propios de Factoring que no rompen la
        | consolidación de la cesión completa.
        |
        | No se filtra por fecha aquí, porque eso partiría una misma cesión entre
        | meses si tuvo una operación inicial y luego un movimiento posterior.
        |--------------------------------------------------------------------------
        */
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
            ->when($bancoId, function ($q) use ($bancoId) {
                $q->where('banco_id', $bancoId);
            })
            ->orderByDesc('fecha_factory')
            ->orderByDesc('id');

        $registros = $query->get();

        /*
        |--------------------------------------------------------------------------
        | Consolidar por Cesión + Banco
        |--------------------------------------------------------------------------
        | Esta es la unidad principal de la nueva vista.
        |
        | Antes:
        |   fecha_factory + cesion + banco
        |
        | Ahora:
        |   cesion + banco
        |
        | Dentro de cada cesión se agrupan los movimientos por fecha_factory.
        |--------------------------------------------------------------------------
        */
        $cesiones = $registros
            ->groupBy(function ($factory) {
                return implode('|', [
                    (string) ($factory->cesion ?? 'sin-cesion'),
                    (string) ($factory->banco_id ?? 'sin-banco'),
                ]);
            })
            ->map(function ($registrosCesion, $claveCesion) {
                $registrosOrdenados = $registrosCesion
                    ->sortBy(function ($factory) {
                        $fecha = $factory->fecha_factory
                            ? $factory->fecha_factory->format('Y-m-d')
                            : '0000-00-00';

                        return $fecha . '-' . str_pad((string) $factory->id, 12, '0', STR_PAD_LEFT);
                    })
                    ->values();

                $factoryBase = $registrosOrdenados->first();
                $factoryUltimo = $registrosOrdenados->last();

                /*
                |--------------------------------------------------------------------------
                | Movimientos internos de la cesión
                |--------------------------------------------------------------------------
                | Cada movimiento corresponde a una fecha de operación dentro de la
                | misma cesión. Ejemplo:
                |
                | Cesión 665162
                |   - 02-04-2026: operación inicial
                |   - 20-05-2026: movimiento posterior / liquidación
                |--------------------------------------------------------------------------
                */
                $movimientos = $registrosOrdenados
                    ->groupBy(function ($factory) {
                        return $factory->fecha_factory
                            ? $factory->fecha_factory->format('Y-m-d')
                            : 'sin-fecha';
                    })
                    ->map(function ($documentosMovimiento, $fechaClave) {
                        $documentosMovimiento = $documentosMovimiento
                            ->sortBy(function ($factory) {
                                $folio = (string) ($factory->documentoFinanciero?->folio ?? '');

                                return str_pad($folio, 20, '0', STR_PAD_LEFT);
                            })
                            ->values();

                        $factoryMovimientoBase = $documentosMovimiento->first();
                        $fechaOperacion = $factoryMovimientoBase->fecha_factory;

                        $montoDocumentos = (int) $documentosMovimiento->sum(function ($factory) {
                            return (int) ($factory->monto ?? 0);
                        });

                        $montoAnticipado = (int) $documentosMovimiento->sum(function ($factory) {
                            return (int) ($factory->saldo_liquido ?? 0);
                        });

                        $montoNoAnticipado = (int) $documentosMovimiento->sum(function ($factory) {
                            return (int) ($factory->monto_no_anticipado ?? 0);
                        });

                        $diferenciaPrecio = (int) $documentosMovimiento->sum(function ($factory) {
                            return (int) ($factory->diferencia_precio ?? 0);
                        });

                        /*
                        |--------------------------------------------------------------------------
                        | Resumen financiero del movimiento
                        |--------------------------------------------------------------------------
                        | Estos valores son solo de presentación. No recalculan ni alteran
                        | saldo_pendiente; muestran lo ya persistido en factories.
                        |--------------------------------------------------------------------------
                        */
                        $montoLiquido = $montoAnticipado + $diferenciaPrecio;
                        $precioCompra = $montoLiquido;

                        /*
                        |--------------------------------------------------------------------------
                        | Valores globales por movimiento
                        |--------------------------------------------------------------------------
                        | En cargas masivas, comision_total y monto_a_recibir se guardan
                        | repetidos en cada fila del mismo movimiento. Se toman una sola vez.
                        |--------------------------------------------------------------------------
                        */
                        $comisionesRegistradas = $documentosMovimiento
                            ->pluck('comision_total')
                            ->filter(fn ($valor) => $valor !== null)
                            ->unique()
                            ->values();

                        $montosARecibirRegistrados = $documentosMovimiento
                            ->pluck('monto_a_recibir')
                            ->filter(fn ($valor) => $valor !== null)
                            ->unique()
                            ->values();

                        return [
                            'clave_movimiento' => $fechaClave,
                            'fecha_factory' => $fechaOperacion,
                            'fecha_orden' => $fechaOperacion
                                ? $fechaOperacion->format('Y-m-d')
                                : '0000-00-00',

                            'banco' => $factoryMovimientoBase->banco,
                            'usuario' => $factoryMovimientoBase->usuario,

                            'cantidad_documentos' => $documentosMovimiento->count(),
                            'cantidad_documentos_unicos' => $documentosMovimiento
                                ->pluck('documento_financiero_id')
                                ->unique()
                                ->count(),

                            'monto_documentos' => $montoDocumentos,
                            'monto_anticipado' => $montoAnticipado,
                            'monto_no_anticipado' => $montoNoAnticipado,
                            'diferencia_precio' => $diferenciaPrecio,
                            'monto_liquido' => $montoLiquido,
                            'precio_compra' => $precioCompra,

                            'comision_total' => $comisionesRegistradas->first(),
                            'monto_a_recibir' => $montosARecibirRegistrados->first(),

                            'valores_globales_consistentes' =>
                                $comisionesRegistradas->count() <= 1 &&
                                $montosARecibirRegistrados->count() <= 1,

                            'documentos' => $documentosMovimiento,
                        ];
                    })
                    ->sortBy('fecha_orden')
                    ->values();

                $fechaInicioCesion = $registrosOrdenados->first()?->fecha_factory;
                $fechaUltimoMovimiento = $registrosOrdenados->last()?->fecha_factory;

                $mesClave = $fechaUltimoMovimiento
                    ? $fechaUltimoMovimiento->format('Y-m')
                    : 'sin-fecha';

                $mesEtiqueta = $fechaUltimoMovimiento
                    ? ucfirst(
                        $fechaUltimoMovimiento
                            ->copy()
                            ->locale('es')
                            ->translatedFormat('F Y')
                    )
                    : 'Sin fecha de operación';

                /*
                |--------------------------------------------------------------------------
                | Totales consolidados de la cesión
                |--------------------------------------------------------------------------
                | Se suman los movimientos internos. Esto permite que una cesión con
                | operación inicial y liquidación posterior muestre el total consolidado.
                |--------------------------------------------------------------------------
                */
                $montoDocumentos = (int) $movimientos->sum('monto_documentos');
                $montoAnticipado = (int) $movimientos->sum('monto_anticipado');
                $montoNoAnticipado = (int) $movimientos->sum('monto_no_anticipado');
                $diferenciaPrecio = (int) $movimientos->sum('diferencia_precio');
                $montoLiquido = (int) $movimientos->sum('monto_liquido');
                $precioCompra = (int) $movimientos->sum('precio_compra');

                /*
                |--------------------------------------------------------------------------
                | Comisión y monto a recibir consolidados
                |--------------------------------------------------------------------------
                | Se suman una vez por movimiento, no por documento.
                |--------------------------------------------------------------------------
                */
                $comisionTotal = (int) $movimientos->sum(function ($movimiento) {
                    return (int) ($movimiento['comision_total'] ?? 0);
                });

                $montoARecibir = (int) $movimientos->sum(function ($movimiento) {
                    return (int) ($movimiento['monto_a_recibir'] ?? 0);
                });

                return [
                    'clave_cesion' => $claveCesion,
                    'mes_clave' => $mesClave,
                    'mes_etiqueta' => $mesEtiqueta,

                    'cesion' => $factoryBase->cesion,
                    'banco' => $factoryBase->banco,

                    /*
                    |--------------------------------------------------------------------------
                    | Usuario informativo
                    |--------------------------------------------------------------------------
                    | Se muestra el usuario del último movimiento de la cesión.
                    |--------------------------------------------------------------------------
                    */
                    'usuario' => $factoryUltimo->usuario,

                    'fecha_inicio' => $fechaInicioCesion,
                    'fecha_ultimo_movimiento' => $fechaUltimoMovimiento,

                    'cantidad_movimientos' => $movimientos->count(),

                    /*
                    |--------------------------------------------------------------------------
                    | Cantidad de documentos
                    |--------------------------------------------------------------------------
                    | cantidad_documentos cuenta registros/movimientos de documento.
                    | cantidad_documentos_unicos queda disponible si la vista quiere
                    | diferenciar documentos únicos de movimientos posteriores.
                    |--------------------------------------------------------------------------
                    */
                    'cantidad_documentos' => $registrosOrdenados->count(),
                    'cantidad_documentos_unicos' => $registrosOrdenados
                        ->pluck('documento_financiero_id')
                        ->unique()
                        ->count(),

                    'monto_documentos' => $montoDocumentos,
                    'monto_anticipado' => $montoAnticipado,
                    'monto_no_anticipado' => $montoNoAnticipado,
                    'diferencia_precio' => $diferenciaPrecio,
                    'monto_liquido' => $montoLiquido,
                    'precio_compra' => $precioCompra,
                    'comision_total' => $comisionTotal,
                    'monto_a_recibir' => $montoARecibir,

                    'valores_globales_consistentes' => $movimientos
                        ->every(fn ($movimiento) => $movimiento['valores_globales_consistentes']),

                    'movimientos' => $movimientos,

                    /*
                    |--------------------------------------------------------------------------
                    | Documentos de la cesión
                    |--------------------------------------------------------------------------
                    | Incluye todos los registros Factory de la cesión, incluso si un
                    | mismo documento aparece en un movimiento posterior.
                    |--------------------------------------------------------------------------
                    */
                    'documentos' => $registrosOrdenados,
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Filtro por período de operación
        |--------------------------------------------------------------------------
        | Se conserva la cesión completa si al menos uno de sus movimientos cae
        | dentro del período seleccionado.
        |--------------------------------------------------------------------------
        */
        if ($periodoInicio || $periodoFin) {
            $cesiones = $cesiones
                ->filter(function ($cesionItem) use ($periodoInicio, $periodoFin) {
                    return $cesionItem['movimientos']->contains(function ($movimiento) use (
                        $periodoInicio,
                        $periodoFin
                    ) {
                        $fecha = $movimiento['fecha_factory'];

                        if (!$fecha) {
                            return false;
                        }

                        $fechaMovimiento = $fecha->toDateString();

                        if ($periodoInicio && $fechaMovimiento < $periodoInicio) {
                            return false;
                        }

                        if ($periodoFin && $fechaMovimiento > $periodoFin) {
                            return false;
                        }

                        return true;
                    });
                })
                ->values();
        }

        /*
        |--------------------------------------------------------------------------
        | Filtros asociados a documentos
        |--------------------------------------------------------------------------
        | Si un documento coincide, se conserva la cesión completa para que el
        | resumen mantenga todos sus movimientos e importes relacionados.
        |--------------------------------------------------------------------------
        */
        $cesiones = $cesiones
            ->filter(function ($cesionItem) use (
                $folio,
                $razonSocial,
                $rutCliente,
                $empresaId
            ) {
                if (
                    $folio === '' &&
                    $razonSocial === '' &&
                    $rutCliente === '' &&
                    !$empresaId
                ) {
                    return true;
                }

                return $cesionItem['documentos']->contains(function ($factory) use (
                    $folio,
                    $razonSocial,
                    $rutCliente,
                    $empresaId
                ) {
                    $documento = $factory->documentoFinanciero;

                    if (!$documento) {
                        return false;
                    }

                    if (
                        $folio !== '' &&
                        mb_stripos((string) ($documento->folio ?? ''), $folio) === false
                    ) {
                        return false;
                    }

                    if (
                        $razonSocial !== '' &&
                        mb_stripos(
                            (string) ($documento->razon_social ?? ''),
                            $razonSocial
                        ) === false
                    ) {
                        return false;
                    }

                    if (
                        $rutCliente !== '' &&
                        mb_stripos(
                            (string) ($documento->rut_cliente ?? ''),
                            $rutCliente
                        ) === false
                    ) {
                        return false;
                    }

                    if (
                        $empresaId &&
                        (string) $documento->empresa_id !== (string) $empresaId
                    ) {
                        return false;
                    }

                    return true;
                });
            })
            ->sortByDesc(function ($cesionItem) {
                return $cesionItem['fecha_ultimo_movimiento']
                    ? $cesionItem['fecha_ultimo_movimiento']->format('Y-m-d')
                    : '0000-00-00';
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Paginar cesiones completas
        |--------------------------------------------------------------------------
        | Cada item del paginador es una cesión completa, no una fila Factory.
        |--------------------------------------------------------------------------
        */
        $paginaActual = \Illuminate\Pagination\Paginator::resolveCurrentPage('page');

        $cesionesPagina = $cesiones
            ->forPage($paginaActual, $perPage)
            ->values();

        $paginadorOperaciones = new \Illuminate\Pagination\LengthAwarePaginator(
            $cesionesPagina,
            $cesiones->count(),
            $perPage,
            $paginaActual,
            [
                'path' => $request->url(),
                'query' => $request->query(),
                'pageName' => 'page',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Agrupación visual por mes dentro de la página actual
        |--------------------------------------------------------------------------
        | La cesión se ubica visualmente en el mes de su último movimiento.
        |--------------------------------------------------------------------------
        */
        $cesionesPorMes = $cesionesPagina
            ->groupBy('mes_clave')
            ->map(function ($cesionesDelMes) {
                return [
                    'mes_clave' => $cesionesDelMes->first()['mes_clave'],
                    'mes_etiqueta' => $cesionesDelMes->first()['mes_etiqueta'],
                    'cesiones' => $cesionesDelMes,
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Compatibilidad temporal
        |--------------------------------------------------------------------------
        | Mientras ajustamos la vista, dejamos también operacionesPorMes apuntando
        | a la nueva estructura.
        |--------------------------------------------------------------------------
        */
        $operacionesPorMes = $cesionesPorMes;

        $empresas = \App\Models\Empresa::orderBy('Nombre')
            ->get(['id', 'Nombre']);

        $bancos = Banco::orderBy('nombre')
            ->get(['id', 'nombre']);

        return view('factoring.index', compact(
            'cesionesPorMes',
            'operacionesPorMes',
            'paginadorOperaciones',
            'empresas',
            'bancos'
        ));
    }



    /**
     * Registrar estado Factoring para un documento financiero CxC.
     *
     * Un documento puede acumular más de un registro Factoring, siempre que
     * continúe teniendo saldo pendiente y no exista un movimiento de cierre.
     */
    public function store(Request $request, DocumentoFinanciero $documento)
    {
        try {
            $validated = $request->validate([
                'banco_id' => 'required|string|max:255',
                'banco_otro' => 'nullable|required_if:banco_id,__otro__|string|max:255',

                'cesion' => 'required|string|max:100',
                'fecha_factory' => 'required|date',
                'comision_total' => 'required|integer|min:0',

                'saldo_liquido' => 'required|integer|min:0',
                'monto_no_anticipado' => 'required|integer|min:0',
            ], [
                'banco_id.required' => 'Debe seleccionar el banco o entidad Factoring.',

                'banco_otro.required_if' => 'Debe ingresar el nombre del banco o entidad Factoring.',
                'banco_otro.max' => 'El nombre del banco o entidad Factoring no puede superar los 255 caracteres.',

                'cesion.required' => 'Debe ingresar la cesión del Factoring.',
                'cesion.max' => 'La cesión del Factoring no puede superar los 100 caracteres.',

                'fecha_factory.required' => 'Debe ingresar la fecha de operación Factoring.',
                'fecha_factory.date' => 'La fecha de operación Factoring no es válida.',

                'comision_total.required' => 'Debe ingresar la comisión total de la operación.',
                'comision_total.integer' => 'La comisión total debe ser un número entero.',
                'comision_total.min' => 'La comisión total no puede ser negativa.',

                'saldo_liquido.required' => 'Debe ingresar el monto líquido del Factoring.',
                'saldo_liquido.integer' => 'El monto líquido debe ser un número entero.',
                'saldo_liquido.min' => 'El monto líquido no puede ser negativo.',

                'monto_no_anticipado.required' => 'Debe ingresar el monto no anticipado.',
                'monto_no_anticipado.integer' => 'El monto no anticipado debe ser un número entero.',
                'monto_no_anticipado.min' => 'El monto no anticipado no puede ser negativo.',
            ]);
            DB::transaction(function () use ($validated, $documento) {
                /*
                |--------------------------------------------------------------------------
                | Bloquear documento y trabajar con su saldo vigente
                |--------------------------------------------------------------------------
                */
                $documentoBloqueado = DocumentoFinanciero::whereKey($documento->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $factoriesExistentes = $documentoBloqueado->factories()->count();
                $tienePago = $documentoBloqueado->pagos()->exists();
                $tieneProntoPago = $documentoBloqueado->prontoPagos()->exists();
                $saldoPendienteActual = (int) $documentoBloqueado->saldo_pendiente;

                if ((int) $documentoBloqueado->tipo_documento_id === 61) {


                    throw ValidationException::withMessages([
                        'factory' => 'No se puede registrar Factoring sobre una nota de crédito.',
                    ]);
                }

                if ((int) $documentoBloqueado->tipo_documento_id === 56) {


                    throw ValidationException::withMessages([
                        'factory' => 'No se puede registrar Factoring sobre una nota de débito.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Movimientos de cierre
                |--------------------------------------------------------------------------
                | Factoring puede repetirse mientras exista saldo. Pago y Pronto pago
                | continúan cerrando el documento e impiden nuevos registros.
                |--------------------------------------------------------------------------
                */
                if ($tienePago) {
                    throw ValidationException::withMessages([
                        'factory' => 'No se puede registrar Factoring porque el documento ya tiene un pago registrado.',
                    ]);
                }

                if ($tieneProntoPago) {
                    throw ValidationException::withMessages([
                        'factory' => 'No se puede registrar Factoring porque el documento ya tiene un pronto pago registrado.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Saldo cedido del nuevo Factoring
                |--------------------------------------------------------------------------
                */
                $saldoAnterior = $saldoPendienteActual;
                $monto = $saldoAnterior;

                $saldoLiquido = (int) $validated['saldo_liquido'];
                $montoNoAnticipado = (int) $validated['monto_no_anticipado'];
                $comisionTotal = (int) $validated['comision_total'];

                if ($monto <= 0) {

                    throw ValidationException::withMessages([
                        'factory' => 'No se puede registrar Factoring porque el documento no tiene saldo pendiente.',
                    ]);
                }

                if (($saldoLiquido + $montoNoAnticipado) > $monto) {

                    throw ValidationException::withMessages([
                        'saldo_liquido' => 'La suma del Monto Líquido y el Monto No Anticipado no puede ser mayor al monto pendiente actual del documento.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Diferencia de Precio
                |--------------------------------------------------------------------------
                */
                $diferenciaPrecio = $monto
                    - $saldoLiquido
                    - $montoNoAnticipado;

                $montoAnticipado = $saldoLiquido;

                $montoLiquido = $montoAnticipado
                    + $diferenciaPrecio;

                $precioCompra = $montoLiquido;

                $montoARecibir = $montoLiquido
                    - $comisionTotal
                    - $diferenciaPrecio;

                if ($montoARecibir < 0) {

                    throw ValidationException::withMessages([
                        'comision_total' => 'La comisión total genera un monto a recibir negativo para la operación.',
                    ]);
                }

                $bancoSeleccionado = $this->resolverBancoFactoryMasivo(
                    bancoId: $validated['banco_id'],
                    bancoOtro: $validated['banco_otro'] ?? null,
                );

                $estadoAnterior = $documentoBloqueado->status;
                $statusOriginalAnterior = $documentoBloqueado->status_original;
                $cantidadFactoriesAnteriores = $factoriesExistentes;

                $factory = FactoryRegistro::create([
                    'documento_financiero_id' => $documentoBloqueado->id,
                    'banco_id' => $bancoSeleccionado->id,

                    /*
                    |--------------------------------------------------------------------------
                    | Campo aún obligatorio en base de datos
                    |--------------------------------------------------------------------------
                    | No tiene captura en el flujo vigente.
                    |--------------------------------------------------------------------------
                    */
                    'rut_factory' => '',

                    'cesion' => trim($validated['cesion']),
                    'fecha_factory' => $validated['fecha_factory'],

                    'monto' => $monto,
                    'saldo_liquido' => $saldoLiquido,
                    'monto_no_anticipado' => $montoNoAnticipado,
                    'diferencia_precio' => $diferenciaPrecio,

                    'comision_total' => $comisionTotal,
                    'monto_a_recibir' => $montoARecibir,

                    'user_id' => Auth::id(),
                ]);

                /*
                |--------------------------------------------------------------------------
                | Recalcular desde movimientos reales existentes
                |--------------------------------------------------------------------------
                */
                $saldoActual = $documentoBloqueado->recalcularSaldoPendiente();

                $documentoBloqueado->update([
                    'status' => 'Factory',
                    'fecha_estado_manual' => now(),
                ]);

                MovimientoDocumento::create([
                    'documento_financiero_id' => $documentoBloqueado->id,
                    'user_id' => Auth::id(),
                    'tipo_movimiento' => 'Registro de Factoring',
                    'descripcion' => "El documento folio {$documentoBloqueado->folio} fue marcado como Factoring. Monto: {$monto}. Monto líquido: {$saldoLiquido}. Monto no anticipado: {$montoNoAnticipado}. Diferencia de precio: {$diferenciaPrecio}. Comisión total: {$comisionTotal}. Monto a recibir: {$montoARecibir}.",
                    'datos_anteriores' => [
                        'estado' => $estadoAnterior,
                        'status_original' => $statusOriginalAnterior,
                        'saldo_anterior' => $saldoAnterior,
                        'cantidad_factoring_anteriores' => $cantidadFactoriesAnteriores,
                    ],
                    'datos_nuevos' => [
                        'factory_id' => $factory->id,
                        'banco_id' => $factory->banco_id,
                        'banco' => $bancoSeleccionado->nombre,
                        'cesion' => $factory->cesion,
                        'fecha_factory' => $factory->fecha_factory,

                        'monto' => $factory->monto,
                        'saldo_liquido' => $factory->saldo_liquido,
                        'monto_no_anticipado' => $factory->monto_no_anticipado,
                        'diferencia_precio' => $factory->diferencia_precio,

                        'monto_liquido' => $montoLiquido,
                        'precio_compra' => $precioCompra,
                        'comision_total' => $factory->comision_total,
                        'monto_a_recibir' => $factory->monto_a_recibir,

                        'cantidad_factoring_actual' => $cantidadFactoriesAnteriores + 1,
                        'nuevo_estado' => 'Factory',
                        'saldo_actual' => $saldoActual,
                    ],
                ]);

            });


        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {


            report($e);

            return back()
                ->withErrors([
                    'factory' => 'Ocurrió un error al registrar Factoring.',
                ])
                ->withInput();
        }

        return back()->with(
            'success',
            'Factoring registrado correctamente. El saldo pendiente del documento fue actualizado según la diferencia de precio.'
        );
    }

    /**
     * Eliminar un registro Factoring y restaurar saldo/estado según movimientos restantes.
     *
     * Puede eliminarse cualquiera de los registros Factoring asociados al documento.
     * El saldo se recalcula utilizando únicamente los movimientos que continúan
     * existiendo después de la eliminación.
     */
    public function destroy(FactoryRegistro $factory)
    {
        try {
            DB::transaction(function () use ($factory) {
                /*
                |--------------------------------------------------------------------------
                | Bloquear documento y registro Factoring eliminado
                |--------------------------------------------------------------------------
                */
                $documento = DocumentoFinanciero::whereKey($factory->documento_financiero_id)
                    ->lockForUpdate()
                    ->first();

                if (!$documento) {
                    throw ValidationException::withMessages([
                        'factory' => 'No se encontró el documento financiero asociado al Factoring.',
                    ]);
                }

                $factoryEliminar = FactoryRegistro::with('banco')
                    ->whereKey($factory->id)
                    ->where('documento_financiero_id', $documento->id)
                    ->lockForUpdate()
                    ->first();

                if (!$factoryEliminar) {
                    throw ValidationException::withMessages([
                        'factory' => 'No se encontró el registro Factoring que se desea eliminar.',
                    ]);
                }

                $datosAnteriores = [
                    'factory_id' => $factoryEliminar->id,
                    'documento_financiero_id' => $factoryEliminar->documento_financiero_id,
                    'banco_id' => $factoryEliminar->banco_id,
                    'banco' => $factoryEliminar->banco?->nombre,
                    'rut_factory' => $factoryEliminar->rut_factory,
                    'cesion' => $factoryEliminar->cesion,
                    'fecha_factory' => $factoryEliminar->fecha_factory,
                    'monto' => $factoryEliminar->monto,
                    'saldo_liquido' => $factoryEliminar->saldo_liquido,
                    'monto_no_anticipado' => $factoryEliminar->monto_no_anticipado,
                    'diferencia_precio' => $factoryEliminar->diferencia_precio,
                    'comision_total' => $factoryEliminar->comision_total,
                    'monto_a_recibir' => $factoryEliminar->monto_a_recibir,
                ];

                $estadoAnterior = $documento->status;
                $statusOriginalAnterior = $documento->status_original;
                $saldoAnterior = (int) $documento->saldo_pendiente;
                $cantidadFactoriesAntes = $documento->factories()->count();

                /*
                |--------------------------------------------------------------------------
                | Eliminar únicamente el Factoring seleccionado
                |--------------------------------------------------------------------------
                */
                $factoryEliminar->delete();

                /*
                |--------------------------------------------------------------------------
                | Recalcular saldo real y Factorings restantes
                |--------------------------------------------------------------------------
                | Si permanecen Factorings posteriores, el modelo reconstruirá su monto
                | cedido y diferencia_precio sobre la nueva secuencia vigente.
                |--------------------------------------------------------------------------
                */
                $saldoActual = $documento->recalcularSaldoPendiente();

                $documento->refresh();

                /*
                |--------------------------------------------------------------------------
                | Recuperar estado manual real vigente
                |--------------------------------------------------------------------------
                | Si permanece otro Factoring posterior o anterior como último movimiento,
                | el estado seguirá siendo Factory. En caso contrario recuperará Abono,
                | Cruce, Pago, Pronto pago, Cobranza judicial o estado automático según
                | corresponda a la lógica central del modelo.
                |--------------------------------------------------------------------------
                */
                $nuevoEstadoManual = $documento->sincronizarEstadosDesdeMovimientos();

                $documento->refresh();

                $nuevoStatusOriginal = $documento->status_original;
                $nuevoEstadoVisible = $documento->estado_visible;
                $cantidadFactoriesRestantes = $documento->factories()->count();

                MovimientoDocumento::create([
                    'documento_financiero_id' => $documento->id,
                    'user_id' => Auth::id(),
                    'tipo_movimiento' => 'Eliminación de Factoring',
                    'descripcion' => "Se eliminó el Factoring ID {$datosAnteriores['factory_id']} del documento folio {$documento->folio}.",
                    'datos_anteriores' => array_merge($datosAnteriores, [
                        'estado_anterior' => $estadoAnterior,
                        'status_original_anterior' => $statusOriginalAnterior,
                        'saldo_anterior' => $saldoAnterior,
                        'cantidad_factoring_antes' => $cantidadFactoriesAntes,
                    ]),
                    'datos_nuevos' => [
                        'nuevo_estado_manual' => $nuevoEstadoManual,
                        'nuevo_status_original' => $nuevoStatusOriginal,
                        'nuevo_estado_visible' => $nuevoEstadoVisible,
                        'cantidad_factoring_restantes' => $cantidadFactoriesRestantes,
                        'saldo_actual' => $saldoActual,
                    ],
                ]);
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withErrors([
                    'factory' => 'Ocurrió un error al eliminar Factoring.',
                ])
                ->withInput();
        }

        return back()->with(
            'success',
            'Factoring eliminado, saldo recalculado y estado actualizado correctamente.'
        );
    }



    /**
     * Registrar Factoring masivo para documentos financieros CxC.
     *
     * Cada documento seleccionado puede tener Factorings anteriores, siempre que
     * continúe teniendo saldo pendiente y no posea movimientos de cierre.
     */
    public function storeMasivo(Request $request)
    {
        $validated = $request->validate([
            'documentos' => 'required|array|min:1',

            'banco_id' => 'required|string|max:255',
            'banco_otro' => 'nullable|string|max:255',
            'cesion' => 'required|string|max:255',
            'fecha_factory' => 'required|date',
            'comision_total' => 'required|integer|min:0',

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
        | No se rechazan documentos con Factoring anterior. Solo se excluyen
        | documentos cerrados, sin saldo o de tipo NC / ND.
        |--------------------------------------------------------------------------
        */
        $documentos = DocumentoFinanciero::with([
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
                $errores[] = "{$identificador}: la suma del monto líquido y el monto no anticipado no puede ser mayor al saldo pendiente actual del documento.";
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
                | Cada documento toma como monto cedido su saldo pendiente vigente,
                | incluso cuando ya tenga uno o más Factorings anteriores.
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
                        $documento->pagos()->exists() ||
                        $documento->prontoPagos()->exists()
                    ) {
                        throw ValidationException::withMessages([
                            'factory_masivo' => "El documento folio {$documento->folio} ya no cumple las condiciones para registrar Factoring.",
                        ]);
                    }

                    $data = $items[$documentoId];

                    $monto = (int) $documento->saldo_pendiente;
                    $saldoLiquido = (int) $data['saldo_liquido'];
                    $montoNoAnticipado = (int) $data['monto_no_anticipado'];

                    if (($saldoLiquido + $montoNoAnticipado) > $monto) {
                        throw ValidationException::withMessages([
                            'factory_masivo' => "La suma del monto líquido y el monto no anticipado del documento folio {$documento->folio} no puede ser mayor al saldo pendiente actual del documento.",
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
                        'cantidad_factoring_anteriores' => $documento->factories()->count(),
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Totales generales de la operación
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
                | Segunda pasada: crear registros, recalcular saldo y auditar
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
                        | Campo aún obligatorio en base de datos
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
                    | Recalcular desde movimientos reales existentes
                    |--------------------------------------------------------------------------
                    */
                    $saldoActual = $documento->recalcularSaldoPendiente();

                    $documento->update([
                        'status' => 'Factory',
                        'fecha_estado_manual' => now(),
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
                            'cantidad_factoring_anteriores' => $calculo['cantidad_factoring_anteriores'],
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

                            'cantidad_factoring_actual' => $calculo['cantidad_factoring_anteriores'] + 1,
                            'nuevo_estado' => 'Factory',
                            'saldo_actual' => $saldoActual,
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