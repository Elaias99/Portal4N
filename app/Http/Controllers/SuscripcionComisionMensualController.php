<?php

namespace App\Http\Controllers;

use App\Models\Asignaciones;
use App\Models\SuscripcionComisionMensual;
use App\Models\SuscripcionProveedor;
use App\Models\SuscripcionTransportista;
use App\Models\SuscripcionCantidadMensual;
use App\Models\SuscripcionConceptoPagoVariable;
use App\Services\Suscripciones\SuscripcionGeneracionMensualService;
use App\Services\Suscripciones\SuscripcionAjusteMensualAplicacionService;
use App\Services\Suscripciones\SuscripcionAjusteMensualRegistroService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuscripcionComisionMensualController extends Controller
{

    public function create(Request $request)
    {
        $anio = (int) $request->input('anio', now()->year);
        $mes = (int) $request->input('mes', now()->month);

        $proveedores = SuscripcionProveedor::with('cobranzaCompra')
            ->whereHas('cobranzaCompra')
            ->get()
            ->sortBy(fn ($proveedor) => $proveedor->cobranzaCompra?->razon_social)
            ->values();

        $transportistas = SuscripcionTransportista::query()
            ->orderBy('nombre_transportista')
            ->get();

        /*
        * Cantidades variables del mes.
        *
        * Por ahora dejamos esta sección estrictamente limitada a asignaciones
        * configuradas como VARIABLE, por ejemplo LOTA.
        */
        $asignacionesCantidadMensual = Asignaciones::with([
            'suscripcionProveedor.cobranzaCompra',
            'transportista',
        ])
            ->where('tipo_asignacion', 'VARIABLE')
            ->orderBy('codigo')
            ->get();

        /*
        * Asignaciones disponibles para novedades mensuales.
        *
        * No se permiten comisiones ni contenedores técnicos como asignación base.
        */
        $asignacionesAjustesMensuales = Asignaciones::with([
            'suscripcionProveedor.cobranzaCompra',
            'transportista',
        ])
            ->whereNotIn('tipo_asignacion', [
                'COMISION',
                'CONTENEDOR_AJUSTE',
            ])
            ->orderBy('codigo')
            ->get();

        /*
        * Pagos fijos mensuales automáticos.
        */
        $asignacionesFijasMensuales = Asignaciones::with([
            'suscripcionProveedor.cobranzaCompra',
            'transportista',
        ])
            ->where('tipo_asignacion', 'FIJO_MENSUAL')
            ->orderBy('codigo')
            ->get();

        /*
        * Catálogo de conceptos para pago variable:
        * Compaginado, primera vuelta, segunda vuelta, etc.
        */
        $conceptosPagoVariable = SuscripcionConceptoPagoVariable::query()
            ->where('activo', true)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        return view('suscripciones.comisiones_mensuales.create', compact(
            'anio',
            'mes',
            'proveedores',
            'transportistas',
            'asignacionesCantidadMensual',
            'asignacionesAjustesMensuales',
            'asignacionesFijasMensuales',
            'conceptosPagoVariable'
        ));
    }

    public function store(
        Request $request,
        SuscripcionGeneracionMensualService $generacionMensualService,
        SuscripcionAjusteMensualRegistroService $ajusteMensualRegistroService,
        SuscripcionAjusteMensualAplicacionService $ajusteMensualAplicacionService
    ) {
        $data = $request->validate([
            'anio' => 'required|integer|min:2020|max:2100',
            'mes' => 'required|integer|min:1|max:12',

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
            *
            * costo representa la tarifa unitaria.
            * cantidad representa las unidades informadas para ese proveedor.
            * total se calcula en el backend: tarifa × cantidad.
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
        ]);

        $anio = (int) $data['anio'];
        $mes = (int) $data['mes'];

        $codigoComision = 'COMISION';

        $comisiones = collect($data['comisiones'] ?? [])
            ->values();

        $ajustesMensuales = collect(
            $data['ajustes_mensuales'] ?? []
        )->values();

        $debeGuardarCantidadMensual =
            !empty($data['cantidad_mensual_asignacion_id'])
            && !empty($data['cantidad_mensual_cantidad']);

        /*
        * Validar que no exista previamente la cantidad variable
        * para la misma asignación y periodo.
        */
        if ($debeGuardarCantidadMensual) {
            $existeCantidadMensual = SuscripcionCantidadMensual::query()
                ->where(
                    'suscripcion_asignacion_id',
                    $data['cantidad_mensual_asignacion_id']
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
        * Confirmar que la asignación seleccionada corresponda
        * efectivamente a una cantidad variable.
        */
        if ($debeGuardarCantidadMensual) {
            $asignacionCantidadMensual = Asignaciones::find(
                $data['cantidad_mensual_asignacion_id']
            );

            if (
                !$asignacionCantidadMensual
                || $asignacionCantidadMensual->tipo_asignacion !== 'VARIABLE'
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
        * Validaciones específicas para novedades mensuales.
        */
        $erroresAjustes = $this->validarAjustesMensualesFormulario(
            $ajustesMensuales->all()
        );

        if (!empty($erroresAjustes)) {
            return back()
                ->withInput()
                ->withErrors($erroresAjustes);
        }

        /*
        * No se valida duplicidad entre pagos adicionales.
        *
        * Cada posición de comisiones[] representa un pago independiente,
        * incluso cuando proveedor, transportista, tarifa, cantidad,
        * servicio y observación sean exactamente iguales.
        */
        DB::transaction(function () use (
            $data,
            $anio,
            $mes,
            $codigoComision,
            $debeGuardarCantidadMensual,
            $comisiones
        ) {
            /*
            * Guardar cantidad variable mensual.
            */
            if ($debeGuardarCantidadMensual) {
                $asignacionCantidad = Asignaciones::findOrFail(
                    $data['cantidad_mensual_asignacion_id']
                );

                $codigoCantidad = $asignacionCantidad->codigo;
                $costoCantidad = (int) $asignacionCantidad->costo;
                $cantidadMensual =
                    (int) $data['cantidad_mensual_cantidad'];

                $totalCantidad =
                    $costoCantidad * $cantidadMensual;

                SuscripcionCantidadMensual::create([
                    'suscripcion_asignacion_id' =>
                        $asignacionCantidad->id,

                    'anio' => $anio,
                    'mes' => $mes,
                    'codigo' => $codigoCantidad,
                    'costo' => $costoCantidad,
                    'cantidad' => $cantidadMensual,
                    'total' => $totalCantidad,

                    'observacion' =>
                        $data['cantidad_mensual_observacion']
                        ?? null,
                ]);
            }

            /*
            * Guardar pagos adicionales.
            *
            * Se crea una asignación técnica independiente por cada pago.
            * Esto permite registrar más de un pago para el mismo proveedor,
            * incluso con datos completamente iguales.
            */
            foreach ($comisiones as $comision) {
                $tarifaComision =
                    (int) $comision['costo'];

                $cantidadComision =
                    (int) $comision['cantidad'];

                $totalComision =
                    $tarifaComision * $cantidadComision;

                /*
                * Recuperar un grupo de prefactura relacionado
                * con el proveedor y transportista.
                */
                $grupoPrefactura = Asignaciones::query()
                    ->where(
                        'suscripcion_proveedor_id',
                        $comision['suscripcion_proveedor_id']
                    )
                    ->where(
                        'suscripcion_transportista_id',
                        $comision['suscripcion_transportista_id']
                    )
                    ->whereNotNull('grupo_prefactura')
                    ->whereRaw("TRIM(grupo_prefactura) <> ''")
                    ->orderBy('id')
                    ->value('grupo_prefactura');

                /*
                * Asignación técnica del pago adicional.
                *
                * costo almacena la tarifa unitaria,
                * no el total calculado.
                */
                $asignacionComision = Asignaciones::create([
                    'suscripcion_proveedor_id' =>
                        (int) $comision['suscripcion_proveedor_id'],

                    'suscripcion_transportista_id' =>
                        (int) $comision['suscripcion_transportista_id'],

                    'punto_1' =>
                        $comision['punto_1'] ?? null,

                    'origen_gasto' =>
                        $comision['origen_gasto']
                        ?? 'Suscripciones',

                    'punto_2' =>
                        $comision['punto_2'] ?? null,

                    'codigo' =>
                        $codigoComision,

                    'servicio' =>
                        $comision['servicio']
                        ?? 'Reparto fin de semana',

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
                *
                * Ejemplo:
                * tarifa = 5.000
                * cantidad = 10
                * total = 50.000
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
                        $comision['observacion']
                        ?? null,
                ]);
            }
        });

        /*
        * Resultado inicial del registro de novedades.
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
                $ajusteMensualRegistroService->guardarDesdeFormulario(
                    $ajustesMensuales->all(),
                    $anio,
                    $mes
                );
        }

        /*
        * Generar detalles mensuales.
        */
        $resultado = $generacionMensualService->generar(
            $anio,
            $mes
        );

        /*
        * Aplicar novedades sobre los detalles generados.
        */
        $resultadoAjustes =
            $ajusteMensualAplicacionService->aplicarPeriodo(
                $anio,
                $mes
            );

        $mensaje =
            "Datos registrados correctamente. "
            . "Mes generado correctamente. "
            . "Creados: {$resultado['creados']}.";

        if ($comisiones->count() > 0) {
            $mensaje .=
                " Pagos adicionales registrados previamente: "
                . "{$comisiones->count()}.";
        }

        if (($resultado['cantidades_creadas'] ?? 0) > 0) {
            $mensaje .=
                " Cantidades variables agregadas: "
                . "{$resultado['cantidades_creadas']}.";
        }

        if (($resultado['comisiones_creadas'] ?? 0) > 0) {
            $mensaje .=
                " Pagos adicionales agregados: "
                . "{$resultado['comisiones_creadas']}.";
        }

        if (($resultadoRegistroAjustes['recibidos'] ?? 0) > 0) {
            $mensaje .=
                " Novedades mensuales recibidas: "
                . "{$resultadoRegistroAjustes['recibidos']}.";

            if (($resultadoRegistroAjustes['creados'] ?? 0) > 0) {
                $mensaje .=
                    " Ajustes creados: "
                    . "{$resultadoRegistroAjustes['creados']}.";
            }

            if (($resultadoRegistroAjustes['actualizados'] ?? 0) > 0) {
                $mensaje .=
                    " Ajustes actualizados: "
                    . "{$resultadoRegistroAjustes['actualizados']}.";
            }

            if (
                ($resultadoRegistroAjustes['asignaciones_creadas'] ?? 0)
                > 0
            ) {
                $mensaje .=
                    " Asignaciones contenedoras creadas: "
                    . "{$resultadoRegistroAjustes['asignaciones_creadas']}.";
            }

            if (
                ($resultadoRegistroAjustes['asignaciones_reutilizadas'] ?? 0)
                > 0
            ) {
                $mensaje .=
                    " Asignaciones contenedoras reutilizadas: "
                    . "{$resultadoRegistroAjustes['asignaciones_reutilizadas']}.";
            }

            if (($resultadoRegistroAjustes['omitidos'] ?? 0) > 0) {
                $mensaje .=
                    " Novedades omitidas: "
                    . "{$resultadoRegistroAjustes['omitidos']}.";
            }
        }

        if (($resultado['duplicados'] ?? 0) > 0) {
            $mensaje .=
                " Registros ya existentes no duplicados: "
                . "{$resultado['duplicados']}.";
        }

        if (($resultado['cantidades_duplicadas'] ?? 0) > 0) {
            $mensaje .=
                " Cantidades variables ya existentes no duplicadas: "
                . "{$resultado['cantidades_duplicadas']}.";
        }

        if (($resultado['comisiones_duplicadas'] ?? 0) > 0) {
            $mensaje .=
                " Pagos adicionales ya generados no duplicados: "
                . "{$resultado['comisiones_duplicadas']}.";
        }

        if (($resultadoAjustes['ajustes_procesados'] ?? 0) > 0) {
            $mensaje .=
                " Ajustes mensuales procesados: "
                . "{$resultadoAjustes['ajustes_procesados']}.";

            if (($resultadoAjustes['detalles_actualizados'] ?? 0) > 0) {
                $mensaje .=
                    " Detalles actualizados por ajustes: "
                    . "{$resultadoAjustes['detalles_actualizados']}.";
            }

            if (
                ($resultadoAjustes['lineas_adicionales_creadas'] ?? 0)
                > 0
            ) {
                $mensaje .=
                    " Líneas adicionales creadas: "
                    . "{$resultadoAjustes['lineas_adicionales_creadas']}.";
            }

            if (
                ($resultadoAjustes['lineas_adicionales_actualizadas'] ?? 0)
                > 0
            ) {
                $mensaje .=
                    " Líneas adicionales actualizadas: "
                    . "{$resultadoAjustes['lineas_adicionales_actualizadas']}.";
            }

            if (($resultadoAjustes['facturacion_registrada'] ?? 0) > 0) {
                $mensaje .=
                    " Ajustes de facturación considerados: "
                    . "{$resultadoAjustes['facturacion_registrada']}.";
            }

            if (($resultadoAjustes['sin_detalle'] ?? 0) > 0) {
                $mensaje .=
                    " Ajustes sin detalle mensual asociado: "
                    . "{$resultadoAjustes['sin_detalle']}.";
            }

            if (($resultadoAjustes['ignorados'] ?? 0) > 0) {
                $mensaje .=
                    " Ajustes ignorados por compatibilidad: "
                    . "{$resultadoAjustes['ignorados']}.";
            }
        }

        if ($resultado['opv_sin_rutas']->isNotEmpty()) {
            $mensaje .=
                ' No se generaron las siguientes rutas OPV '
                . 'porque no tienen locales OPV asignados: ';

            $mensaje .=
                $resultado['opv_sin_rutas']
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
            ->with('success', $mensaje);
    }

    private function validarAjustesMensualesFormulario(array $ajustes): array
    {
        $errores = [];

        foreach ($ajustes as $index => $ajuste) {
            $numero = $index + 1;
            $tipo = mb_strtoupper(trim((string) ($ajuste['tipo_ajuste'] ?? '')));
            $tipo = str_replace([' ', '-'], '_', $tipo);

            if ($tipo === '') {
                $errores["ajustes_mensuales.$index.tipo_ajuste"] = "La novedad mensual #{$numero} no tiene tipo de ajuste.";
                continue;
            }

            if (!in_array($tipo, [
                'INASISTENCIA',
                'FACTURACION',
                'LINEA_ADICIONAL',
                'PAGO_VARIABLE',
                'PAGO_ADICIONAL', // compatibilidad temporal con formularios antiguos
                'REEMPLAZO',
            ], true)) {
                $errores["ajustes_mensuales.$index.tipo_ajuste"] = "La novedad mensual #{$numero} tiene un tipo de ajuste no válido.";
                continue;
            }

            if (in_array($tipo, ['INASISTENCIA', 'FACTURACION'], true)) {
                if (empty($ajuste['suscripcion_asignacion_id'])) {
                    $errores["ajustes_mensuales.$index.suscripcion_asignacion_id"] = "La novedad mensual #{$numero} requiere una asignación existente.";
                }
            }

            $asignacionAjuste = null;

            if (!empty($ajuste['suscripcion_asignacion_id'])) {
                $asignacionAjuste = Asignaciones::find($ajuste['suscripcion_asignacion_id']);

                if (!$asignacionAjuste) {
                    $errores["ajustes_mensuales.$index.suscripcion_asignacion_id"] = "La novedad mensual #{$numero} tiene una asignación inválida.";
                    continue;
                }

                if (in_array($asignacionAjuste->tipo_asignacion, ['COMISION', 'CONTENEDOR_AJUSTE'], true)) {
                    $errores["ajustes_mensuales.$index.suscripcion_asignacion_id"] = "La novedad mensual #{$numero} no puede usar comisiones ni contenedores como asignación existente.";
                }

                if ($tipo === 'INASISTENCIA' && $asignacionAjuste->tipo_asignacion !== 'RUTA') {
                    $errores["ajustes_mensuales.$index.suscripcion_asignacion_id"] = "La inasistencia #{$numero} sólo puede aplicarse a rutas normales.";
                }

                if ($tipo === 'FACTURACION' && !in_array($asignacionAjuste->tipo_asignacion, ['RUTA', 'VARIABLE', 'FIJO_MENSUAL', 'OPV'], true)) {
                    $errores["ajustes_mensuales.$index.suscripcion_asignacion_id"] = "El cambio de facturación #{$numero} no puede aplicarse a esta asignación.";
                }
            }

            if (in_array($tipo, ['LINEA_ADICIONAL', 'PAGO_VARIABLE', 'PAGO_ADICIONAL', 'REEMPLAZO'], true)) {
                if (empty($ajuste['suscripcion_proveedor_id'])) {
                    $errores["ajustes_mensuales.$index.suscripcion_proveedor_id"] = "La novedad mensual #{$numero} requiere un proveedor.";
                }

                if (empty($ajuste['codigo'])) {
                    $errores["ajustes_mensuales.$index.codigo"] = "La novedad mensual #{$numero} requiere un código.";
                }

                if (!isset($ajuste['costo']) || $ajuste['costo'] === '') {
                    $errores["ajustes_mensuales.$index.costo"] = "La novedad mensual #{$numero} requiere un costo.";
                }

                if (!isset($ajuste['cantidad']) || $ajuste['cantidad'] === '') {
                    $errores["ajustes_mensuales.$index.cantidad"] = "La novedad mensual #{$numero} requiere una cantidad.";
                }

                if ($tipo === 'PAGO_VARIABLE') {
                    $tieneConceptoSeleccionado = !empty($ajuste['concepto_pago_variable_id']);
                    $tieneConceptoManual = !empty(trim((string) ($ajuste['concepto_pago_variable_manual'] ?? '')));

                    if (!$tieneConceptoSeleccionado && !$tieneConceptoManual) {
                        $errores["ajustes_mensuales.$index.concepto_pago_variable_id"] = "El pago variable #{$numero} requiere seleccionar un concepto o escribir uno manualmente.";
                    }
                }
            }

            if ($tipo === 'INASISTENCIA') {
                if (!isset($ajuste['q_inasistencia']) || $ajuste['q_inasistencia'] === '') {
                    $errores["ajustes_mensuales.$index.q_inasistencia"] = "La novedad mensual #{$numero} requiere cantidad de inasistencias.";
                }
            }
        }

        return $errores;
    }


}