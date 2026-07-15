@extends('layouts.app')

@section('content')
<div class="container">

    @php
        $meses = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];

        $asignacionesAjustesMensuales = $asignacionesAjustesMensuales ?? collect();
        $asignacionesFijasMensuales = $asignacionesFijasMensuales ?? collect();
        $conceptosPagoVariable = $conceptosPagoVariable ?? collect();
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Preparar generación mensual</h1>
            <div class="small text-muted">
                Define las cantidades variables, novedades mensuales y comisiones antes de generar el mes completo.
            </div>
        </div>

        <a href="{{ route('suscripciones.liquidacion-detalles.index', [
            'anio' => $anio,
            'mes' => $mes,
        ]) }}" class="link-secondary text-decoration-none">
            ← Volver
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Revisa los datos ingresados.</strong>

            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="alert alert-info">
        <strong>Flujo recomendado:</strong> primero define el periodo, revisa los pagos fijos automáticos,
        luego registra cantidades variables como LOTA, después agrega sólo las novedades reales del mes,
        registra comisiones si corresponde y finalmente presiona
        <strong>Guardar datos y generar mes completo</strong>.

        <div class="small mt-2 mb-0">
            Importante: una cantidad variable no debe cargarse como fijo mensual. Si LOTA cambia de proveedor facturador,
            carga la cantidad en <strong>Cantidades variables del mes</strong> y registra sólo el
            <strong>cambio de facturación</strong> en novedades.
        </div>
    </div>

    <form id="form-generacion-mensual" method="POST" action="{{ route('suscripciones.comisiones-mensuales.store') }}" data-long-loader>
        @csrf

        {{-- PERIODO --}}
        <div class="card mb-4">
            <div class="card-header">
                <strong>Periodo a generar</strong>
            </div>

            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="anio" class="form-label">Año</label>
                        <input
                            type="number"
                            name="anio"
                            id="anio"
                            class="form-control"
                            value="{{ old('anio', $anio) }}"
                            min="2020"
                            max="2100"
                            required
                        >
                    </div>

                    <div class="col-md-3">
                        <label for="mes" class="form-label">Mes</label>
                        <select name="mes" id="mes" class="form-select" required>
                            @foreach($meses as $numeroMes => $nombreMes)
                                <option
                                    value="{{ $numeroMes }}"
                                    @selected((int) old('mes', $mes) === (int) $numeroMes)
                                >
                                    {{ $nombreMes }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- CANTIDADES VARIABLES --}}
        @if($asignacionesCantidadMensual->isNotEmpty())
            <div class="card mb-4">
                <button
                    type="button"
                    class="card-header w-100 border-0 bg-light d-flex justify-content-between align-items-center text-start"
                    data-suscripcion-toggle="#panel-cantidades-variables"
                    aria-expanded="false"
                >
                    <span>
                        <strong>Cantidades variables del mes</strong>
                        <span class="small text-muted ms-2">
                            LOTA u otras cantidades informadas manualmente.
                        </span>
                    </span>

                    <span data-suscripcion-toggle-icon>⌄</span>
                </button>

                <div id="panel-cantidades-variables" class="card-body d-none">
                    <div class="alert alert-light border small mb-3">
                        <strong>Usa esta sección sólo para cantidades variables.</strong>
                        Ejemplo: LOTA se calcula por cantidad informada del mes, no por fines de semana.
                        No cargues aquí reemplazos, fijos mensuales, comisiones ni pagos variables.
                    </div>

                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label for="cantidad_mensual_asignacion_id" class="form-label">
                                Ruta / asignación
                            </label>

                            <select
                                name="cantidad_mensual_asignacion_id"
                                id="cantidad_mensual_asignacion_id"
                                class="form-select"
                            >
                                <option value="">Seleccionar ruta...</option>

                                @foreach($asignacionesCantidadMensual as $asignacion)
                                    @php
                                        $cobranza = $asignacion->suscripcionProveedor?->cobranzaCompra;
                                        $transportista = $asignacion->transportista;
                                    @endphp

                                    <option
                                        value="{{ $asignacion->id }}"
                                        data-costo="{{ (int) $asignacion->costo }}"
                                        data-codigo="{{ $asignacion->codigo }}"
                                        data-tipo-asignacion="{{ $asignacion->tipo_asignacion }}"
                                        @selected((int) old('cantidad_mensual_asignacion_id') === (int) $asignacion->id)
                                    >
                                        {{ $asignacion->codigo }}
                                        |
                                        {{ $cobranza?->razon_social ?? 'Sin proveedor' }}
                                        |
                                        {{ $transportista?->nombre_transportista ?? 'Sin transportista' }}
                                        |
                                        ${{ number_format($asignacion->costo, 0, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>

                            <div id="cantidad_variable_advertencia" class="form-text text-muted">
                                Selecciona una ruta variable y escribe la cantidad mensual informada.
                            </div>
                        </div>

                        <div class="col-md-2">
                            <label for="cantidad_mensual_cantidad" class="form-label">Cantidad</label>
                            <input
                                type="number"
                                name="cantidad_mensual_cantidad"
                                id="cantidad_mensual_cantidad"
                                class="form-control"
                                value="{{ old('cantidad_mensual_cantidad') }}"
                                min="1"
                            >
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Total estimado cantidad variable</label>
                            <input
                                type="text"
                                id="total_variable_estimado"
                                class="form-control"
                                value="$0"
                                disabled
                            >
                        </div>

                        <div class="col-md-12">
                            <label for="cantidad_mensual_observacion" class="form-label">
                                Observación cantidad variable
                            </label>
                            <input
                                type="text"
                                name="cantidad_mensual_observacion"
                                id="cantidad_mensual_observacion"
                                class="form-control"
                                value="{{ old('cantidad_mensual_observacion') }}"
                                placeholder="Ej: cantidad de repartos informada para este mes"
                            >
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- NOVEDADES MENSUALES --}}
        <div class="card mb-4">
            <button
                type="button"
                class="card-header w-100 border-0 bg-light d-flex justify-content-between align-items-center flex-wrap gap-2 text-start"
                data-suscripcion-toggle="#panel-novedades-mensuales"
                aria-expanded="false"
            >
                <span>
                    <strong>Novedades mensuales</strong>
                    <span class="small text-muted ms-2">
                        Inasistencias, cambios de facturación/reemplazos, nuevas rutas o pagos variables.
                    </span>
                </span>

                <span data-suscripcion-toggle-icon>⌄</span>
            </button>

            <div id="panel-novedades-mensuales" class="card-body d-none">
                <div class="alert alert-warning small mb-3">
                    <strong>Usa este bloque sólo para excepciones del periodo.</strong>
                    No reemplaza la sección de cantidades variables. Por ejemplo, LOTA se carga arriba con su cantidad mensual
                    y aquí sólo se registra si cambia proveedor facturador, documento o transportista efectivo.
                    Los <strong>pagos variables</strong> como compaginado, primera vuelta o segunda vuelta se agregan aquí
                    como una línea propia con <strong>tarifa</strong>, no como comisión ni como cantidad de ruta.
                </div>

                <div class="border rounded p-3 mb-3">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="ajuste_tipo_ajuste" class="form-label">Tipo de novedad</label>

                            <div class="d-flex gap-2">


                                <select id="ajuste_tipo_ajuste" class="form-select">
                                    <option value="">Seleccionar tipo...</option>
                                    <option value="INASISTENCIA">Inasistencia de ruta</option>

                                    {{-- <option value="FIJO_MENSUAL">Fijo mensual excepcional</option> --}}

                                    <option value="FACTURACION">Cambio de facturación en ruta existente</option>
                                    <option value="LINEA_ADICIONAL">Ruta adicional</option>
                                    <option value="PAGO_VARIABLE">Pago variable del mes</option>
                                </select>



                                <button
                                    type="button"
                                    id="btn-abrir-inasistencias-masivas"
                                    class="btn btn-outline-secondary text-nowrap d-none ml-2"
                                    data-toggle="modal"
                                    data-target="#modal-ajustes-masivos-inasistencia"
                                >
                                    Masivo
                                </button>

                                <button
                                    type="button"
                                    id="btn-abrir-facturaciones-masivas"
                                    class="btn btn-outline-secondary text-nowrap d-none ml-2"
                                    data-toggle="modal"
                                    data-target="#modal-ajustes-masivos-facturacion"
                                >
                                    Masivo
                                </button>

                                <button
                                    type="button"
                                    id="btn-abrir-lineas-adicionales-masivas"
                                    class="btn btn-outline-secondary text-nowrap d-none ml-2"
                                    data-toggle="modal"
                                    data-target="#modal-ajustes-masivos-linea-adicional"
                                >
                                    Masivo
                                </button>

                                <button
                                    type="button"
                                    id="btn-abrir-pagos-variables-masivos"
                                    class="btn btn-outline-secondary text-nowrap d-none ml-2"
                                    data-toggle="modal"
                                    data-target="#modal-ajustes-masivos-pago-variable"
                                >
                                    Masivo
                                </button>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Descripción del tipo seleccionado</label>
                            <input
                                type="text"
                                id="ajuste_tipo_descripcion"
                                class="form-control"
                                value="Selecciona un tipo de novedad para ver los campos necesarios."
                                disabled
                            >
                        </div>

                        <div class="col-md-12">
                            <div id="ajuste_guia_operativa" class="alert alert-light border small mb-0">
                                Selecciona un tipo de novedad. El formulario mostrará sólo los campos necesarios para evitar errores.
                            </div>
                        </div>

                        <div class="col-md-12 d-none" id="ajuste_advertencia_asignacion_wrapper">
                            <div id="ajuste_advertencia_asignacion" class="alert alert-warning small mb-0"></div>
                        </div>

                        <div class="col-md-12 d-none" id="bloque-ajuste-asignacion">
                            <label for="ajuste_suscripcion_asignacion_id" class="form-label">
                                Asignación existente
                            </label>

                            <select id="ajuste_suscripcion_asignacion_id" class="form-select">
                                <option value="">Seleccionar asignación...</option>

                                @foreach($asignacionesAjustesMensuales as $asignacion)
                                    @php
                                        $cobranza = $asignacion->suscripcionProveedor?->cobranzaCompra;
                                        $transportista = $asignacion->transportista;

                                        $codigoAsignacionNormalizado = mb_strtoupper(trim((string) $asignacion->codigo));
                                        $servicioAsignacionNormalizado = mb_strtoupper(trim((string) $asignacion->servicio));
                                        $origenAsignacionNormalizado = mb_strtoupper(trim((string) $asignacion->origen_gasto));
                                        $generarAutomaticamente = $asignacion->generar_automaticamente;

                                        $esAsignacionComision = str_ends_with($codigoAsignacionNormalizado, '.COM')
                                            || str_contains($codigoAsignacionNormalizado, 'COMISION');

                                        $esAsignacionOpv = $codigoAsignacionNormalizado === 'OPV'
                                            || str_ends_with($codigoAsignacionNormalizado, '.OPV')
                                            || $servicioAsignacionNormalizado === 'OPV'
                                            || $origenAsignacionNormalizado === 'OPV';

                                        $esAsignacionCantidadVariable = !$esAsignacionComision
                                            && !$esAsignacionOpv
                                            && str_contains($codigoAsignacionNormalizado, 'LOTA');

                                        $esAsignacionNoAutomatica = (string) $generarAutomaticamente === '0';

                                        $asignacionLabel = trim(
                                            ($asignacion->codigo ?? 'Sin código')
                                            . ' | '
                                            . ($cobranza?->razon_social ?? 'Sin proveedor')
                                            . ' | '
                                            . ($transportista?->nombre_transportista ?? 'Sin transportista')
                                            . ' | $'
                                            . number_format((int) $asignacion->costo, 0, ',', '.')
                                        );
                                    @endphp

                                    <option
                                        value="{{ $asignacion->id }}"
                                        data-label="{{ $asignacionLabel }}"
                                        data-codigo="{{ $asignacion->codigo }}"
                                        data-costo="{{ (int) $asignacion->costo }}"
                                        data-punto-1="{{ $asignacion->punto_1 }}"
                                        data-origen-gasto="{{ $asignacion->origen_gasto }}"
                                        data-punto-2="{{ $asignacion->punto_2 }}"
                                        data-servicio="{{ $asignacion->servicio }}"
                                        data-grupo-prefactura="{{ $asignacion->grupo_prefactura }}"
                                        data-generar-automaticamente="{{ $generarAutomaticamente }}"
                                        data-tipo-asignacion="{{ $asignacion->tipo_asignacion }}"
                                        data-es-comision="{{ $esAsignacionComision ? 1 : 0 }}"
                                        data-es-opv="{{ $esAsignacionOpv ? 1 : 0 }}"
                                        data-es-cantidad-variable="{{ $esAsignacionCantidadVariable ? 1 : 0 }}"
                                        data-es-no-automatica="{{ $esAsignacionNoAutomatica ? 1 : 0 }}"
                                    >
                                        {{ $asignacionLabel }}
                                    </option>
                                @endforeach
                            </select>

                            <div class="form-text" id="ajuste_asignacion_ayuda">
                                Selecciona la ruta original. El listado se ajusta según el tipo de novedad.
                            </div>
                        </div>

                        <div class="col-md-6 d-none" id="bloque-ajuste-proveedor">
                            <label for="ajuste_suscripcion_proveedor_id" class="form-label">
                                Proveedor de la novedad
                            </label>

                            <select id="ajuste_suscripcion_proveedor_id" class="form-select">
                                <option value="">Seleccionar proveedor...</option>

                                @foreach($proveedores as $proveedor)
                                    @php
                                        $cobranza = $proveedor->cobranzaCompra;

                                        $proveedorLabel = trim(
                                            ($cobranza?->razon_social ?? 'Sin razón social')
                                            . ' | '
                                            . ($cobranza?->rut_cliente ?? 'Sin RUT')
                                            . ' | '
                                            . ($proveedor->tipo ?? 'Sin tipo')
                                        );
                                    @endphp

                                    <option
                                        value="{{ $proveedor->id }}"
                                        data-label="{{ $proveedorLabel }}"
                                        data-tipo="{{ $proveedor->tipo }}"
                                        data-detalle-documento="{{ $proveedor->detalle_documento }}"
                                        data-detalle-impuesto="{{ $proveedor->detalle_impuesto }}"
                                        data-final="{{ $proveedor->final }}"
                                    >
                                        {{ $proveedorLabel }}
                                    </option>
                                @endforeach
                            </select>

                            <div class="form-text">
                                Para nueva ruta o pago variable. En pago variable será quien recibe la línea con tarifa.
                            </div>
                        </div>

                        <div class="col-md-6 d-none" id="bloque-ajuste-transportista">
                            <label for="ajuste_suscripcion_transportista_id" class="form-label">
                                Transportista relacionado
                            </label>

                            <select id="ajuste_suscripcion_transportista_id" class="form-select">
                                <option value="">Seleccionar transportista...</option>

                                @foreach($transportistas as $transportista)
                                    <option
                                        value="{{ $transportista->id }}"
                                        data-label="{{ $transportista->nombre_transportista }}"
                                    >
                                        {{ $transportista->nombre_transportista }}
                                    </option>
                                @endforeach
                            </select>

                            <div class="form-text">
                                Opcional para pago variable; recomendable para nuevas rutas cuando el transportista efectivo importa.
                            </div>
                        </div>

                        <div class="col-md-6 d-none" id="bloque-ajuste-concepto-pago-variable">
                            <label for="ajuste_concepto_pago_variable_id" class="form-label">
                                Concepto de pago variable
                            </label>

                            <select id="ajuste_concepto_pago_variable_id" class="form-select">
                                <option value="">Seleccionar concepto...</option>

                                @foreach($conceptosPagoVariable as $concepto)
                                    <option
                                        value="{{ $concepto->id }}"
                                        data-codigo="{{ $concepto->codigo }}"
                                        data-nombre="{{ $concepto->nombre }}"
                                        data-descripcion="{{ $concepto->descripcion }}"
                                    >
                                        {{ $concepto->nombre }}
                                    </option>
                                @endforeach

                                <option
                                    value="__OTRO__"
                                    data-codigo="OTRO"
                                    data-nombre="Otro"
                                >
                                    Otro concepto
                                </option>
                            </select>

                            <div class="form-text">
                                Ejemplo: Compaginado, primera vuelta, segunda vuelta o reposición.
                                La tarifa se ingresa en el campo <strong>Costo / tarifa</strong>.
                            </div>
                        </div>

                        <div class="col-md-6 d-none" id="bloque-ajuste-concepto-pago-variable-manual">
                            <label for="ajuste_concepto_pago_variable_manual" class="form-label">
                                Concepto manual
                            </label>

                            <input
                                type="text"
                                id="ajuste_concepto_pago_variable_manual"
                                class="form-control"
                                placeholder="Ej: Reposición, apoyo de ruta, entrega especial"
                            >

                            <div class="form-text">
                                Se usará sólo si seleccionas “Otro concepto”.
                            </div>
                        </div>

                        <div class="col-md-6 d-none" id="bloque-ajuste-proveedor-facturacion">
                            <label for="ajuste_suscripcion_proveedor_facturacion_id" class="form-label">
                                Proveedor facturador efectivo
                            </label>

                            <select id="ajuste_suscripcion_proveedor_facturacion_id" class="form-select">
                                <option value="">Seleccionar proveedor facturador...</option>

                                @foreach($proveedores as $proveedor)
                                    @php
                                        $cobranza = $proveedor->cobranzaCompra;

                                        $proveedorLabel = trim(
                                            ($cobranza?->razon_social ?? 'Sin razón social')
                                            . ' | '
                                            . ($cobranza?->rut_cliente ?? 'Sin RUT')
                                            . ' | '
                                            . ($proveedor->tipo ?? 'Sin tipo')
                                        );
                                    @endphp

                                    <option
                                        value="{{ $proveedor->id }}"
                                        data-label="{{ $proveedorLabel }}"
                                        data-tipo="{{ $proveedor->tipo }}"
                                        data-detalle-documento="{{ $proveedor->detalle_documento }}"
                                        data-detalle-impuesto="{{ $proveedor->detalle_impuesto }}"
                                        data-final="{{ $proveedor->final }}"
                                    >
                                        {{ $proveedorLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 d-none" id="bloque-ajuste-transportista-override">
                            <label for="ajuste_suscripcion_transportista_override_id" class="form-label">
                                Transportista efectivo opcional
                            </label>

                            <select id="ajuste_suscripcion_transportista_override_id" class="form-select">
                                <option value="">Mantener transportista original...</option>

                                @foreach($transportistas as $transportista)
                                    <option
                                        value="{{ $transportista->id }}"
                                        data-label="{{ $transportista->nombre_transportista }}"
                                    >
                                        {{ $transportista->nombre_transportista }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="ajuste_punto_1" class="form-label">Punto</label>
                            <input
                                type="text"
                                id="ajuste_punto_1"
                                class="form-control"
                                placeholder="Ej: 17, VITACURA, ÑUÑOA"
                            >
                        </div>

                        <div class="col-md-3">
                            <label for="ajuste_origen_gasto" class="form-label">Origen gasto</label>
                            <input
                                type="text"
                                id="ajuste_origen_gasto"
                                class="form-control"
                                value="Suscripciones"
                            >
                        </div>

                        <div class="col-md-3">
                            <label for="ajuste_punto_2" class="form-label">Punto 2</label>
                            <input
                                type="text"
                                id="ajuste_punto_2"
                                class="form-control"
                                placeholder="Ej: BELLAVISTA"
                            >
                        </div>

                        <div class="col-md-3">
                            <label for="ajuste_codigo" class="form-label">Código</label>
                            <input
                                type="text"
                                id="ajuste_codigo"
                                class="form-control"
                                placeholder="Ej: PV-COMPAGINADO"
                            >
                        </div>

                        <div class="col-md-4">
                            <label for="ajuste_servicio" class="form-label">Servicio</label>
                            <input
                                type="text"
                                id="ajuste_servicio"
                                class="form-control"
                                value="Reparto fin de semana"
                            >
                        </div>

                        {{-- <div class="col-md-4">
                            <label for="ajuste_grupo_prefactura" class="form-label">Grupo prefactura opcional</label>
                            <input
                                type="text"
                                id="ajuste_grupo_prefactura"
                                class="form-control"
                                placeholder="Ej: LA DEHESA / GENERAL"
                            >
                        </div> --}}

                        <div class="col-md-4">
                            <label for="ajuste_costo" id="ajuste_costo_label" class="form-label">
                                Costo / tarifa neta
                            </label>

                            <input
                                type="number"
                                id="ajuste_costo"
                                class="form-control"
                                min="0"
                                placeholder="Ej: 50000"
                            >

                            <div id="ajuste_costo_ayuda" class="form-text">
                                Pago variable: ingresa la <strong>tarifa neta de impuesto</strong>.
                                El sistema calculará impuesto o retención según el tipo de documento del proveedor.
                                Nueva ruta: ingresa el costo unitario.
                                Cambio de facturación: úsalo sólo si debes corregir el costo del mes.
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label for="ajuste_q_calendario" class="form-label">Q calendario</label>
                            <input
                                type="number"
                                id="ajuste_q_calendario"
                                class="form-control"
                                min="0"
                                placeholder="Opcional"
                            >
                        </div>

                        <div class="col-md-3">
                            <label for="ajuste_q_inasistencia" class="form-label">Q inasistencia</label>
                            <input
                                type="number"
                                id="ajuste_q_inasistencia"
                                class="form-control"
                                min="0"
                                placeholder="Ej: 5"
                            >
                        </div>

                        <div class="col-md-3">
                            <label for="ajuste_cantidad" class="form-label">Cantidad</label>
                            <input
                                type="number"
                                id="ajuste_cantidad"
                                class="form-control"
                                min="0"
                                placeholder="Opcional"
                            >
                            <div class="form-text">
                                Para nuevas rutas. En pago variable se usa cantidad técnica 1.
                            </div>
                        </div>

                        {{-- <div class="col-md-3">
                            <label for="ajuste_total" class="form-label">Total manual opcional</label>
                            <input
                                type="number"
                                id="ajuste_total"
                                class="form-control"
                                min="0"
                                placeholder="Opcional"
                            >
                        </div> --}}

                        <div class="col-md-4">
                            <label for="ajuste_tipo_documento" class="form-label">Tipo documento</label>
                            <input
                                type="text"
                                id="ajuste_tipo_documento"
                                class="form-control"
                                placeholder="FACTURA / BOLETA / DOCUMENTO"
                            >
                        </div>

                        <div class="col-md-4">
                            <label for="ajuste_detalle_documento" class="form-label">Detalle documento</label>
                            <input
                                type="text"
                                id="ajuste_detalle_documento"
                                class="form-control"
                                placeholder="NETO / BRUTO"
                            >
                        </div>

                        <div class="col-md-4">
                            <label for="ajuste_detalle_impuesto" class="form-label">Detalle impuesto</label>
                            <input
                                type="text"
                                id="ajuste_detalle_impuesto"
                                class="form-control"
                                placeholder="IMPUESTO / RETENCION"
                            >
                        </div>

                        <div class="col-md-4">
                            <label for="ajuste_final" class="form-label">Final</label>
                            <input
                                type="text"
                                id="ajuste_final"
                                class="form-control"
                                placeholder="TOTAL / LIQUIDO A PAGAR"
                            >
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Total estimado novedad</label>
                            <input
                                type="text"
                                id="ajuste_total_estimado"
                                class="form-control"
                                value="$0"
                                disabled
                            >
                            <div class="form-text">
                                Pago variable: el total estimado corresponde a la tarifa. Nueva ruta:
                                si dejas el total manual vacío, el backend calculará costo × cantidad.
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="ajuste_observacion" class="form-label">Observación</label>
                            <input
                                type="text"
                                id="ajuste_observacion"
                                class="form-control"
                                placeholder="Ej: NUEVA RUTA MAYO 2026 / COMPAGINADO MAYO 2026"
                            >
                        </div>

                        <div class="col-md-12 d-flex justify-content-end">
                            <button type="button" id="btn-agregar-ajuste" class="btn btn-outline-primary">
                                Agregar novedad mensual
                            </button>
                        </div>
                    </div>
                </div>

                <div id="ajustes-hidden-container"></div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-2">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Asignación base / proveedor</th>
                                <th>Detalle del cambio</th>
                                <th>Código</th>
                                <th class="text-end">Cantidad / Q</th>
                                <th class="text-end">Total estimado</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>

                        <tbody id="ajustes-resumen-body">
                            <tr>
                                <td colspan="7" class="text-muted text-center">
                                    No hay novedades mensuales agregadas para este periodo.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="small text-muted mt-3">
                    Novedades agregadas:
                    <strong id="ajustes-cantidad">0</strong>
                    <span class="mx-1">|</span>
                    Total estimado:
                    <strong id="ajustes-total">$0</strong>
                </div>
            </div>
        </div>

        @include('suscripciones.comisiones_mensuales.partials.modal-ajustes-masivos-inasistencia')
        @include('suscripciones.comisiones_mensuales.partials.modal-ajustes-masivos-facturacion')
        @include('suscripciones.comisiones_mensuales.partials.modal-ajustes-masivos-linea-adicional')
        @include('suscripciones.comisiones_mensuales.partials.modal-ajustes-masivos-pago-variable')





    {{-- COMISIONES --}}
    {{-- PAGOS ADICIONALES --}}
    <div class="card mb-4">
        <button
            type="button"
            class="card-header w-100 border-0 bg-light d-flex justify-content-between align-items-center flex-wrap gap-2 text-start"
            data-suscripcion-toggle="#panel-comisiones-mensuales"
            aria-expanded="false"
        >
            <span>
                <strong>Pagos adicionales del mes</strong>

                <span class="small text-muted ms-2">
                    Agrega pagos adicionales sólo si corresponde para este periodo.
                </span>
            </span>

            <span data-suscripcion-toggle-icon>⌄</span>
        </button>

        {{-- CONTENIDO DEL ACORDEÓN --}}
        <div
            id="panel-comisiones-mensuales"
            class="card-body d-none"
        >
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div>
                    <div class="small text-muted mb-1">
                        Los pagos adicionales del mes se agregan exclusivamente
                        desde la carga masiva.
                    </div>

                    <div class="small text-muted">
                        Cada pago utiliza una tarifa y una cantidad.
                        El total se calcula multiplicando tarifa × cantidad.
                    </div>
                </div>

                <button
                    type="button"
                    id="btn-abrir-comisiones-masivas"
                    class="btn btn-outline-primary btn-sm"
                    data-toggle="modal"
                    data-target="#modal-comisiones-masivas"
                >
                    Carga masiva de pagos adicionales
                </button>
            </div>

            {{-- INPUTS OCULTOS GENERADOS POR EL JS --}}
            <div id="comisiones-hidden-container"></div>

            {{-- RESUMEN DE PAGOS AGREGADOS --}}
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-2">
                    <thead>
                        <tr>
                            <th>
                                Proveedor
                            </th>

                            <th>
                                Transportista
                            </th>

                            <th class="text-end">
                                Tarifa
                            </th>

                            <th class="text-end">
                                Cantidad
                            </th>

                            <th class="text-end">
                                Total
                            </th>

                            <th>
                                Observación
                            </th>

                            <th class="text-center">
                                Acción
                            </th>
                        </tr>
                    </thead>

                    <tbody id="comisiones-resumen-body">
                        <tr>
                            <td
                                colspan="7"
                                class="text-muted text-center"
                            >
                                No hay pagos adicionales agregados para este periodo.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="small text-muted mt-3">
                Pagos adicionales agregados:

                <strong id="comisiones-cantidad">
                    0
                </strong>

                <span class="mx-1">|</span>

                Total estimado:

                <strong id="comisiones-total">
                    $0
                </strong>
            </div>
        </div>
    </div>




     @include('suscripciones.comisiones_mensuales.partials.modal-comisiones-masivas')





        <div class="d-flex justify-content-end align-items-center mb-4">
            <button type="submit" class="btn btn-primary">
                Guardar datos y generar mes completo
            </button>
        </div>
    </form>
</div>

<script>
    window.suscripcionesGeneracionMensual = {
        comisionesIniciales: @json(collect(old('comisiones', []))->values()->all()),
        ajustesIniciales: @json(collect(old('ajustes_mensuales', []))->values()->all()),
    };
</script>

@vite('resources/js/suscripciones/generacion-mensual.js')
@endsection