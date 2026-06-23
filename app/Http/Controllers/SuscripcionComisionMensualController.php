<?php

namespace App\Http\Controllers;

use App\Models\Asignaciones;
use App\Models\SuscripcionComisionMensual;
use App\Models\SuscripcionProveedor;
use App\Models\SuscripcionTransportista;
use App\Models\SuscripcionCantidadMensual;
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
        * Por ahora dejamos esta sección estrictamente limitada a LOTA,
        * porque otras asignaciones con generar_automaticamente = 0 pueden ser
        * contenedoras de reemplazos, pagos adicionales o líneas especiales,
        * y no deben aparecer como cantidades variables.
        *
        * Ejemplo correcto:
        * - LOTA: cantidad variable informada del mes.
        *
        * Ejemplos que NO deben aparecer aquí:
        * - VA03, VA04
        * - BH.01, 02, 03
        * - COMISION
        * - .COM
        */
        $asignacionesCantidadMensual = Asignaciones::with([
            'suscripcionProveedor.cobranzaCompra',
            'transportista',
        ])
            ->where('generar_automaticamente', 0)
            ->whereRaw("UPPER(TRIM(codigo)) = 'LOTA'")
            ->orderBy('codigo')
            ->get();

        /*
        * Novedades mensuales.
        *
        * Aquí sí dejamos disponibles las asignaciones existentes,
        * porque el filtro fino lo hace la vista según el tipo de novedad:
        * INASISTENCIA, FIJO_MENSUAL, FACTURACION, REEMPLAZO, etc.
        */
        $asignacionesAjustesMensuales = Asignaciones::with([
            'suscripcionProveedor.cobranzaCompra',
            'transportista',
        ])
            ->orderBy('codigo')
            ->get();

        return view('suscripciones.comisiones_mensuales.create', compact(
            'anio',
            'mes',
            'proveedores',
            'transportistas',
            'asignacionesCantidadMensual',
            'asignacionesAjustesMensuales'
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

            'cantidad_mensual_asignacion_id' => 'nullable|required_with:cantidad_mensual_cantidad|exists:suscripcion_asignaciones,id',
            'cantidad_mensual_cantidad' => 'nullable|required_with:cantidad_mensual_asignacion_id|integer|min:1',
            'cantidad_mensual_observacion' => 'nullable|string|max:1000',

            'comisiones' => 'nullable|array',
            'comisiones.*.suscripcion_proveedor_id' => 'required|exists:suscripcion_proveedores,id',
            'comisiones.*.suscripcion_transportista_id' => 'required|exists:suscripcion_transportistas,id',
            'comisiones.*.punto_1' => 'nullable|string|max:255',
            'comisiones.*.origen_gasto' => 'nullable|string|max:255',
            'comisiones.*.punto_2' => 'nullable|string|max:255',
            'comisiones.*.servicio' => 'nullable|string|max:255',
            'comisiones.*.costo' => 'required|integer|min:0',
            'comisiones.*.observacion' => 'nullable|string|max:1000',

            'ajustes_mensuales' => 'nullable|array',
            'ajustes_mensuales.*.tipo_ajuste' => 'required|string|max:50',

            'ajustes_mensuales.*.suscripcion_asignacion_id' => 'nullable|exists:suscripcion_asignaciones,id',
            'ajustes_mensuales.*.suscripcion_proveedor_id' => 'nullable|exists:suscripcion_proveedores,id',
            'ajustes_mensuales.*.suscripcion_transportista_id' => 'nullable|exists:suscripcion_transportistas,id',

            'ajustes_mensuales.*.suscripcion_proveedor_facturacion_id' => 'nullable|exists:suscripcion_proveedores,id',
            'ajustes_mensuales.*.suscripcion_transportista_override_id' => 'nullable|exists:suscripcion_transportistas,id',

            'ajustes_mensuales.*.punto_1' => 'nullable|string|max:255',
            'ajustes_mensuales.*.origen_gasto' => 'nullable|string|max:255',
            'ajustes_mensuales.*.punto_2' => 'nullable|string|max:255',
            'ajustes_mensuales.*.codigo' => 'nullable|string|max:255',
            'ajustes_mensuales.*.servicio' => 'nullable|string|max:255',

            'ajustes_mensuales.*.tipo_documento' => 'nullable|string|max:255',
            'ajustes_mensuales.*.detalle_documento' => 'nullable|string|max:255',
            'ajustes_mensuales.*.detalle_impuesto' => 'nullable|string|max:255',
            'ajustes_mensuales.*.final' => 'nullable|string|max:255',

            'ajustes_mensuales.*.grupo_prefactura' => 'nullable|string|max:255',

            'ajustes_mensuales.*.costo' => 'nullable|integer|min:0',
            'ajustes_mensuales.*.q_calendario' => 'nullable|integer|min:0',
            'ajustes_mensuales.*.q_inasistencia' => 'nullable|integer|min:0',
            'ajustes_mensuales.*.cantidad' => 'nullable|integer|min:0',
            'ajustes_mensuales.*.total' => 'nullable|integer|min:0',

            'ajustes_mensuales.*.observacion' => 'nullable|string|max:1000',
        ]);

        $anio = (int) $data['anio'];
        $mes = (int) $data['mes'];

        $codigoComision = 'COMISION';

        $comisiones = collect($data['comisiones'] ?? [])->values();
        $ajustesMensuales = collect($data['ajustes_mensuales'] ?? [])->values();

        $debeGuardarCantidadMensual = !empty($data['cantidad_mensual_asignacion_id'])
            && !empty($data['cantidad_mensual_cantidad']);

        if ($debeGuardarCantidadMensual) {
            $existeCantidadMensual = SuscripcionCantidadMensual::where('suscripcion_asignacion_id', $data['cantidad_mensual_asignacion_id'])
                ->where('anio', $anio)
                ->where('mes', $mes)
                ->exists();

            if ($existeCantidadMensual) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'cantidad_mensual_asignacion_id' => 'Esta cantidad mensual ya existe para la asignación, año y mes seleccionado.',
                    ]);
            }
        }

        $erroresAjustes = $this->validarAjustesMensualesFormulario($ajustesMensuales->all());

        if (!empty($erroresAjustes)) {
            return back()
                ->withInput()
                ->withErrors($erroresAjustes);
        }

        $clavesComisiones = [];

        foreach ($comisiones as $index => $comision) {
            $claveComision = $comision['suscripcion_proveedor_id'] . '_' . $comision['suscripcion_transportista_id'];

            if (isset($clavesComisiones[$claveComision])) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'comisiones' => 'No puedes agregar más de una comisión para el mismo proveedor y transportista en el mismo periodo.',
                    ]);
            }

            $clavesComisiones[$claveComision] = true;

            $existeComision = SuscripcionComisionMensual::where('anio', $anio)
                ->where('mes', $mes)
                ->whereRaw('UPPER(TRIM(codigo)) = ?', [$codigoComision])
                ->whereHas('asignacion', function ($query) use ($comision) {
                    $query->where('suscripcion_proveedor_id', $comision['suscripcion_proveedor_id'])
                        ->where('suscripcion_transportista_id', $comision['suscripcion_transportista_id']);
                })
                ->exists();

            if ($existeComision) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'comisiones' => 'Ya existe una comisión para uno de los proveedores/transportistas seleccionados en este año y mes.',
                    ]);
            }
        }

        DB::transaction(function () use (
            $data,
            $anio,
            $mes,
            $codigoComision,
            $debeGuardarCantidadMensual,
            $comisiones
        ) {
            if ($debeGuardarCantidadMensual) {
                $asignacionCantidad = Asignaciones::findOrFail($data['cantidad_mensual_asignacion_id']);

                $codigoCantidad = $asignacionCantidad->codigo;
                $costoCantidad = (int) $asignacionCantidad->costo;
                $cantidadMensual = (int) $data['cantidad_mensual_cantidad'];
                $totalCantidad = $costoCantidad * $cantidadMensual;

                SuscripcionCantidadMensual::create([
                    'suscripcion_asignacion_id' => $asignacionCantidad->id,
                    'anio' => $anio,
                    'mes' => $mes,
                    'codigo' => $codigoCantidad,
                    'costo' => $costoCantidad,
                    'cantidad' => $cantidadMensual,
                    'total' => $totalCantidad,
                    'observacion' => $data['cantidad_mensual_observacion'] ?? null,
                ]);
            }

            foreach ($comisiones as $comision) {
                $costoComision = (int) $comision['costo'];
                $cantidadComision = 1;
                $totalComision = $costoComision * $cantidadComision;

                $grupoPrefactura = Asignaciones::query()
                    ->where('suscripcion_proveedor_id', $comision['suscripcion_proveedor_id'])
                    ->where('suscripcion_transportista_id', $comision['suscripcion_transportista_id'])
                    ->whereNotNull('grupo_prefactura')
                    ->whereRaw("TRIM(grupo_prefactura) <> ''")
                    ->orderBy('id')
                    ->value('grupo_prefactura');

                $asignacionComision = Asignaciones::create([
                    'suscripcion_proveedor_id' => (int) $comision['suscripcion_proveedor_id'],
                    'suscripcion_transportista_id' => (int) $comision['suscripcion_transportista_id'],
                    'punto_1' => $comision['punto_1'] ?? null,
                    'origen_gasto' => $comision['origen_gasto'] ?? 'Suscripciones',
                    'punto_2' => $comision['punto_2'] ?? null,
                    'codigo' => $codigoComision,
                    'servicio' => $comision['servicio'] ?? 'Comisión mensual',
                    'costo' => $costoComision,
                    'grupo_prefactura' => $grupoPrefactura,
                    'generar_automaticamente' => 0,
                ]);

                SuscripcionComisionMensual::create([
                    'suscripcion_asignacion_id' => $asignacionComision->id,
                    'anio' => $anio,
                    'mes' => $mes,
                    'codigo' => $codigoComision,
                    'costo' => $costoComision,
                    'cantidad' => $cantidadComision,
                    'total' => $totalComision,
                    'observacion' => $comision['observacion'] ?? null,
                ]);
            }
        });

        $resultadoRegistroAjustes = [
            'recibidos' => 0,
            'creados' => 0,
            'actualizados' => 0,
            'omitidos' => 0,
            'asignaciones_creadas' => 0,
            'asignaciones_reutilizadas' => 0,
        ];

        if ($ajustesMensuales->isNotEmpty()) {
            $resultadoRegistroAjustes = $ajusteMensualRegistroService->guardarDesdeFormulario(
                $ajustesMensuales->all(),
                $anio,
                $mes
            );
        }

        $resultado = $generacionMensualService->generar($anio, $mes);

        $resultadoAjustes = $ajusteMensualAplicacionService->aplicarPeriodo($anio, $mes);

        $mensaje = "Datos registrados correctamente. Mes generado correctamente. Creados: {$resultado['creados']}.";

        if ($comisiones->count() > 0) {
            $mensaje .= " Comisiones registradas previamente: {$comisiones->count()}.";
        }

        if (($resultado['cantidades_creadas'] ?? 0) > 0) {
            $mensaje .= " Cantidades variables agregadas: {$resultado['cantidades_creadas']}.";
        }

        if (($resultado['comisiones_creadas'] ?? 0) > 0) {
            $mensaje .= " Comisiones agregadas: {$resultado['comisiones_creadas']}.";
        }

        if (($resultadoRegistroAjustes['recibidos'] ?? 0) > 0) {
            $mensaje .= " Novedades mensuales recibidas: {$resultadoRegistroAjustes['recibidos']}.";

            if (($resultadoRegistroAjustes['creados'] ?? 0) > 0) {
                $mensaje .= " Ajustes creados: {$resultadoRegistroAjustes['creados']}.";
            }

            if (($resultadoRegistroAjustes['actualizados'] ?? 0) > 0) {
                $mensaje .= " Ajustes actualizados: {$resultadoRegistroAjustes['actualizados']}.";
            }

            if (($resultadoRegistroAjustes['asignaciones_creadas'] ?? 0) > 0) {
                $mensaje .= " Asignaciones contenedoras creadas: {$resultadoRegistroAjustes['asignaciones_creadas']}.";
            }

            if (($resultadoRegistroAjustes['asignaciones_reutilizadas'] ?? 0) > 0) {
                $mensaje .= " Asignaciones contenedoras reutilizadas: {$resultadoRegistroAjustes['asignaciones_reutilizadas']}.";
            }

            if (($resultadoRegistroAjustes['omitidos'] ?? 0) > 0) {
                $mensaje .= " Novedades omitidas: {$resultadoRegistroAjustes['omitidos']}.";
            }
        }

        if ($resultado['duplicados'] > 0) {
            $mensaje .= " Registros ya existentes no duplicados: {$resultado['duplicados']}.";
        }

        if (($resultado['cantidades_duplicadas'] ?? 0) > 0) {
            $mensaje .= " Cantidades variables ya existentes no duplicadas: {$resultado['cantidades_duplicadas']}.";
        }

        if (($resultado['comisiones_duplicadas'] ?? 0) > 0) {
            $mensaje .= " Comisiones ya existentes no duplicadas: {$resultado['comisiones_duplicadas']}.";
        }

        if (($resultadoAjustes['ajustes_procesados'] ?? 0) > 0) {
            $mensaje .= " Ajustes mensuales procesados: {$resultadoAjustes['ajustes_procesados']}.";

            if (($resultadoAjustes['detalles_actualizados'] ?? 0) > 0) {
                $mensaje .= " Detalles actualizados por ajustes: {$resultadoAjustes['detalles_actualizados']}.";
            }

            if (($resultadoAjustes['lineas_adicionales_creadas'] ?? 0) > 0) {
                $mensaje .= " Líneas adicionales creadas: {$resultadoAjustes['lineas_adicionales_creadas']}.";
            }

            if (($resultadoAjustes['lineas_adicionales_actualizadas'] ?? 0) > 0) {
                $mensaje .= " Líneas adicionales actualizadas: {$resultadoAjustes['lineas_adicionales_actualizadas']}.";
            }

            if (($resultadoAjustes['facturacion_registrada'] ?? 0) > 0) {
                $mensaje .= " Ajustes de facturación considerados: {$resultadoAjustes['facturacion_registrada']}.";
            }

            if (($resultadoAjustes['sin_detalle'] ?? 0) > 0) {
                $mensaje .= " Ajustes sin detalle mensual asociado: {$resultadoAjustes['sin_detalle']}.";
            }

            if (($resultadoAjustes['ignorados'] ?? 0) > 0) {
                $mensaje .= " Ajustes ignorados por compatibilidad: {$resultadoAjustes['ignorados']}.";
            }
        }

        if ($resultado['opv_sin_rutas']->isNotEmpty()) {
            $mensaje .= ' No se generaron las siguientes rutas OPV porque no tienen locales OPV asignados: ';
            $mensaje .= $resultado['opv_sin_rutas']->unique()->implode('; ') . '.';
        }

        return redirect()
            ->route('suscripciones.liquidacion-detalles.index', [
                'anio' => $anio,
                'mes' => $mes,
            ])
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
                'FIJO_MENSUAL',
                'FACTURACION',
                'LINEA_ADICIONAL',
                'PAGO_ADICIONAL',
                'REEMPLAZO',
            ], true)) {






                $errores["ajustes_mensuales.$index.tipo_ajuste"] = "La novedad mensual #{$numero} tiene un tipo de ajuste no válido.";
                continue;
            }

            if (in_array($tipo, ['INASISTENCIA', 'FIJO_MENSUAL', 'FACTURACION'], true)) {
                if (empty($ajuste['suscripcion_asignacion_id'])) {
                    $errores["ajustes_mensuales.$index.suscripcion_asignacion_id"] = "La novedad mensual #{$numero} requiere una asignación existente.";
                }
            }

            if (in_array($tipo, ['LINEA_ADICIONAL', 'PAGO_ADICIONAL', 'REEMPLAZO'], true)) {
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
            }

            if ($tipo === 'INASISTENCIA') {
                if (!isset($ajuste['q_inasistencia']) || $ajuste['q_inasistencia'] === '') {
                    $errores["ajustes_mensuales.$index.q_inasistencia"] = "La novedad mensual #{$numero} requiere cantidad de inasistencias.";
                }
            }

            if ($tipo === 'FIJO_MENSUAL') {
                if (!isset($ajuste['costo']) || $ajuste['costo'] === '') {
                    $errores["ajustes_mensuales.$index.costo"] = "La novedad mensual #{$numero} requiere el valor mensual fijo.";
                }
            }


        }

        return $errores;
    }


}