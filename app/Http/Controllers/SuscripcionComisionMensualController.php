<?php

namespace App\Http\Controllers;

use App\Models\Asignaciones;
use App\Models\SuscripcionComisionMensual;
use App\Models\SuscripcionProveedor;
use App\Models\SuscripcionTransportista;
use App\Models\SuscripcionCantidadMensual;
use App\Models\SuscripcionLiquidacionDetalle;
use App\Models\SuscripcionConceptoPagoVariable;
use App\Models\SuscripcionZona;
use App\Models\SuscripcionZonaDiaOperativo;
use App\Services\Suscripciones\SuscripcionGeneracionMensualService;
use App\Services\Suscripciones\SuscripcionAjusteMensualAplicacionService;
use App\Services\Suscripciones\SuscripcionAjusteMensualRegistroService;
use App\Services\Suscripciones\SuscripcionExcepcionFacturacionRegistroService;
use App\Services\Suscripciones\SuscripcionExcepcionFacturacionAplicacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class SuscripcionComisionMensualController extends Controller
{

    public function create(Request $request)
    {
        $anio = (int) $request->input(
            'anio',
            now()->year
        );

        $mes = (int) $request->input(
            'mes',
            now()->month
        );

        /*
        * Zonas activas que participarán en la matriz mensual.
        */
        $zonas = SuscripcionZona::query()
            ->where('activo', true)
            ->orderBy('numero_zona')
            ->get();

        /*
        * Sábados y domingos correspondientes al período.
        */
        $fechasFinSemana = $this->obtenerFechasFinSemana(
            $anio,
            $mes
        );

        /*
        * Estados previamente guardados para las zonas y fechas
        * del período seleccionado.
        */
        $diasOperativosGuardados =
            SuscripcionZonaDiaOperativo::query()
                ->whereIn(
                    'suscripcion_zona_id',
                    $zonas->pluck('id')
                )
                ->whereIn(
                    'fecha',
                    $fechasFinSemana->all()
                )
                ->get()
                ->keyBy(function (
                    SuscripcionZonaDiaOperativo $diaOperativo
                ) {
                    return
                        $diaOperativo->suscripcion_zona_id
                        . '|'
                        . $diaOperativo->fecha->format('Y-m-d');
                });

        /*
        * Estructura preparada para el formulario.
        *
        * Si todavía no existe un registro para una zona y fecha,
        * se muestra marcado por defecto.
        */
        $calendarioZonas = $zonas->map(
            function (SuscripcionZona $zona) use (
                $fechasFinSemana,
                $diasOperativosGuardados
            ) {
                $dias = $fechasFinSemana->map(
                    function (string $fecha) use (
                        $zona,
                        $diasOperativosGuardados
                    ) {
                        $clave =
                            $zona->id
                            . '|'
                            . $fecha;

                        $registro =
                            $diasOperativosGuardados->get($clave);

                        return [
                            'fecha' => $fecha,

                            'hubo_despacho' => $registro
                                ? (bool) $registro->hubo_despacho
                                : true,

                            'observacion' =>
                                $registro?->observacion,

                            'guardado' =>
                                $registro !== null,
                        ];
                    }
                );

                return [
                    'id' => $zona->id,
                    'numero_zona' => $zona->numero_zona,
                    'despacho' => $zona->despacho,
                    'dias' => $dias,
                ];
            }
        );

        /*
        * Período.
        */
        $periodoYaGenerado =
            SuscripcionLiquidacionDetalle::query()
                ->where('anio', $anio)
                ->where('mes', $mes)
                ->exists();

        /*
        * Permite distinguir entre:
        *
        * - un período todavía no configurado;
        * - un período completamente configurado;
        * - un período con información incompleta.
        */
        $totalCombinacionesEsperadas =
            $zonas->count()
            * $fechasFinSemana->count();

        $totalCombinacionesGuardadas =
            $diasOperativosGuardados->count();

        $calendarioZonasConfigurado =
            $totalCombinacionesEsperadas > 0
            && $totalCombinacionesGuardadas
                === $totalCombinacionesEsperadas;

        $calendarioZonasIncompleto =
            $totalCombinacionesGuardadas > 0
            && $totalCombinacionesGuardadas
                < $totalCombinacionesEsperadas;

        /*
        * Proveedores disponibles.
        */
        $proveedores =
            SuscripcionProveedor::with(
                'cobranzaCompra'
            )
                ->whereHas('cobranzaCompra')
                ->get()
                ->sortBy(
                    fn ($proveedor) =>
                        $proveedor->cobranzaCompra?->razon_social
                )
                ->values();

        /*
        * Transportistas disponibles.
        */
        $transportistas =
            SuscripcionTransportista::query()
                ->orderBy('nombre_transportista')
                ->get();

        /*
        * Cantidades variables mensuales.
        */
        $asignacionesCantidadMensual =
            Asignaciones::with([
                'suscripcionProveedor.cobranzaCompra',
                'transportista',
            ])
                ->where(
                    'tipo_asignacion',
                    'VARIABLE'
                )
                ->orderBy('codigo')
                ->get();

        /*
        * Asignaciones disponibles para novedades mensuales.
        *
        * Se excluyen todas las asignaciones técnicas:
        *
        * - COMISION
        * - CONTENEDOR_AJUSTE
        * - EXCEPCION_FACTURACION
        *
        * EXCEPCION_FACTURACION se genera internamente cuando una
        * ejecución puntual es trasladada a otro proveedor.
        */
        $asignacionesAjustesMensuales =
            Asignaciones::with([
                'suscripcionProveedor.cobranzaCompra',
                'transportista',
            ])
                ->whereNotIn(
                    'tipo_asignacion',
                    [
                        'COMISION',
                        'CONTENEDOR_AJUSTE',
                        'EXCEPCION_FACTURACION',
                    ]
                )
                ->orderBy('codigo')
                ->get();

        /*
        * Asignaciones fijas mensuales.
        */
        $asignacionesFijasMensuales =
            Asignaciones::with([
                'suscripcionProveedor.cobranzaCompra',
                'transportista',
            ])
                ->where(
                    'tipo_asignacion',
                    'FIJO_MENSUAL'
                )
                ->orderBy('codigo')
                ->get();

        /*
        * Conceptos configurados para pagos variables.
        */
        $conceptosPagoVariable =
            SuscripcionConceptoPagoVariable::query()
                ->where('activo', true)
                ->orderBy('orden')
                ->orderBy('nombre')
                ->get();

        return view(
            'suscripciones.comisiones_mensuales.create',
            compact(
                'anio',
                'mes',

                'zonas',
                'fechasFinSemana',
                'calendarioZonas',
                'calendarioZonasConfigurado',
                'calendarioZonasIncompleto',

                'proveedores',
                'transportistas',

                'asignacionesCantidadMensual',
                'asignacionesAjustesMensuales',
                'asignacionesFijasMensuales',

                'conceptosPagoVariable',
                'periodoYaGenerado'
            )
        );
    }



    public function store(Request $request, SuscripcionGeneracionMensualService $generacionMensualService, SuscripcionAjusteMensualRegistroService $ajusteMensualRegistroService, SuscripcionAjusteMensualAplicacionService $ajusteMensualAplicacionService, SuscripcionExcepcionFacturacionRegistroService $excepcionFacturacionRegistroService, SuscripcionExcepcionFacturacionAplicacionService $excepcionFacturacionAplicacionService) 
    {
        $data = $request->validate([
            'anio' => [
                'required',
                'integer',
                'min:2020',
                'max:2100',
            ],

            'mes' => [
                'required',
                'integer',
                'min:1',
                'max:12',
            ],

            /*
            * Calendario operativo de las zonas.
            */
            'zonas_operativas' => [
                'required',
                'array',
                'min:1',
            ],

            'zonas_operativas.*.suscripcion_zona_id' => [
                'required',
                'integer',
                'exists:suscripcion_zonas,id',
            ],

            'zonas_operativas.*.fecha' => [
                'required',
                'date_format:Y-m-d',
            ],

            'zonas_operativas.*.hubo_despacho' => [
                'required',
                'boolean',
            ],

            'zonas_operativas.*.observacion' => [
                'nullable',
                'string',
                'max:500',
            ],

            /*
            * Cantidad variable mensual.
            */
            'cantidad_mensual_asignacion_id' => [
                'nullable',
                'required_with:cantidad_mensual_cantidad',
                'exists:suscripcion_asignaciones,id',
            ],

            'cantidad_mensual_cantidad' => [
                'nullable',
                'required_with:cantidad_mensual_asignacion_id',
                'integer',
                'min:1',
            ],

            'cantidad_mensual_observacion' => [
                'nullable',
                'string',
                'max:1000',
            ],

            /*
            * Pagos adicionales.
            */
            'comisiones' => [
                'nullable',
                'array',
            ],

            'comisiones.*.suscripcion_proveedor_id' => [
                'required',
                'exists:suscripcion_proveedores,id',
            ],

            'comisiones.*.suscripcion_transportista_id' => [
                'required',
                'exists:suscripcion_transportistas,id',
            ],

            'comisiones.*.punto_1' => [
                'nullable',
                'string',
                'max:255',
            ],

            'comisiones.*.origen_gasto' => [
                'nullable',
                'string',
                'max:255',
            ],

            'comisiones.*.punto_2' => [
                'nullable',
                'string',
                'max:255',
            ],

            'comisiones.*.servicio' => [
                'nullable',
                'string',
                'max:255',
            ],

            'comisiones.*.costo' => [
                'required',
                'integer',
                'min:1',
            ],

            'comisiones.*.cantidad' => [
                'required',
                'integer',
                'min:1',
            ],

            'comisiones.*.observacion' => [
                'nullable',
                'string',
                'max:1000',
            ],

            /*
            * Novedades y ajustes mensuales.
            */
            'ajustes_mensuales' => [
                'nullable',
                'array',
            ],

            'ajustes_mensuales.*.tipo_ajuste' => [
                'required',
                'string',
                'max:50',
            ],

            'ajustes_mensuales.*.concepto_pago_variable_id' => [
                'nullable',
                'exists:suscripcion_conceptos_pago_variable,id',
            ],

            'ajustes_mensuales.*.concepto_pago_variable_manual' => [
                'nullable',
                'string',
                'max:150',
            ],

            'ajustes_mensuales.*.suscripcion_asignacion_id' => [
                'nullable',
                'exists:suscripcion_asignaciones,id',
            ],

            'ajustes_mensuales.*.suscripcion_proveedor_id' => [
                'nullable',
                'exists:suscripcion_proveedores,id',
            ],

            'ajustes_mensuales.*.suscripcion_transportista_id' => [
                'nullable',
                'exists:suscripcion_transportistas,id',
            ],

            'ajustes_mensuales.*.suscripcion_proveedor_facturacion_id' => [
                'nullable',
                'exists:suscripcion_proveedores,id',
            ],

            'ajustes_mensuales.*.suscripcion_transportista_override_id' => [
                'nullable',
                'exists:suscripcion_transportistas,id',
            ],

            'ajustes_mensuales.*.punto_1' => [
                'nullable',
                'string',
                'max:255',
            ],

            'ajustes_mensuales.*.origen_gasto' => [
                'nullable',
                'string',
                'max:255',
            ],

            'ajustes_mensuales.*.punto_2' => [
                'nullable',
                'string',
                'max:255',
            ],

            'ajustes_mensuales.*.codigo' => [
                'nullable',
                'string',
                'max:255',
            ],

            'ajustes_mensuales.*.servicio' => [
                'nullable',
                'string',
                'max:255',
            ],

            'ajustes_mensuales.*.tipo_documento' => [
                'nullable',
                'string',
                'max:255',
            ],

            'ajustes_mensuales.*.detalle_documento' => [
                'nullable',
                'string',
                'max:255',
            ],

            'ajustes_mensuales.*.detalle_impuesto' => [
                'nullable',
                'string',
                'max:255',
            ],

            'ajustes_mensuales.*.final' => [
                'nullable',
                'string',
                'max:255',
            ],

            'ajustes_mensuales.*.grupo_prefactura' => [
                'nullable',
                'string',
                'max:255',
            ],

            'ajustes_mensuales.*.costo' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'ajustes_mensuales.*.q_calendario' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'ajustes_mensuales.*.q_inasistencia' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'ajustes_mensuales.*.cantidad' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'ajustes_mensuales.*.total' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'ajustes_mensuales.*.observacion' => [
                'nullable',
                'string',
                'max:1000',
            ],

            /*
            * Excepciones de facturación por fecha.
            *
            * Representan una ejecución puntual de una ruta
            * cuya facturación cambia únicamente para una fecha.
            */
            'excepciones_facturacion' => [
                'nullable',
                'array',
            ],

            'excepciones_facturacion.*.suscripcion_asignacion_id' => [
                'required',
                'integer',
                'exists:suscripcion_asignaciones,id',
            ],

            'excepciones_facturacion.*.fecha' => [
                'required',
                'date_format:Y-m-d',
            ],

            'excepciones_facturacion.*.suscripcion_proveedor_facturacion_id' => [
                'required',
                'integer',
                'exists:suscripcion_proveedores,id',
            ],

            'excepciones_facturacion.*.suscripcion_transportista_override_id' => [
                'nullable',
                'integer',
                'exists:suscripcion_transportistas,id',
            ],

            /*
            * NULL significa utilizar el costo habitual
            * de la asignación original.
            */
            'excepciones_facturacion.*.costo' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'excepciones_facturacion.*.tipo_documento' => [
                'nullable',
                'string',
                'max:255',
            ],

            'excepciones_facturacion.*.detalle_documento' => [
                'nullable',
                'string',
                'max:255',
            ],

            'excepciones_facturacion.*.detalle_impuesto' => [
                'nullable',
                'string',
                'max:255',
            ],

            'excepciones_facturacion.*.final' => [
                'nullable',
                'string',
                'max:255',
            ],

            'excepciones_facturacion.*.observacion' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $anio = (int) $data['anio'];
        $mes = (int) $data['mes'];

        /*
        * Normalizar calendario zonal.
        */
        $zonasOperativas = collect(
            $data['zonas_operativas'] ?? []
        )
            ->map(function (array $diaOperativo) {
                $observacion =
                    isset($diaOperativo['observacion'])
                        ? trim(
                            (string)
                            $diaOperativo['observacion']
                        )
                        : null;

                return [
                    'suscripcion_zona_id' =>
                        (int) $diaOperativo[
                            'suscripcion_zona_id'
                        ],

                    'fecha' =>
                        $diaOperativo['fecha'],

                    'hubo_despacho' =>
                        (bool) (
                            (int)
                            $diaOperativo['hubo_despacho']
                        ),

                    'observacion' =>
                        $observacion !== ''
                            ? $observacion
                            : null,
                ];
            })
            ->values();

        /*
        * Verificar matriz completa de zonas × fechas.
        */
        $this->validarCalendarioZonas(
            $zonasOperativas,
            $anio,
            $mes
        );

        $codigoComision = 'COMISION';

        $comisiones = collect(
            $data['comisiones'] ?? []
        )->values();

        $ajustesMensuales = collect(
            $data['ajustes_mensuales'] ?? []
        )->values();

        /*
        * Excepciones por fecha.
        */
        $excepcionesFacturacion = collect(
            $data['excepciones_facturacion'] ?? []
        )->values();

        $debeGuardarCantidadMensual =
            !empty(
                $data['cantidad_mensual_asignacion_id']
            )
            && !empty(
                $data['cantidad_mensual_cantidad']
            );

        /*
        * Validar cantidad variable duplicada.
        */
        if ($debeGuardarCantidadMensual) {
            $existeCantidadMensual =
                SuscripcionCantidadMensual::query()
                    ->where(
                        'suscripcion_asignacion_id',
                        $data[
                            'cantidad_mensual_asignacion_id'
                        ]
                    )
                    ->where('anio', $anio)
                    ->where('mes', $mes)
                    ->exists();

            if ($existeCantidadMensual) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'cantidad_mensual_asignacion_id' =>
                            'Esta cantidad mensual ya existe para la asignación, año y mes seleccionado.',
                    ]);
            }
        }

        /*
        * Confirmar tipo VARIABLE.
        */
        if ($debeGuardarCantidadMensual) {
            $asignacionCantidadMensual =
                Asignaciones::find(
                    $data[
                        'cantidad_mensual_asignacion_id'
                    ]
                );

            if (
                !$asignacionCantidadMensual
                || $asignacionCantidadMensual
                    ->tipo_asignacion !== 'VARIABLE'
            ) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'cantidad_mensual_asignacion_id' =>
                            'La asignación seleccionada no está configurada como cantidad variable mensual.',
                    ]);
            }
        }

        /*
        * Validaciones específicas de ajustes mensuales.
        */
        $erroresAjustes =
            $this->validarAjustesMensualesFormulario(
                $ajustesMensuales->all()
            );

        if (!empty($erroresAjustes)) {
            return back()
                ->withInput()
                ->withErrors($erroresAjustes);
        }

        /*
        * Guardar calendario, cantidad variable
        * y pagos adicionales.
        */
        DB::transaction(function () use (
            $data,
            $anio,
            $mes,
            $codigoComision,
            $debeGuardarCantidadMensual,
            $comisiones,
            $zonasOperativas
        ) {
            /*
            * Calendario zonal.
            */
            foreach ($zonasOperativas as $diaOperativo) {
                SuscripcionZonaDiaOperativo::query()
                    ->updateOrCreate(
                        [
                            'suscripcion_zona_id' =>
                                $diaOperativo[
                                    'suscripcion_zona_id'
                                ],

                            'fecha' =>
                                $diaOperativo['fecha'],
                        ],
                        [
                            'hubo_despacho' =>
                                $diaOperativo[
                                    'hubo_despacho'
                                ],

                            'observacion' =>
                                $diaOperativo[
                                    'observacion'
                                ],
                        ]
                    );
            }

            /*
            * Cantidad variable mensual.
            */
            if ($debeGuardarCantidadMensual) {
                $asignacionCantidad =
                    Asignaciones::findOrFail(
                        $data[
                            'cantidad_mensual_asignacion_id'
                        ]
                    );

                $codigoCantidad =
                    $asignacionCantidad->codigo;

                $costoCantidad =
                    (int) $asignacionCantidad->costo;

                $cantidadMensual =
                    (int) $data[
                        'cantidad_mensual_cantidad'
                    ];

                $totalCantidad =
                    $costoCantidad
                    * $cantidadMensual;

                SuscripcionCantidadMensual::create([
                    'suscripcion_asignacion_id' =>
                        $asignacionCantidad->id,

                    'anio' =>
                        $anio,

                    'mes' =>
                        $mes,

                    'codigo' =>
                        $codigoCantidad,

                    'costo' =>
                        $costoCantidad,

                    'cantidad' =>
                        $cantidadMensual,

                    'total' =>
                        $totalCantidad,

                    'observacion' =>
                        $data[
                            'cantidad_mensual_observacion'
                        ] ?? null,
                ]);
            }

            /*
            * Pagos adicionales.
            */
            foreach ($comisiones as $comision) {
                $tarifaComision =
                    (int) $comision['costo'];

                $cantidadComision =
                    (int) $comision['cantidad'];

                $totalComision =
                    $tarifaComision
                    * $cantidadComision;

                /*
                * Recuperar grupo de prefactura relacionado.
                */
                $grupoPrefactura =
                    Asignaciones::query()
                        ->where(
                            'suscripcion_proveedor_id',
                            $comision[
                                'suscripcion_proveedor_id'
                            ]
                        )
                        ->where(
                            'suscripcion_transportista_id',
                            $comision[
                                'suscripcion_transportista_id'
                            ]
                        )
                        ->whereNotNull(
                            'grupo_prefactura'
                        )
                        ->whereRaw(
                            "TRIM(grupo_prefactura) <> ''"
                        )
                        ->orderBy('id')
                        ->value(
                            'grupo_prefactura'
                        );

                /*
                * Asignación técnica del pago adicional.
                */
                $asignacionComision =
                    Asignaciones::create([
                        'suscripcion_proveedor_id' =>
                            (int) $comision[
                                'suscripcion_proveedor_id'
                            ],

                        'suscripcion_transportista_id' =>
                            (int) $comision[
                                'suscripcion_transportista_id'
                            ],

                        'suscripcion_zona_id' =>
                            null,

                        'punto_1' =>
                            $comision[
                                'punto_1'
                            ] ?? null,

                        'origen_gasto' =>
                            $comision[
                                'origen_gasto'
                            ] ?? 'Suscripciones',

                        'punto_2' =>
                            $comision[
                                'punto_2'
                            ] ?? null,

                        'codigo' =>
                            $codigoComision,

                        'servicio' =>
                            $comision[
                                'servicio'
                            ] ?? 'Reparto fin de semana',

                        'costo' =>
                            $tarifaComision,

                        'grupo_prefactura' =>
                            $grupoPrefactura,

                        'generar_automaticamente' =>
                            0,

                        'tipo_asignacion' =>
                            'COMISION',
                    ]);

                /*
                * Registro mensual del pago adicional.
                */
                SuscripcionComisionMensual::create([
                    'suscripcion_asignacion_id' =>
                        $asignacionComision->id,

                    'anio' =>
                        $anio,

                    'mes' =>
                        $mes,

                    'codigo' =>
                        $codigoComision,

                    'costo' =>
                        $tarifaComision,

                    'cantidad' =>
                        $cantidadComision,

                    'total' =>
                        $totalComision,

                    'observacion' =>
                        $comision[
                            'observacion'
                        ] ?? null,
                ]);
            }
        });

        /*
        * Resultado inicial registro de ajustes.
        */
        $resultadoRegistroAjustes = [
            'recibidos' => 0,
            'creados' => 0,
            'actualizados' => 0,
            'omitidos' => 0,
            'asignaciones_creadas' => 0,
            'asignaciones_reutilizadas' => 0,
        ];

        /*
        * Registrar novedades mensuales.
        */
        if ($ajustesMensuales->isNotEmpty()) {
            $resultadoRegistroAjustes =
                $ajusteMensualRegistroService
                    ->guardarDesdeFormulario(
                        $ajustesMensuales->all(),
                        $anio,
                        $mes
                    );
        }

        /*
        * Resultado inicial registro de excepciones.
        */
        $resultadoExcepcionesFacturacion = [
            'recibidas' => 0,
            'creadas' => 0,
            'actualizadas' => 0,
            'sin_cambios' => 0,
            'omitidas' => 0,
        ];

        /*
        * Registrar excepciones de facturación por fecha.
        */
        if ($excepcionesFacturacion->isNotEmpty()) {
            $resultadoExcepcionesFacturacion =
                $excepcionFacturacionRegistroService
                    ->guardarDesdeFormulario(
                        $excepcionesFacturacion->all(),
                        $anio,
                        $mes
                    );
        }

        /*
        * Generar liquidación mensual base.
        */
        $resultado =
            $generacionMensualService->generar(
                $anio,
                $mes
            );

        /*
        * Aplicar novedades mensuales normales.
        */
        $resultadoAjustes =
            $ajusteMensualAplicacionService
                ->aplicarPeriodo(
                    $anio,
                    $mes
                );

        /*
        * Aplicar excepciones de facturación por fecha.
        *
        * Se ejecutan después de los ajustes mensuales porque
        * representan una regla más específica:
        *
        * "esta ejecución concreta de esta fecha pertenece
        * a otro proveedor".
        */
        $resultadoAplicacionExcepciones =
            $excepcionFacturacionAplicacionService
                ->aplicarPeriodo(
                    $anio,
                    $mes
                );

        /*
        * Construir mensaje final.
        */
        $mensaje =
            'Datos registrados correctamente. '
            . 'Calendario zonal guardado: '
            . $zonasOperativas->count()
            . ' combinaciones zona-fecha. '
            . 'Mes generado correctamente. '
            . "Creados: {$resultado['creados']}.";

        /*
        * Pagos adicionales ingresados.
        */
        if ($comisiones->count() > 0) {
            $mensaje .=
                ' Pagos adicionales registrados previamente: '
                . "{$comisiones->count()}.";
        }

        /*
        * Cantidades variables.
        */
        if (
            ($resultado[
                'cantidades_creadas'
            ] ?? 0) > 0
        ) {
            $mensaje .=
                ' Cantidades variables agregadas: '
                . "{$resultado['cantidades_creadas']}.";
        }

        /*
        * Pagos adicionales generados.
        */
        if (
            ($resultado[
                'comisiones_creadas'
            ] ?? 0) > 0
        ) {
            $mensaje .=
                ' Pagos adicionales agregados: '
                . "{$resultado['comisiones_creadas']}.";
        }

        /*
        * Registro de ajustes mensuales.
        */
        if (
            ($resultadoRegistroAjustes[
                'recibidos'
            ] ?? 0) > 0
        ) {
            $mensaje .=
                ' Novedades mensuales recibidas: '
                . "{$resultadoRegistroAjustes['recibidos']}.";

            if (
                ($resultadoRegistroAjustes[
                    'creados'
                ] ?? 0) > 0
            ) {
                $mensaje .=
                    ' Ajustes creados: '
                    . "{$resultadoRegistroAjustes['creados']}.";
            }

            if (
                ($resultadoRegistroAjustes[
                    'actualizados'
                ] ?? 0) > 0
            ) {
                $mensaje .=
                    ' Ajustes actualizados: '
                    . "{$resultadoRegistroAjustes['actualizados']}.";
            }

            if (
                ($resultadoRegistroAjustes[
                    'asignaciones_creadas'
                ] ?? 0) > 0
            ) {
                $mensaje .=
                    ' Asignaciones contenedoras creadas: '
                    . "{$resultadoRegistroAjustes['asignaciones_creadas']}.";
            }

            if (
                ($resultadoRegistroAjustes[
                    'asignaciones_reutilizadas'
                ] ?? 0) > 0
            ) {
                $mensaje .=
                    ' Asignaciones contenedoras reutilizadas: '
                    . "{$resultadoRegistroAjustes['asignaciones_reutilizadas']}.";
            }

            if (
                ($resultadoRegistroAjustes[
                    'omitidos'
                ] ?? 0) > 0
            ) {
                $mensaje .=
                    ' Novedades omitidas: '
                    . "{$resultadoRegistroAjustes['omitidos']}.";
            }
        }

        /*
        * Registro de excepciones por fecha.
        */
        if (
            ($resultadoExcepcionesFacturacion[
                'recibidas'
            ] ?? 0) > 0
        ) {
            $mensaje .=
                ' Excepciones de facturación por fecha recibidas: '
                . "{$resultadoExcepcionesFacturacion['recibidas']}.";

            if (
                ($resultadoExcepcionesFacturacion[
                    'creadas'
                ] ?? 0) > 0
            ) {
                $mensaje .=
                    ' Excepciones creadas: '
                    . "{$resultadoExcepcionesFacturacion['creadas']}.";
            }

            if (
                ($resultadoExcepcionesFacturacion[
                    'actualizadas'
                ] ?? 0) > 0
            ) {
                $mensaje .=
                    ' Excepciones actualizadas: '
                    . "{$resultadoExcepcionesFacturacion['actualizadas']}.";
            }

            if (
                ($resultadoExcepcionesFacturacion[
                    'sin_cambios'
                ] ?? 0) > 0
            ) {
                $mensaje .=
                    ' Excepciones sin cambios: '
                    . "{$resultadoExcepcionesFacturacion['sin_cambios']}.";
            }

            if (
                ($resultadoExcepcionesFacturacion[
                    'omitidas'
                ] ?? 0) > 0
            ) {
                $mensaje .=
                    ' Excepciones omitidas: '
                    . "{$resultadoExcepcionesFacturacion['omitidas']}.";
            }
        }

        /*
        * Aplicación real de excepciones.
        */
        if (
            ($resultadoAplicacionExcepciones[
                'excepciones_procesadas'
            ] ?? 0) > 0
        ) {
            $mensaje .=
                ' Excepciones por fecha aplicadas: '
                . "{$resultadoAplicacionExcepciones['excepciones_procesadas']}.";

            if (
                ($resultadoAplicacionExcepciones[
                    'detalles_origen_actualizados'
                ] ?? 0) > 0
            ) {
                $mensaje .=
                    ' Liquidaciones de origen actualizadas: '
                    . "{$resultadoAplicacionExcepciones['detalles_origen_actualizados']}.";
            }

            if (
                ($resultadoAplicacionExcepciones[
                    'detalles_receptor_creados'
                ] ?? 0) > 0
            ) {
                $mensaje .=
                    ' Liquidaciones receptoras creadas: '
                    . "{$resultadoAplicacionExcepciones['detalles_receptor_creados']}.";
            }

            if (
                ($resultadoAplicacionExcepciones[
                    'detalles_receptor_actualizados'
                ] ?? 0) > 0
            ) {
                $mensaje .=
                    ' Liquidaciones receptoras actualizadas: '
                    . "{$resultadoAplicacionExcepciones['detalles_receptor_actualizados']}.";
            }

            if (
                ($resultadoAplicacionExcepciones[
                    'asignaciones_tecnicas_creadas'
                ] ?? 0) > 0
            ) {
                $mensaje .=
                    ' Asignaciones técnicas de excepción creadas: '
                    . "{$resultadoAplicacionExcepciones['asignaciones_tecnicas_creadas']}.";
            }

            if (
                ($resultadoAplicacionExcepciones[
                    'asignaciones_tecnicas_reutilizadas'
                ] ?? 0) > 0
            ) {
                $mensaje .=
                    ' Asignaciones técnicas de excepción reutilizadas: '
                    . "{$resultadoAplicacionExcepciones['asignaciones_tecnicas_reutilizadas']}.";
            }

            if (
                ($resultadoAplicacionExcepciones[
                    'sin_detalle_origen'
                ] ?? 0) > 0
            ) {
                $mensaje .=
                    ' Excepciones sin liquidación de origen: '
                    . "{$resultadoAplicacionExcepciones['sin_detalle_origen']}.";
            }
        }

        /*
        * Detalles técnicos eliminados porque dejaron
        * de corresponder a excepciones activas.
        */
        if (
            ($resultadoAplicacionExcepciones[
                'detalles_receptor_eliminados'
            ] ?? 0) > 0
        ) {
            $mensaje .=
                ' Liquidaciones receptoras obsoletas eliminadas: '
                . "{$resultadoAplicacionExcepciones['detalles_receptor_eliminados']}.";
        }

        /*
        * Duplicados de generación.
        */
        if (
            ($resultado['duplicados'] ?? 0) > 0
        ) {
            $mensaje .=
                ' Registros ya existentes no duplicados: '
                . "{$resultado['duplicados']}.";
        }

        if (
            ($resultado[
                'cantidades_duplicadas'
            ] ?? 0) > 0
        ) {
            $mensaje .=
                ' Cantidades variables ya existentes no duplicadas: '
                . "{$resultado['cantidades_duplicadas']}.";
        }

        if (
            ($resultado[
                'comisiones_duplicadas'
            ] ?? 0) > 0
        ) {
            $mensaje .=
                ' Pagos adicionales ya generados no duplicados: '
                . "{$resultado['comisiones_duplicadas']}.";
        }

        /*
        * Aplicación de ajustes mensuales.
        */
        if (
            ($resultadoAjustes[
                'ajustes_procesados'
            ] ?? 0) > 0
        ) {
            $mensaje .=
                ' Ajustes mensuales procesados: '
                . "{$resultadoAjustes['ajustes_procesados']}.";

            if (
                ($resultadoAjustes[
                    'detalles_actualizados'
                ] ?? 0) > 0
            ) {
                $mensaje .=
                    ' Detalles actualizados por ajustes: '
                    . "{$resultadoAjustes['detalles_actualizados']}.";
            }

            if (
                ($resultadoAjustes[
                    'lineas_adicionales_creadas'
                ] ?? 0) > 0
            ) {
                $mensaje .=
                    ' Líneas adicionales creadas: '
                    . "{$resultadoAjustes['lineas_adicionales_creadas']}.";
            }

            if (
                ($resultadoAjustes[
                    'lineas_adicionales_actualizadas'
                ] ?? 0) > 0
            ) {
                $mensaje .=
                    ' Líneas adicionales actualizadas: '
                    . "{$resultadoAjustes['lineas_adicionales_actualizadas']}.";
            }

            if (
                ($resultadoAjustes[
                    'facturacion_registrada'
                ] ?? 0) > 0
            ) {
                $mensaje .=
                    ' Ajustes de facturación considerados: '
                    . "{$resultadoAjustes['facturacion_registrada']}.";
            }

            if (
                ($resultadoAjustes[
                    'sin_detalle'
                ] ?? 0) > 0
            ) {
                $mensaje .=
                    ' Ajustes sin detalle mensual asociado: '
                    . "{$resultadoAjustes['sin_detalle']}.";
            }

            if (
                ($resultadoAjustes[
                    'ignorados'
                ] ?? 0) > 0
            ) {
                $mensaje .=
                    ' Ajustes ignorados por compatibilidad: '
                    . "{$resultadoAjustes['ignorados']}.";
            }
        }

        /*
        * Rutas OPV sin configuración.
        */
        if (
            $resultado[
                'opv_sin_rutas'
            ]->isNotEmpty()
        ) {
            $mensaje .=
                ' No se generaron las siguientes rutas OPV '
                . 'porque no tienen locales OPV asignados: ';

            $mensaje .=
                $resultado[
                    'opv_sin_rutas'
                ]
                    ->unique()
                    ->implode('; ')
                . '.';
        }

        return redirect()
            ->route(
                'suscripciones.liquidacion-detalles.index',
                [
                    'anio' => $anio,
                    'mes' => $mes,
                ]
            )
            ->with(
                'success',
                $mensaje
            );
    }







    private function validarAjustesMensualesFormulario(array $ajustes): array
    {
        $errores = [];

        foreach ($ajustes as $index => $ajuste) {
            $numero = $index + 1;

            $tipo = mb_strtoupper(
                trim(
                    (string) (
                        $ajuste['tipo_ajuste'] ?? ''
                    )
                )
            );

            $tipo = str_replace(
                [' ', '-'],
                '_',
                $tipo
            );

            /*
            * Validar que exista tipo de novedad.
            */
            if ($tipo === '') {
                $errores[
                    "ajustes_mensuales.$index.tipo_ajuste"
                ] =
                    "La novedad mensual #{$numero} no tiene tipo de ajuste.";

                continue;
            }

            /*
            * Tipos de novedades mensuales soportados.
            */
            if (
                !in_array(
                    $tipo,
                    [
                        'INASISTENCIA',
                        'FACTURACION',
                        'LINEA_ADICIONAL',
                        'PAGO_VARIABLE',

                        /*
                        * Compatibilidad temporal con
                        * formularios antiguos.
                        */
                        'PAGO_ADICIONAL',

                        'REEMPLAZO',
                    ],
                    true
                )
            ) {
                $errores[
                    "ajustes_mensuales.$index.tipo_ajuste"
                ] =
                    "La novedad mensual #{$numero} tiene un tipo de ajuste no válido.";

                continue;
            }

            /*
            * INASISTENCIA y FACTURACION trabajan
            * necesariamente sobre una asignación existente.
            */
            if (
                in_array(
                    $tipo,
                    [
                        'INASISTENCIA',
                        'FACTURACION',
                    ],
                    true
                )
            ) {
                if (
                    empty(
                        $ajuste[
                            'suscripcion_asignacion_id'
                        ]
                    )
                ) {
                    $errores[
                        "ajustes_mensuales.$index.suscripcion_asignacion_id"
                    ] =
                        "La novedad mensual #{$numero} requiere una asignación existente.";
                }
            }

            /*
            * Resolver la asignación existente, si fue informada.
            */
            $asignacionAjuste = null;

            if (
                !empty(
                    $ajuste[
                        'suscripcion_asignacion_id'
                    ]
                )
            ) {
                $asignacionAjuste =
                    Asignaciones::find(
                        $ajuste[
                            'suscripcion_asignacion_id'
                        ]
                    );

                if (!$asignacionAjuste) {
                    $errores[
                        "ajustes_mensuales.$index.suscripcion_asignacion_id"
                    ] =
                        "La novedad mensual #{$numero} tiene una asignación inválida.";

                    continue;
                }

                /*
                * Las asignaciones técnicas nunca pueden volver
                * a utilizarse como asignaciones base.
                *
                * COMISION:
                * asignación creada para pagos adicionales.
                *
                * CONTENEDOR_AJUSTE:
                * asignación técnica utilizada para líneas
                * adicionales, pagos variables, etc.
                *
                * EXCEPCION_FACTURACION:
                * asignación técnica creada para trasladar
                * una ejecución puntual a otro proveedor.
                */
                if (
                    in_array(
                        $asignacionAjuste->tipo_asignacion,
                        [
                            'COMISION',
                            'CONTENEDOR_AJUSTE',
                            'EXCEPCION_FACTURACION',
                        ],
                        true
                    )
                ) {
                    $errores[
                        "ajustes_mensuales.$index.suscripcion_asignacion_id"
                    ] =
                        "La novedad mensual #{$numero} no puede usar comisiones, contenedores ni excepciones de facturación como asignación existente.";
                }

                /*
                * Las inasistencias sólo corresponden
                * a rutas normales.
                */
                if (
                    $tipo === 'INASISTENCIA'
                    && $asignacionAjuste->tipo_asignacion
                        !== 'RUTA'
                ) {
                    $errores[
                        "ajustes_mensuales.$index.suscripcion_asignacion_id"
                    ] =
                        "La inasistencia #{$numero} sólo puede aplicarse a rutas normales.";
                }

                /*
                * Los cambios de facturación mensuales
                * sólo pueden aplicarse sobre tipos
                * operacionales reconocidos.
                *
                * EXCEPCION_FACTURACION no entra aquí porque
                * representa una asignación técnica generada
                * por el nuevo flujo de excepciones por fecha.
                */
                if (
                    $tipo === 'FACTURACION'
                    && !in_array(
                        $asignacionAjuste->tipo_asignacion,
                        [
                            'RUTA',
                            'VARIABLE',
                            'FIJO_MENSUAL',
                            'OPV',
                        ],
                        true
                    )
                ) {
                    $errores[
                        "ajustes_mensuales.$index.suscripcion_asignacion_id"
                    ] =
                        "El cambio de facturación #{$numero} no puede aplicarse a esta asignación.";
                }
            }

            /*
            * Líneas que se construyen como novedades
            * independientes del maestro normal.
            */
            if (
                in_array(
                    $tipo,
                    [
                        'LINEA_ADICIONAL',
                        'PAGO_VARIABLE',
                        'PAGO_ADICIONAL',
                        'REEMPLAZO',
                    ],
                    true
                )
            ) {
                /*
                * Proveedor obligatorio.
                */
                if (
                    empty(
                        $ajuste[
                            'suscripcion_proveedor_id'
                        ]
                    )
                ) {
                    $errores[
                        "ajustes_mensuales.$index.suscripcion_proveedor_id"
                    ] =
                        "La novedad mensual #{$numero} requiere un proveedor.";
                }

                /*
                * Código obligatorio.
                */
                if (
                    empty(
                        $ajuste['codigo']
                    )
                ) {
                    $errores[
                        "ajustes_mensuales.$index.codigo"
                    ] =
                        "La novedad mensual #{$numero} requiere un código.";
                }

                /*
                * Costo obligatorio.
                */
                if (
                    !isset(
                        $ajuste['costo']
                    )
                    || $ajuste['costo'] === ''
                ) {
                    $errores[
                        "ajustes_mensuales.$index.costo"
                    ] =
                        "La novedad mensual #{$numero} requiere un costo.";
                }

                /*
                * Cantidad obligatoria.
                */
                if (
                    !isset(
                        $ajuste['cantidad']
                    )
                    || $ajuste['cantidad'] === ''
                ) {
                    $errores[
                        "ajustes_mensuales.$index.cantidad"
                    ] =
                        "La novedad mensual #{$numero} requiere una cantidad.";
                }

                /*
                * Pago variable:
                *
                * debe venir desde un concepto configurado
                * o desde un concepto manual.
                */
                if ($tipo === 'PAGO_VARIABLE') {
                    $tieneConceptoSeleccionado =
                        !empty(
                            $ajuste[
                                'concepto_pago_variable_id'
                            ]
                        );

                    $tieneConceptoManual =
                        !empty(
                            trim(
                                (string) (
                                    $ajuste[
                                        'concepto_pago_variable_manual'
                                    ] ?? ''
                                )
                            )
                        );

                    if (
                        !$tieneConceptoSeleccionado
                        && !$tieneConceptoManual
                    ) {
                        $errores[
                            "ajustes_mensuales.$index.concepto_pago_variable_id"
                        ] =
                            "El pago variable #{$numero} requiere seleccionar un concepto o escribir uno manualmente.";
                    }
                }
            }

            /*
            * Inasistencia:
            *
            * debe indicar cuántas ejecuciones no fueron
            * realizadas durante días operativos.
            */
            if ($tipo === 'INASISTENCIA') {
                if (
                    !isset(
                        $ajuste[
                            'q_inasistencia'
                        ]
                    )
                    || $ajuste[
                        'q_inasistencia'
                    ] === ''
                ) {
                    $errores[
                        "ajustes_mensuales.$index.q_inasistencia"
                    ] =
                        "La novedad mensual #{$numero} requiere cantidad de inasistencias.";
                }
            }
        }

        return $errores;
    }









    private function obtenerFechasFinSemana(int $anio, int $mes): Collection 
    {
        $fecha = CarbonImmutable::create(
            $anio,
            $mes,
            1
        )->startOfMonth();

        $fechaFin = $fecha->endOfMonth();

        $fechas = collect();

        while ($fecha->lessThanOrEqualTo($fechaFin)) {
            if ($fecha->isSaturday() || $fecha->isSunday()) {
                $fechas->push($fecha->toDateString());
            }

            $fecha = $fecha->addDay();
        }

        return $fechas;
    }



    private function validarCalendarioZonas( Collection $zonasOperativas, int $anio, int $mes): void 
    {
        $zonasActivasIds = SuscripcionZona::query()
            ->where('activo', true)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($zonaId) => (int) $zonaId)
            ->values();

        $fechasEsperadas = $this->obtenerFechasFinSemana(
            $anio,
            $mes
        );

        $clavesEsperadas = collect();

        foreach ($zonasActivasIds as $zonaId) {
            foreach ($fechasEsperadas as $fecha) {
                $clavesEsperadas->push(
                    $zonaId . '|' . $fecha
                );
            }
        }

        $clavesRecibidas = $zonasOperativas
            ->map(function (array $diaOperativo) {
                return
                    $diaOperativo['suscripcion_zona_id']
                    . '|'
                    . $diaOperativo['fecha'];
            })
            ->values();

        /*
        * Impedir que una misma combinación zona-fecha
        * sea enviada más de una vez.
        */
        if ($clavesRecibidas->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'zonas_operativas' =>
                    'El calendario contiene combinaciones de zona y fecha duplicadas.',
            ]);
        }

        /*
        * La información recibida debe coincidir exactamente con:
        *
        * zonas activas × sábados y domingos del período.
        */
        $clavesEsperadasOrdenadas = $clavesEsperadas
            ->sort()
            ->values();

        $clavesRecibidasOrdenadas = $clavesRecibidas
            ->sort()
            ->values();

        if (
            $clavesEsperadasOrdenadas->all()
            !== $clavesRecibidasOrdenadas->all()
        ) {
            throw ValidationException::withMessages([
                'zonas_operativas' =>
                    'El calendario de zonas está incompleto o contiene zonas o fechas que no corresponden al período seleccionado.',
            ]);
        }
    }


}