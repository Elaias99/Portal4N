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
use App\Services\Ventas\Factoring\CesionFactoryService;
use Illuminate\Validation\ValidationException;

class FactoryController extends Controller
{



    /**
     * Listado informativo de registros Factoring asociados a documentos CxC.
     *
     * Presenta la información almacenada agrupada por mes y por cesión Factoring,
     * sin modificar ni recalcular el saldo pendiente de los documentos.
     *
     * La unidad principal de visualización es:
     * cesion + banco_id.
     *
     * Importante:
     * - No se elimina ninguna estructura existente.
     * - Se mantienen documentos, movimientos y totales actuales.
     * - Se agrega una estructura preparada para mostrar documentos únicos con
     *   todos sus movimientos Factoring reales.
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
        */
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin    = $request->input('fecha_fin');

        $perPage = 10;

        /*
        |--------------------------------------------------------------------------
        | Resolver período de operación
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
        | No se filtra por fecha aquí para no partir una cesión con movimientos
        | posteriores.
        |--------------------------------------------------------------------------
        */
        $query = FactoryRegistro::query()
            ->with([
                'documentoFinanciero:id,empresa_id,tipo_documento_id,folio,razon_social,rut_cliente,status,status_original,monto_total,saldo_pendiente',
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
        */
        $cesiones = $registros
            ->groupBy(function ($factory) {
                return implode('|', [
                    (string) ($factory->cesion ?? 'sin-cesion'),
                    (string) ($factory->banco_id ?? 'sin-banco'),
                ]);
            })
            ->map(function ($registrosCesion, $claveCesion) {
                /*
                |--------------------------------------------------------------------------
                | Orden común para registros Factoring
                |--------------------------------------------------------------------------
                */
                $ordenarFactory = function ($factory): string {
                    $fecha = $factory->fecha_factory
                        ? $factory->fecha_factory->format('Y-m-d')
                        : '0000-00-00';

                    return $fecha . '-' . str_pad((string) $factory->id, 12, '0', STR_PAD_LEFT);
                };

                $registrosOrdenados = collect($registrosCesion)
                    ->sortBy($ordenarFactory)
                    ->values();

                $factoryBase = $registrosOrdenados->first();
                $factoryUltimo = $registrosOrdenados->last();

                /*
                |--------------------------------------------------------------------------
                | Movimientos internos de la cesión por fecha
                |--------------------------------------------------------------------------
                | Se conserva la estructura existente.
                |--------------------------------------------------------------------------
                */
                $movimientos = $registrosOrdenados
                    ->groupBy(function ($factory) {
                        return $factory->fecha_factory
                            ? $factory->fecha_factory->format('Y-m-d')
                            : 'sin-fecha';
                    })
                    ->map(function ($documentosMovimiento, $fechaClave) {
                        $documentosMovimiento = collect($documentosMovimiento)
                            ->sortBy(function ($factory) {
                                $folio = (string) ($factory->documentoFinanciero?->folio ?? '');

                                return str_pad($folio, 20, '0', STR_PAD_LEFT);
                            })
                            ->values();

                        $factoryMovimientoBase = $documentosMovimiento->first();
                        $fechaOperacion = $factoryMovimientoBase?->fecha_factory;

                        $montoDocumentosMovimiento = (int) $documentosMovimiento->sum(function ($factory) {
                            return (int) ($factory->monto ?? 0);
                        });

                        $montoAnticipadoMovimiento = (int) $documentosMovimiento->sum(function ($factory) {
                            return (int) ($factory->saldo_liquido ?? 0);
                        });

                        $montoNoAnticipadoMovimiento = (int) $documentosMovimiento->sum(function ($factory) {
                            return (int) ($factory->monto_no_anticipado ?? 0);
                        });

                        $diferenciaPrecioMovimiento = (int) $documentosMovimiento->sum(function ($factory) {
                            return (int) ($factory->diferencia_precio ?? 0);
                        });

                        $montoLiquidoMovimiento = $montoAnticipadoMovimiento + $diferenciaPrecioMovimiento;
                        $precioCompraMovimiento = $montoLiquidoMovimiento;

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

                            'banco' => $factoryMovimientoBase?->banco,
                            'usuario' => $factoryMovimientoBase?->usuario,

                            'cantidad_documentos' => $documentosMovimiento->count(),
                            'cantidad_documentos_unicos' => $documentosMovimiento
                                ->pluck('documento_financiero_id')
                                ->unique()
                                ->count(),

                            'monto_documentos' => $montoDocumentosMovimiento,
                            'monto_anticipado' => $montoAnticipadoMovimiento,
                            'monto_no_anticipado' => $montoNoAnticipadoMovimiento,
                            'diferencia_precio' => $diferenciaPrecioMovimiento,
                            'monto_liquido' => $montoLiquidoMovimiento,
                            'precio_compra' => $precioCompraMovimiento,

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

                /*
                |--------------------------------------------------------------------------
                | Documentos únicos de la cesión con movimientos reales
                |--------------------------------------------------------------------------
                | Esta es la estructura nueva importante.
                |
                | Un documento aparece una sola vez, pero conserva todos sus registros
                | Factory en "movimientos". Así el folio 1356 puede mostrar sus 2
                | movimientos sin duplicar el documento ni ocultar el segundo.
                |--------------------------------------------------------------------------
                */
                $documentosDetalle = $registrosOrdenados
                    ->groupBy(function ($factory) {
                        return $factory->documento_financiero_id
                            ? (string) $factory->documento_financiero_id
                            : 'factory-sin-documento-' . $factory->id;
                    })
                    ->map(function ($registrosDocumento) use ($ordenarFactory) {
                        $registrosDocumento = collect($registrosDocumento)
                            ->sortBy($ordenarFactory)
                            ->values();

                        $factoryDocumentoBase = $registrosDocumento->first();
                        $factoryDocumentoUltimo = $registrosDocumento->last();

                        $documento = $factoryDocumentoUltimo?->documentoFinanciero
                            ?? $factoryDocumentoBase?->documentoFinanciero;

                        $movimientosDocumento = $registrosDocumento
                            ->map(function ($factory, $index) {
                                return [
                                    'numero_movimiento' => $index + 1,
                                    'factory_id' => $factory->id,
                                    'fecha_factory' => $factory->fecha_factory,
                                    'banco' => $factory->banco,
                                    'usuario' => $factory->usuario,

                                    /*
                                    |--------------------------------------------------------------------------
                                    | Valores reales del movimiento
                                    |--------------------------------------------------------------------------
                                    */
                                    'monto_cedido' => (int) ($factory->monto ?? 0),
                                    'monto_anticipado' => (int) ($factory->saldo_liquido ?? 0),
                                    'monto_no_anticipado' => (int) ($factory->monto_no_anticipado ?? 0),
                                    'diferencia_precio' => (int) ($factory->diferencia_precio ?? 0),
                                    'comision_total' => (int) ($factory->comision_total ?? 0),
                                    'monto_a_recibir' => (int) ($factory->monto_a_recibir ?? 0),

                                    /*
                                    |--------------------------------------------------------------------------
                                    | Referencia original completa
                                    |--------------------------------------------------------------------------
                                    */
                                    'registro' => $factory,
                                ];
                            })
                            ->values();

                        return [
                            'documento_financiero_id' => $factoryDocumentoBase?->documento_financiero_id,
                            'documento' => $documento,

                            /*
                            |--------------------------------------------------------------------------
                            | Compatibilidad con lo que ya existía
                            |--------------------------------------------------------------------------
                            */
                            'factory_base' => $factoryDocumentoBase,
                            'factory_ultimo' => $factoryDocumentoUltimo,
                            'factory' => $factoryDocumentoUltimo ?? $factoryDocumentoBase,

                            'cantidad_movimientos' => $registrosDocumento->count(),
                            'cantidad_movimientos_documento' => $registrosDocumento->count(),

                            /*
                            |--------------------------------------------------------------------------
                            | Valores base del comprobante del documento
                            |--------------------------------------------------------------------------
                            | Se mantienen para que no se duplique el monto original del folio.
                            |--------------------------------------------------------------------------
                            */
                            'monto_documento' => (int) ($factoryDocumentoBase?->monto ?? 0),
                            'monto_original' => (int) ($factoryDocumentoBase?->monto ?? 0),
                            'monto_no_anticipado_base' => (int) ($factoryDocumentoBase?->monto_no_anticipado ?? 0),
                            'monto_anticipado_base' => (int) ($factoryDocumentoBase?->saldo_liquido ?? 0),
                            'diferencia_precio_base' => (int) ($factoryDocumentoBase?->diferencia_precio ?? 0),

                            /*
                            |--------------------------------------------------------------------------
                            | Último movimiento del documento
                            |--------------------------------------------------------------------------
                            | Saldo después final:
                            |   corresponde al saldo que queda después del último movimiento Factoring
                            |   del documento dentro de esta cesión.
                            |
                            | Importante:
                            |   Si el documento tiene más de un movimiento, NO se suman todos los saldos
                            |   después. Solo se considera el resultado final del último movimiento.
                            |--------------------------------------------------------------------------
                            */
                            'ultimo_monto_cedido' => (int) ($factoryDocumentoUltimo?->monto ?? 0),
                            'ultimo_monto_anticipado' => (int) ($factoryDocumentoUltimo?->saldo_liquido ?? 0),
                            'ultimo_monto_no_anticipado' => (int) ($factoryDocumentoUltimo?->monto_no_anticipado ?? 0),
                            'ultima_diferencia_precio' => (int) ($factoryDocumentoUltimo?->diferencia_precio ?? 0),
                            'saldo_despues_final' => (int) ($factoryDocumentoUltimo?->diferencia_precio ?? 0),

                            /*
                            |--------------------------------------------------------------------------
                            | Totales acumulados de movimientos del documento
                            |--------------------------------------------------------------------------
                            | No sustituyen al monto original; quedan disponibles solo para
                            | mostrar trazabilidad si la vista lo necesita.
                            |--------------------------------------------------------------------------
                            */
                            'total_monto_cedido_movimientos' => (int) $movimientosDocumento->sum('monto_cedido'),
                            'total_monto_anticipado_movimientos' => (int) $movimientosDocumento->sum('monto_anticipado'),
                            'total_monto_no_anticipado_movimientos' => (int) $movimientosDocumento->sum('monto_no_anticipado'),
                            'total_diferencia_precio_movimientos' => (int) $movimientosDocumento->sum('diferencia_precio'),

                            /*
                            |--------------------------------------------------------------------------
                            | Movimientos reales del documento dentro de la cesión
                            |--------------------------------------------------------------------------
                            */
                            'movimientos' => $movimientosDocumento,
                        ];
                    })
                    ->sortBy(function ($item) {
                        $folio = (string) ($item['documento']?->folio ?? '');

                        return str_pad($folio, 20, '0', STR_PAD_LEFT);
                    })
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
                | Totales visibles tipo comprobante
                |--------------------------------------------------------------------------
                | Se calculan desde documentos únicos, tomando el registro base de cada
                | documento. Así no se suma dos veces el mismo folio.
                |--------------------------------------------------------------------------
                */
                $montoDocumentos = (int) $documentosDetalle->sum('monto_documento');
                $montoAnticipado = (int) $documentosDetalle->sum('monto_anticipado_base');
                $montoNoAnticipado = (int) $documentosDetalle->sum('monto_no_anticipado_base');
                $diferenciaPrecio = (int) $documentosDetalle->sum('diferencia_precio_base');

                /*
                |--------------------------------------------------------------------------
                | Saldo después consolidado de la cesión
                |--------------------------------------------------------------------------
                | Se calcula por documento único.
                |
                | Si un documento tiene más de un movimiento Factoring, solo se considera
                | el saldo resultante del último movimiento del documento.
                |
                | Ejemplo:
                |   Folio 1356:
                |       Mov. 1 deja 65.544
                |       Mov. 2 deja 33.576
                |       Se considera 33.576
                |
                |   Folio 1362:
                |       Mov. 1 deja 1.509.747
                |       Se considera 1.509.747
                |--------------------------------------------------------------------------
                */
                $saldoDespues = (int) $documentosDetalle->sum('saldo_despues_final');

                $montoLiquido = $montoAnticipado + $diferenciaPrecio;
                $precioCompra = $montoLiquido;

                /*
                |--------------------------------------------------------------------------
                | Comisión y monto a recibir visibles
                |--------------------------------------------------------------------------
                | Se mantiene la lógica existente: se toman desde el primer movimiento
                | de la cesión. No se elimina ningún total de trazabilidad.
                |--------------------------------------------------------------------------
                */
                $movimientoBaseCesion = $movimientos->first();

                $comisionTotal = (int) ($movimientoBaseCesion['comision_total'] ?? 0);
                $montoARecibir = (int) ($movimientoBaseCesion['monto_a_recibir'] ?? 0);

                /*
                |--------------------------------------------------------------------------
                | Totales de trazabilidad de todos los movimientos
                |--------------------------------------------------------------------------
                */
                $montoDocumentosMovimientos = (int) $movimientos->sum('monto_documentos');
                $montoAnticipadoMovimientos = (int) $movimientos->sum('monto_anticipado');
                $montoNoAnticipadoMovimientos = (int) $movimientos->sum('monto_no_anticipado');
                $diferenciaPrecioMovimientos = (int) $movimientos->sum('diferencia_precio');

                $comisionTotalMovimientos = (int) $movimientos->sum(function ($movimiento) {
                    return (int) ($movimiento['comision_total'] ?? 0);
                });

                $montoARecibirMovimientos = (int) $movimientos->sum(function ($movimiento) {
                    return (int) ($movimiento['monto_a_recibir'] ?? 0);
                });

                return [
                    'clave_cesion' => $claveCesion,
                    'mes_clave' => $mesClave,
                    'mes_etiqueta' => $mesEtiqueta,

                    'cesion' => $factoryBase?->cesion,
                    'banco' => $factoryBase?->banco,

                    /*
                    |--------------------------------------------------------------------------
                    | Usuario informativo
                    |--------------------------------------------------------------------------
                    */
                    'usuario' => $factoryUltimo?->usuario,

                    'fecha_inicio' => $fechaInicioCesion,
                    'fecha_ultimo_movimiento' => $fechaUltimoMovimiento,

                    'cantidad_movimientos' => $movimientos->count(),

                    /*
                    |--------------------------------------------------------------------------
                    | Cantidades
                    |--------------------------------------------------------------------------
                    */
                    'cantidad_documentos' => $registrosOrdenados->count(),
                    'cantidad_documentos_unicos' => $documentosDetalle->count(),

                    /*
                    |--------------------------------------------------------------------------
                    | Totales visibles tipo comprobante
                    |--------------------------------------------------------------------------
                    */
                    'monto_documentos' => $montoDocumentos,
                    'monto_anticipado' => $montoAnticipado,
                    'monto_no_anticipado' => $montoNoAnticipado,
                    'diferencia_precio' => $diferenciaPrecio,

                    /*
                    |--------------------------------------------------------------------------
                    | Saldo después
                    |--------------------------------------------------------------------------
                    | Saldo final consolidado de los documentos únicos de la cesión.
                    | No es suma de todos los movimientos; es suma del último saldo después
                    | de cada documento.
                    |--------------------------------------------------------------------------
                    */
                    'saldo_despues' => $saldoDespues,

                    'monto_liquido' => $montoLiquido,
                    'precio_compra' => $precioCompra,
                    'comision_total' => $comisionTotal,
                    'monto_a_recibir' => $montoARecibir,

                    /*
                    |--------------------------------------------------------------------------
                    | Totales de todos los movimientos, solo trazabilidad
                    |--------------------------------------------------------------------------
                    */
                    'monto_documentos_movimientos' => $montoDocumentosMovimientos,
                    'monto_anticipado_movimientos' => $montoAnticipadoMovimientos,
                    'monto_no_anticipado_movimientos' => $montoNoAnticipadoMovimientos,
                    'diferencia_precio_movimientos' => $diferenciaPrecioMovimientos,
                    'comision_total_movimientos' => $comisionTotalMovimientos,
                    'monto_a_recibir_movimientos' => $montoARecibirMovimientos,

                    'valores_globales_consistentes' => $movimientos
                        ->every(fn ($movimiento) => $movimiento['valores_globales_consistentes']),

                    /*
                    |--------------------------------------------------------------------------
                    | Movimientos reales por fecha
                    |--------------------------------------------------------------------------
                    */
                    'movimientos' => $movimientos,

                    /*
                    |--------------------------------------------------------------------------
                    | Registros Factory originales para filtros y compatibilidad
                    |--------------------------------------------------------------------------
                    */
                    'documentos' => $registrosOrdenados,

                    /*
                    |--------------------------------------------------------------------------
                    | Documentos únicos preparados para la vista nueva
                    |--------------------------------------------------------------------------
                    */
                    'documentos_detalle' => $documentosDetalle,
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Filtro por período de operación
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
     * Un documento puede registrar un primer Factoring como operación vigente.
     * Al registrar un segundo movimiento Factoring, se cierra la operación
     * completa del documento.
     */
    public function store(Request $request, DocumentoFinanciero $documento)
    {
        /*
        |--------------------------------------------------------------------------
        | Validación dinámica
        |--------------------------------------------------------------------------
        | Primer Factoring:
        |   Se mantiene el formulario completo actual.
        |
        | Segundo Factoring:
        |   Solo se solicita fecha, monto a descontar y se muestra saldo resultante.
        |--------------------------------------------------------------------------
        */
        $tieneFactoryPrevioAntesDeValidar = $documento->factories()->exists();

        $rules = [
            'fecha_factory' => 'required|date',
        ];

        $messages = [
            'fecha_factory.required' => 'Debe ingresar la fecha de operación Factoring.',
            'fecha_factory.date' => 'La fecha de operación Factoring no es válida.',
        ];

        if ($tieneFactoryPrevioAntesDeValidar) {
            $rules['monto_descuento'] = 'required|integer|min:1';

            $messages['monto_descuento.required'] = 'Debe ingresar el monto a descontar.';
            $messages['monto_descuento.integer'] = 'El monto a descontar debe ser un número entero.';
            $messages['monto_descuento.min'] = 'El monto a descontar debe ser mayor a cero.';
        } else {
            $rules = array_merge($rules, [
                'banco_id' => 'required|string|max:255',
                'banco_otro' => 'nullable|required_if:banco_id,__otro__|string|max:255',

                'cesion' => 'required|string|max:100',
                'comision_total' => 'required|integer|min:0',

                'saldo_liquido' => 'required|integer|min:0',
                'monto_no_anticipado' => 'required|integer|min:0',
            ]);

            $messages = array_merge($messages, [
                'banco_id.required' => 'Debe seleccionar el banco o entidad Factoring.',

                'banco_otro.required_if' => 'Debe ingresar el nombre del banco o entidad Factoring.',
                'banco_otro.max' => 'El nombre del banco o entidad Factoring no puede superar los 255 caracteres.',

                'cesion.required' => 'Debe ingresar la cesión del Factoring.',
                'cesion.max' => 'La cesión del Factoring no puede superar los 100 caracteres.',

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
        }

        $validated = $request->validate($rules, $messages);

        try {
            DB::transaction(function () use ($validated, $documento) {
                /** @var CesionFactoryService $cesionFactoryService */
                $cesionFactoryService = app(CesionFactoryService::class);

                /*
                |--------------------------------------------------------------------------
                | Bloquear documento y trabajar con su saldo vigente
                |--------------------------------------------------------------------------
                */
                $documentoBloqueado = DocumentoFinanciero::whereKey($documento->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $factoriesBloqueados = FactoryRegistro::where('documento_financiero_id', $documentoBloqueado->id)
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $factoriesExistentes = $factoriesBloqueados->count();
                $operacionFactoryCerrada = $factoriesBloqueados
                    ->contains(fn ($factory) => $factory->estado_operacion === 'Cerrada');

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
                | Operación Factoring cerrada
                |--------------------------------------------------------------------------
                */
                if ($operacionFactoryCerrada || $factoriesExistentes >= 2) {
                    throw ValidationException::withMessages([
                        'factory' => 'No se puede registrar otro Factoring porque la operación ya se encuentra cerrada.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Movimientos de cierre
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

                if ($monto <= 0) {
                    throw ValidationException::withMessages([
                        'factory' => 'No se puede registrar Factoring porque el documento no tiene saldo pendiente.',
                    ]);
                }

                $estadoAnterior = $documentoBloqueado->status;
                $statusOriginalAnterior = $documentoBloqueado->status_original;
                $cantidadFactoriesAnteriores = $factoriesExistentes;

                $estadosOperacionAnteriores = $factoriesBloqueados
                    ->mapWithKeys(fn ($factory) => [$factory->id => $factory->estado_operacion])
                    ->toArray();

                /*
                |--------------------------------------------------------------------------
                | Segundo Factoring
                |--------------------------------------------------------------------------
                | No se piden banco, cesión, comisión ni monto no anticipado.
                | Se heredan banco, cesión y cabecera desde el último Factoring existente.
                |--------------------------------------------------------------------------
                */
                if ($factoriesExistentes > 0) {
                    if (!array_key_exists('monto_descuento', $validated)) {
                        throw ValidationException::withMessages([
                            'monto_descuento' => 'Debe ingresar el monto a descontar.',
                        ]);
                    }

                    $factoryReferencia = $factoriesBloqueados
                        ->sortByDesc(fn ($factory) => ($factory->created_at?->format('Y-m-d H:i:s') ?? '') . '-' . str_pad((string) $factory->id, 12, '0', STR_PAD_LEFT))
                        ->first();

                    $factoryReferencia?->loadMissing([
                        'banco',
                        'cesionFactory',
                    ]);

                    if (!$factoryReferencia) {
                        throw ValidationException::withMessages([
                            'factory' => 'No se encontró una operación Factoring anterior para usar como referencia.',
                        ]);
                    }

                    $bancoSeleccionado = $factoryReferencia->banco;

                    if (!$bancoSeleccionado) {
                        throw ValidationException::withMessages([
                            'factory' => 'No se encontró el banco o entidad Factoring de la operación anterior.',
                        ]);
                    }

                    $montoDescuento = (int) $validated['monto_descuento'];

                    if ($montoDescuento > $monto) {
                        throw ValidationException::withMessages([
                            'monto_descuento' => 'El monto a descontar no puede ser mayor al saldo pendiente actual del documento.',
                        ]);
                    }

                    $saldoLiquido = $montoDescuento;
                    $montoNoAnticipado = 0;
                    $diferenciaPrecio = max($monto - $montoDescuento, 0);

                    $montoAnticipado = $saldoLiquido;

                    $montoLiquido = $montoAnticipado
                        + $diferenciaPrecio;

                    $precioCompra = $montoLiquido;

                    $comisionTotal = 0;
                    $montoARecibir = $montoDescuento;

                    $cesion = trim((string) ($factoryReferencia->cesion ?? ''));
                    $fechaFactory = $validated['fecha_factory'];

                    /*
                    |--------------------------------------------------------------------------
                    | Resolver cabecera de cesión para segundo movimiento
                    |--------------------------------------------------------------------------
                    | Si el Factoring anterior es legado y no tiene cesion_factoring_id,
                    | se crea o recupera la cabecera y se asocia.
                    |--------------------------------------------------------------------------
                    */
                    $cesionFactory = $factoryReferencia->cesionFactory;

                    if (!$cesionFactory) {
                        $cesionFactory = $cesionFactoryService->resolverOCrear([
                            'cesion' => $cesion,
                            'banco_id' => $bancoSeleccionado->id,
                            'fecha_operacion' => $factoryReferencia->fecha_factory,
                            'comision_total' => (int) ($factoryReferencia->comision_total ?? 0),
                            'monto_a_recibir' => (int) ($factoryReferencia->monto_a_recibir ?? 0),
                            'estado_operacion' => 'Vigente',
                            'user_id' => Auth::id(),
                        ]);

                        $cesionFactoryService->asociarFactory(
                            factory: $factoryReferencia,
                            cesionFactory: $cesionFactory
                        );
                    }

                    $estadoOperacionNuevoFactory = 'Cerrada';

                    $tipoMovimiento = 'Registro de Factoring';
                    $descripcionMovimiento = "El documento folio {$documentoBloqueado->folio} registró un nuevo movimiento Factoring. Saldo anterior: {$monto}. Monto descontado: {$montoDescuento}. Saldo resultante: {$diferenciaPrecio}. La operación Factoring quedó cerrada.";
                } else {
                    /*
                    |--------------------------------------------------------------------------
                    | Primer Factoring
                    |--------------------------------------------------------------------------
                    | Se conserva la lógica completa existente.
                    | La operación queda Vigente.
                    |--------------------------------------------------------------------------
                    */
                    $saldoLiquido = (int) $validated['saldo_liquido'];
                    $montoNoAnticipado = (int) $validated['monto_no_anticipado'];
                    $comisionTotal = (int) $validated['comision_total'];

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

                    $cesion = trim($validated['cesion']);

                    /*
                    |--------------------------------------------------------------------------
                    | Resolver o crear cabecera de cesión
                    |--------------------------------------------------------------------------
                    | Si la cesión ya existe para el mismo banco, se reutilizan sus datos
                    | generales.
                    |--------------------------------------------------------------------------
                    */
                    $cesionFactory = $cesionFactoryService->resolverOCrear([
                        'cesion' => $cesion,
                        'banco_id' => $bancoSeleccionado->id,
                        'fecha_operacion' => $validated['fecha_factory'],
                        'comision_total' => $comisionTotal,
                        'monto_a_recibir' => $montoARecibir,
                        'estado_operacion' => 'Vigente',
                        'user_id' => Auth::id(),
                    ]);

                    if ($cesionFactory->estado_operacion === 'Cerrada') {
                        throw ValidationException::withMessages([
                            'factory' => 'No se puede asociar el documento porque la cesión Factoring seleccionada está cerrada.',
                        ]);
                    }

                    $cesionFactory->loadMissing('banco');

                    $bancoSeleccionado = $cesionFactory->banco ?? $bancoSeleccionado;
                    $cesion = $cesionFactory->cesion;
                    $fechaFactory = $cesionFactory->fecha_operacion ?? $validated['fecha_factory'];
                    $comisionTotal = (int) ($cesionFactory->comision_total ?? $comisionTotal);
                    $montoARecibir = (int) ($cesionFactory->monto_a_recibir ?? $montoARecibir);

                    $estadoOperacionNuevoFactory = 'Vigente';

                    $tipoMovimiento = 'Registro de Factoring';
                    $descripcionMovimiento = "El documento folio {$documentoBloqueado->folio} fue marcado como Factoring. Monto: {$monto}. Monto líquido: {$saldoLiquido}. Monto no anticipado: {$montoNoAnticipado}. Diferencia de precio: {$diferenciaPrecio}. Comisión total: {$comisionTotal}. Monto a recibir: {$montoARecibir}. La operación Factoring quedó vigente.";
                }

                $factory = FactoryRegistro::create([
                    'documento_financiero_id' => $documentoBloqueado->id,
                    'cesion_factoring_id' => $cesionFactory->id,
                    'banco_id' => $bancoSeleccionado->id,

                    /*
                    |--------------------------------------------------------------------------
                    | Campo aún obligatorio en base de datos
                    |--------------------------------------------------------------------------
                    | No tiene captura en el flujo vigente.
                    |--------------------------------------------------------------------------
                    */
                    'rut_factory' => '',

                    'cesion' => $cesion,
                    'fecha_factory' => $fechaFactory,

                    'monto' => $monto,
                    'saldo_liquido' => $saldoLiquido,
                    'monto_no_anticipado' => $montoNoAnticipado,
                    'diferencia_precio' => $diferenciaPrecio,

                    'comision_total' => $comisionTotal,
                    'monto_a_recibir' => $montoARecibir,

                    'estado_operacion' => $estadoOperacionNuevoFactory,

                    'user_id' => Auth::id(),
                ]);

                $factory = $cesionFactoryService->asociarFactory(
                    factory: $factory,
                    cesionFactory: $cesionFactory
                );

                /*
                |--------------------------------------------------------------------------
                | Cierre de operación completa
                |--------------------------------------------------------------------------
                | Si este es el segundo movimiento, todos los registros Factoring
                | asociados al documento quedan Cerrada.
                |--------------------------------------------------------------------------
                */
                if ($factoriesExistentes > 0) {
                    FactoryRegistro::where('documento_financiero_id', $documentoBloqueado->id)
                        ->update([
                            'estado_operacion' => 'Cerrada',
                        ]);

                    $factory->refresh();
                }

                /*
                |--------------------------------------------------------------------------
                | Sincronizar estado general de la cabecera de cesión
                |--------------------------------------------------------------------------
                | Si al menos un documento asociado sigue Vigente, la cesión queda Vigente.
                | Si todos sus Factoring están Cerrada, la cesión queda Cerrada.
                |--------------------------------------------------------------------------
                */
                $cesionFactory = $cesionFactoryService->sincronizarEstadoOperacion($cesionFactory);

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

                $estadosOperacionNuevos = FactoryRegistro::where('documento_financiero_id', $documentoBloqueado->id)
                    ->pluck('estado_operacion', 'id')
                    ->toArray();

                MovimientoDocumento::create([
                    'documento_financiero_id' => $documentoBloqueado->id,
                    'user_id' => Auth::id(),
                    'tipo_movimiento' => $tipoMovimiento,
                    'descripcion' => $descripcionMovimiento,
                    'datos_anteriores' => [
                        'estado' => $estadoAnterior,
                        'status_original' => $statusOriginalAnterior,
                        'saldo_anterior' => $saldoAnterior,
                        'cantidad_factoring_anteriores' => $cantidadFactoriesAnteriores,
                        'estados_operacion_anteriores' => $estadosOperacionAnteriores,
                    ],
                    'datos_nuevos' => [
                        'factory_id' => $factory->id,
                        'cesion_factoring_id' => $factory->cesion_factoring_id,
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

                        'estado_operacion' => $factory->estado_operacion,
                        'estado_operacion_cesion' => $cesionFactory->estado_operacion,
                        'estados_operacion_actuales' => $estadosOperacionNuevos,

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
     * Eliminar un registro Factoring y restaurar saldo/estado de movimientos restantes.
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

                $estadosOperacionAntes = FactoryRegistro::where('documento_financiero_id', $documento->id)
                    ->lockForUpdate()
                    ->pluck('estado_operacion', 'id')
                    ->toArray();

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
                    'estado_operacion' => $factoryEliminar->estado_operacion,
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
                | Recalcular estado de operación de Factorings restantes
                |--------------------------------------------------------------------------
                | 0 registros restantes:
                |   no se actualiza nada.
                |
                | 1 registro restante:
                |   vuelve a Vigente.
                |
                | 2 o más registros restantes:
                |   permanecen Cerrada.
                |--------------------------------------------------------------------------
                */
                $factoriesRestantes = FactoryRegistro::where('documento_financiero_id', $documento->id)
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($factoriesRestantes->count() === 1) {
                    $factoriesRestantes->first()->update([
                        'estado_operacion' => 'Vigente',
                    ]);
                }

                if ($factoriesRestantes->count() >= 2) {
                    FactoryRegistro::where('documento_financiero_id', $documento->id)
                        ->update([
                            'estado_operacion' => 'Cerrada',
                        ]);
                }

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

                $estadosOperacionDespues = FactoryRegistro::where('documento_financiero_id', $documento->id)
                    ->pluck('estado_operacion', 'id')
                    ->toArray();

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
                        'estados_operacion_antes' => $estadosOperacionAntes,
                    ]),
                    'datos_nuevos' => [
                        'nuevo_estado_manual' => $nuevoEstadoManual,
                        'nuevo_status_original' => $nuevoStatusOriginal,
                        'nuevo_estado_visible' => $nuevoEstadoVisible,
                        'cantidad_factoring_restantes' => $cantidadFactoriesRestantes,
                        'estados_operacion_despues' => $estadosOperacionDespues,
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
        | No se rechazan documentos con Factoring anterior vigente.
        | Sí se excluyen documentos cerrados, sin saldo o de tipo NC / ND.
        |--------------------------------------------------------------------------
        */
        $documentos = DocumentoFinanciero::with([
                'pagos',
                'prontoPagos',
                'factories:id,documento_financiero_id,estado_operacion',
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

            $tieneOperacionFactoryCerrada = $documento->factories
                ->contains(fn ($factory) => $factory->estado_operacion === 'Cerrada');

            if ($tieneOperacionFactoryCerrada || $documento->factories->count() >= 2) {
                $errores[] = "{$identificador}: la operación Factoring ya se encuentra cerrada.";
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
                /** @var CesionFactoryService $cesionFactoryService */
                $cesionFactoryService = app(CesionFactoryService::class);

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
                | Cada documento toma como monto cedido su saldo pendiente vigente.
                | Si el documento ya tiene un Factoring vigente, este nuevo registro
                | cerrará la operación completa de ese documento.
                |--------------------------------------------------------------------------
                */
                $calculosPorDocumento = collect();

                $montoAnticipadoTotal = 0;
                $diferenciaPrecioTotal = 0;

                foreach ($documentoIds as $documentoId) {
                    $documento = DocumentoFinanciero::whereKey($documentoId)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $factoriesDocumento = FactoryRegistro::where('documento_financiero_id', $documento->id)
                        ->orderBy('created_at')
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();

                    $factoriesExistentes = $factoriesDocumento->count();

                    $tieneOperacionFactoryCerrada = $factoriesDocumento
                        ->contains(fn ($factory) => $factory->estado_operacion === 'Cerrada');

                    if (
                        in_array((int) $documento->tipo_documento_id, [61, 56], true) ||
                        (int) $documento->saldo_pendiente <= 0 ||
                        $documento->pagos()->exists() ||
                        $documento->prontoPagos()->exists() ||
                        $tieneOperacionFactoryCerrada ||
                        $factoriesExistentes >= 2
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

                        'cantidad_factoring_anteriores' => $factoriesExistentes,
                        'estados_operacion_anteriores' => $factoriesDocumento
                            ->pluck('estado_operacion', 'id')
                            ->toArray(),

                        /*
                        |--------------------------------------------------------------------------
                        | Estado del nuevo movimiento Factoring
                        |--------------------------------------------------------------------------
                        | Sin Factoring previo:
                        |   nuevo registro queda Vigente.
                        |
                        | Con un Factoring previo:
                        |   este nuevo registro cierra la operación completa del documento.
                        |--------------------------------------------------------------------------
                        */
                        'estado_operacion_factory' => $factoriesExistentes > 0
                            ? 'Cerrada'
                            : 'Vigente',

                        'cerrar_operacion_documento' => $factoriesExistentes > 0,
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
                | Cabecera de cesión Factoring
                |--------------------------------------------------------------------------
                | A partir de ahora la cesión queda formalizada en cesiones_factoring.
                | Si ya existe para la misma cesión + banco, se reutiliza.
                |--------------------------------------------------------------------------
                */
                $cesionFactory = $cesionFactoryService->resolverOCrear([
                    'cesion' => $cesion,
                    'banco_id' => $banco->id,
                    'fecha_operacion' => $fechaFactory,
                    'comision_total' => $comisionTotal,
                    'monto_a_recibir' => $montoARecibir,
                    'estado_operacion' => 'Vigente',
                    'user_id' => Auth::id(),
                ]);

                $cesionFactory->loadMissing('banco');

                /*
                |--------------------------------------------------------------------------
                | Si la cabecera ya existía, se respetan sus datos generales.
                |--------------------------------------------------------------------------
                */
                $banco = $cesionFactory->banco ?? $banco;
                $cesion = $cesionFactory->cesion;
                $fechaFactory = $cesionFactory->fecha_operacion;
                $comisionTotal = (int) ($cesionFactory->comision_total ?? $comisionTotal);
                $montoARecibir = (int) ($cesionFactory->monto_a_recibir ?? $montoARecibir);

                /*
                |--------------------------------------------------------------------------
                | Segunda pasada: crear registros, asociar cesión, recalcular saldo y auditar
                |--------------------------------------------------------------------------
                */
                foreach ($calculosPorDocumento as $calculo) {
                    /** @var DocumentoFinanciero $documento */
                    $documento = $calculo['documento'];

                    $factory = FactoryRegistro::create([
                        'documento_financiero_id' => $documento->id,
                        'cesion_factoring_id' => $cesionFactory->id,
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
                        'estado_operacion' => $calculo['estado_operacion_factory'],
                        'user_id' => Auth::id(),
                    ]);

                    $factory = $cesionFactoryService->asociarFactory(
                        factory: $factory,
                        cesionFactory: $cesionFactory
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Cerrar operación completa del documento
                    |--------------------------------------------------------------------------
                    | Si este documento ya tenía un Factoring previo, este nuevo registro
                    | actúa como segundo movimiento y deja todos sus Factoring Cerrada.
                    |--------------------------------------------------------------------------
                    */
                    if ($calculo['cerrar_operacion_documento']) {
                        FactoryRegistro::where('documento_financiero_id', $documento->id)
                            ->update([
                                'estado_operacion' => 'Cerrada',
                            ]);

                        $factory->refresh();
                    }

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

                    $estadosOperacionActuales = FactoryRegistro::where('documento_financiero_id', $documento->id)
                        ->pluck('estado_operacion', 'id')
                        ->toArray();

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
                            'estados_operacion_anteriores' => $calculo['estados_operacion_anteriores'],
                        ],
                        'datos_nuevos' => [
                            'factory_id' => $factory->id,
                            'cesion_factoring_id' => $factory->cesion_factoring_id,
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

                            'estado_operacion' => $factory->estado_operacion,
                            'estados_operacion_actuales' => $estadosOperacionActuales,

                            'cantidad_factoring_actual' => $calculo['cantidad_factoring_anteriores'] + 1,
                            'nuevo_estado' => 'Factory',
                            'saldo_actual' => $saldoActual,
                        ],
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Sincronizar estado general de la cabecera de cesión
                |--------------------------------------------------------------------------
                */
                $cesionFactoryService->sincronizarEstadoOperacion($cesionFactory);
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