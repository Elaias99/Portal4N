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
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Preparar generación mensual</h1>
            <div class="small text-muted">
                Define las cantidades variables, comisiones y novedades mensuales antes de generar el mes completo.
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
        Completa los datos necesarios para el periodo. Al presionar
        <strong>Guardar datos y generar mes completo</strong>, el sistema registrará la cantidad variable si corresponde,
        registrará las comisiones agregadas si existen, registrará las novedades mensuales y luego generará el mes.
    </div>

    <form id="form-generacion-mensual" method="POST" action="{{ route('suscripciones.comisiones-mensuales.store') }}">
        @csrf

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

        @if($asignacionesCantidadMensual->isNotEmpty())
            <div class="card mb-4">
                <div class="card-header">
                    <strong>Cantidades variables del mes</strong>
                </div>

                <div class="card-body">
                    <div class="small text-muted mb-3">
                        Registra aquí las rutas que no se calculan por calendario, por ejemplo LOTA.
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

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <strong>Pagos adicionales / comisiones del mes</strong>

                <span class="small text-muted">
                    Opcional: agrega una o varias comisiones.
                </span>
            </div>

            <div class="card-body">
                <div class="small text-muted mb-3">
                    Completa los datos de una comisión y presiona <strong>Agregar comisión</strong>.
                    La comisión quedará en la lista inferior antes de generar el mes.
                </div>

                <div class="border rounded p-3 mb-3">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label for="comision_proveedor_id" class="form-label">
                                Proveedor a integrar comisión
                            </label>

                            <select id="comision_proveedor_id" class="form-select">
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
                        </div>

                        <div class="col-md-6">
                            <label for="comision_transportista_id" class="form-label">
                                Transportista
                            </label>

                            <select id="comision_transportista_id" class="form-select">
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
                        </div>

                        <div class="col-md-3">
                            <label for="comision_punto_1" class="form-label">Punto</label>
                            <input
                                type="text"
                                id="comision_punto_1"
                                class="form-control"
                                placeholder="Ej: LA DEHESA"
                            >
                        </div>

                        <div class="col-md-3">
                            <label for="comision_origen_gasto" class="form-label">Origen gasto</label>
                            <input
                                type="text"
                                id="comision_origen_gasto"
                                class="form-control"
                                value="Suscripciones"
                            >
                        </div>

                        <div class="col-md-3">
                            <label for="comision_punto_2" class="form-label">Punto 2</label>
                            <input
                                type="text"
                                id="comision_punto_2"
                                class="form-control"
                                placeholder="Ej: LA DEHESA"
                            >
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Código comisión</label>
                            <input
                                type="text"
                                class="form-control"
                                value="COMISION"
                                disabled
                            >
                            <div class="form-text">
                                Definido automáticamente por el sistema.
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="comision_servicio" class="form-label">Servicio</label>
                            <input
                                type="text"
                                id="comision_servicio"
                                class="form-control"
                                value="Reparto fin de semana"
                            >
                        </div>

                        <div class="col-md-4">
                            <label for="comision_costo" class="form-label">Costo comisión</label>
                            <input
                                type="number"
                                id="comision_costo"
                                class="form-control"
                                min="0"
                            >
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Total estimado comisión actual</label>
                            <input
                                type="text"
                                id="comision_total_estimado"
                                class="form-control"
                                value="$0"
                                disabled
                            >
                            <div class="form-text">
                                Las comisiones siempre se registran con cantidad 1.
                            </div>
                        </div>

                        <div class="col-md-9">
                            <label for="comision_observacion" class="form-label">
                                Observación comisión
                            </label>
                            <input
                                type="text"
                                id="comision_observacion"
                                class="form-control"
                                placeholder="Ej: comisión informada para este mes"
                            >
                        </div>

                        <div class="col-md-3 d-flex align-items-end">
                            <button type="button" id="btn-agregar-comision" class="btn btn-outline-primary w-100">
                                Agregar comisión
                            </button>
                        </div>

                    </div>
                </div>

                <div id="comisiones-hidden-container"></div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-2">
                        <thead>
                            <tr>
                                <th>Proveedor</th>
                                <th>Transportista</th>
                                <th>Punto</th>
                                <th>Servicio</th>
                                <th class="text-end">Costo</th>
                                <th>Observación</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>

                        <tbody id="comisiones-resumen-body">
                            <tr>
                                <td colspan="7" class="text-muted text-center">
                                    No hay comisiones agregadas para este periodo.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="small text-muted mt-3">
                    Comisiones agregadas:
                    <strong id="comisiones-cantidad">0</strong>
                    <span class="mx-1">|</span>
                    Total estimado:
                    <strong id="comisiones-total">$0</strong>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <strong>Novedades mensuales</strong>

                <span class="small text-muted">
                    Opcional: registra inasistencias, cambios de facturación o líneas adicionales del mes.
                </span>
            </div>

            <div class="card-body">
                <div class="alert alert-warning small mb-3">
                    Usa este bloque sólo para sucesos especiales del periodo. El sistema guardará estas novedades en
                    <strong>suscripcion_ajustes_mensuales</strong> y luego las aplicará sobre el detalle mensual generado.
                </div>

                <div class="border rounded p-3 mb-3">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="ajuste_tipo_ajuste" class="form-label">Tipo de novedad</label>


                            <select id="ajuste_tipo_ajuste" class="form-select">
                                <option value="">Seleccionar tipo...</option>
                                <option value="INASISTENCIA">Inasistencia</option>
                                <option value="FIJO_MENSUAL">Fijo mensual</option>
                                <option value="FACTURACION">Cambio de facturación</option>
                                <option value="LINEA_ADICIONAL">Línea adicional</option>
                                <option value="PAGO_ADICIONAL">Pago adicional</option>
                                <option value="REEMPLAZO">Reemplazo</option>
                            </select>



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
                                    >
                                        {{ $asignacionLabel }}
                                    </option>
                                @endforeach
                            </select>

                            <div class="form-text">
                                Para inasistencias y cambios de facturación, selecciona la ruta original existente.
                            </div>
                        </div>

                        <div class="col-md-6 d-none" id="bloque-ajuste-proveedor">
                            <label for="ajuste_suscripcion_proveedor_id" class="form-label">
                                Proveedor de la línea adicional
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
                        </div>

                        <div class="col-md-6 d-none" id="bloque-ajuste-transportista">
                            <label for="ajuste_suscripcion_transportista_id" class="form-label">
                                Transportista de la línea adicional
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
                                placeholder="Ej: VA03 Y VA04"
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

                        <div class="col-md-4">
                            <label for="ajuste_grupo_prefactura" class="form-label">Grupo prefactura opcional</label>
                            <input
                                type="text"
                                id="ajuste_grupo_prefactura"
                                class="form-control"
                                placeholder="Ej: LA DEHESA / GENERAL"
                            >
                        </div>

                        <div class="col-md-4">
                            <label for="ajuste_costo" class="form-label">Costo</label>
                            <input
                                type="number"
                                id="ajuste_costo"
                                class="form-control"
                                min="0"
                            >
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
                        </div>

                        <div class="col-md-3">
                            <label for="ajuste_total" class="form-label">Total manual opcional</label>
                            <input
                                type="number"
                                id="ajuste_total"
                                class="form-control"
                                min="0"
                                placeholder="Opcional"
                            >
                        </div>

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
                                Si dejas el total manual vacío, el backend calculará costo × cantidad cuando corresponda.
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="ajuste_observacion" class="form-label">Observación</label>
                            <input
                                type="text"
                                id="ajuste_observacion"
                                class="form-control"
                                placeholder="Ej: REEMPLAZO VA03 Y VA04 MAYO 2026"
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
                                <th>Base / proveedor</th>
                                <th>Código</th>
                                <th>Servicio</th>
                                <th class="text-end">Cantidad</th>
                                <th class="text-end">Total estimado</th>
                                <th>Observación</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>

                        <tbody id="ajustes-resumen-body">
                            <tr>
                                <td colspan="8" class="text-muted text-center">
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

        <div class="d-flex justify-content-end align-items-center mb-4">
            <button type="submit" class="btn btn-primary">
                Guardar datos y generar mes completo
            </button>
        </div>
    </form>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const asignacionCantidadSelect = document.getElementById('cantidad_mensual_asignacion_id');
        const cantidadVariableInput = document.getElementById('cantidad_mensual_cantidad');
        const totalVariableInput = document.getElementById('total_variable_estimado');

        const proveedorSelect = document.getElementById('comision_proveedor_id');
        const transportistaSelect = document.getElementById('comision_transportista_id');
        const punto1Input = document.getElementById('comision_punto_1');
        const origenGastoInput = document.getElementById('comision_origen_gasto');
        const punto2Input = document.getElementById('comision_punto_2');
        const servicioInput = document.getElementById('comision_servicio');
        const costoInput = document.getElementById('comision_costo');
        const totalComisionInput = document.getElementById('comision_total_estimado');
        const observacionInput = document.getElementById('comision_observacion');

        const agregarComisionBtn = document.getElementById('btn-agregar-comision');
        const hiddenContainer = document.getElementById('comisiones-hidden-container');
        const resumenBody = document.getElementById('comisiones-resumen-body');
        const cantidadComisiones = document.getElementById('comisiones-cantidad');
        const totalComisiones = document.getElementById('comisiones-total');

        const ajusteTipoSelect = document.getElementById('ajuste_tipo_ajuste');
        const ajusteTipoDescripcion = document.getElementById('ajuste_tipo_descripcion');

        const bloqueAjusteAsignacion = document.getElementById('bloque-ajuste-asignacion');
        const bloqueAjusteProveedor = document.getElementById('bloque-ajuste-proveedor');
        const bloqueAjusteTransportista = document.getElementById('bloque-ajuste-transportista');
        const bloqueAjusteProveedorFacturacion = document.getElementById('bloque-ajuste-proveedor-facturacion');
        const bloqueAjusteTransportistaOverride = document.getElementById('bloque-ajuste-transportista-override');

        const ajusteAsignacionSelect = document.getElementById('ajuste_suscripcion_asignacion_id');
        const ajusteProveedorSelect = document.getElementById('ajuste_suscripcion_proveedor_id');
        const ajusteTransportistaSelect = document.getElementById('ajuste_suscripcion_transportista_id');
        const ajusteProveedorFacturacionSelect = document.getElementById('ajuste_suscripcion_proveedor_facturacion_id');
        const ajusteTransportistaOverrideSelect = document.getElementById('ajuste_suscripcion_transportista_override_id');

        const ajustePunto1Input = document.getElementById('ajuste_punto_1');
        const ajusteOrigenGastoInput = document.getElementById('ajuste_origen_gasto');
        const ajustePunto2Input = document.getElementById('ajuste_punto_2');
        const ajusteCodigoInput = document.getElementById('ajuste_codigo');
        const ajusteServicioInput = document.getElementById('ajuste_servicio');
        const ajusteGrupoPrefacturaInput = document.getElementById('ajuste_grupo_prefactura');
        const ajusteCostoInput = document.getElementById('ajuste_costo');
        const ajusteQCalendarioInput = document.getElementById('ajuste_q_calendario');
        const ajusteQInasistenciaInput = document.getElementById('ajuste_q_inasistencia');
        const ajusteCantidadInput = document.getElementById('ajuste_cantidad');
        const ajusteTotalInput = document.getElementById('ajuste_total');

        const ajusteTipoDocumentoInput = document.getElementById('ajuste_tipo_documento');
        const ajusteDetalleDocumentoInput = document.getElementById('ajuste_detalle_documento');
        const ajusteDetalleImpuestoInput = document.getElementById('ajuste_detalle_impuesto');
        const ajusteFinalInput = document.getElementById('ajuste_final');

        const ajusteTotalEstimadoInput = document.getElementById('ajuste_total_estimado');
        const ajusteObservacionInput = document.getElementById('ajuste_observacion');
        const agregarAjusteBtn = document.getElementById('btn-agregar-ajuste');

        const ajustesHiddenContainer = document.getElementById('ajustes-hidden-container');
        const ajustesResumenBody = document.getElementById('ajustes-resumen-body');
        const cantidadAjustes = document.getElementById('ajustes-cantidad');
        const totalAjustes = document.getElementById('ajustes-total');

        let comisiones = [];
        let ajustesMensuales = [];

        const comisionesIniciales = @json(collect(old('comisiones', []))->values()->all());
        const ajustesIniciales = @json(collect(old('ajustes_mensuales', []))->values()->all());

        function formatearCLP(valor) {
            return '$' + new Intl.NumberFormat('es-CL').format(valor || 0);
        }

        function limpiarTexto(valor) {
            return String(valor || '').replace(/\s+/g, ' ').trim();
        }

        function escaparHtml(valor) {
            return String(valor || '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function optionLabel(select) {
            const option = select?.options[select.selectedIndex];

            if (!option) {
                return '—';
            }

            return limpiarTexto(option.dataset.label || option.text || '—');
        }

        function labelPorValor(select, valor) {
            const option = Array.from(select?.options || []).find(function (item) {
                return String(item.value) === String(valor);
            });

            if (!option) {
                return '—';
            }

            return limpiarTexto(option.dataset.label || option.text || '—');
        }

        function selectedOption(select) {
            return select?.options[select.selectedIndex] || null;
        }

        function normalizarTipo(valor) {
            return limpiarTexto(valor).toUpperCase().replaceAll(' ', '_').replaceAll('-', '_');
        }

        function esTipoLineaAdicional(tipo) {
            return ['LINEA_ADICIONAL', 'PAGO_ADICIONAL', 'REEMPLAZO'].includes(normalizarTipo(tipo));
        }

        function esTipoAsignacionExistente(tipo) {
            return ['INASISTENCIA', 'FIJO_MENSUAL', 'FACTURACION'].includes(normalizarTipo(tipo));
        }

        function actualizarTotalVariable() {
            if (!asignacionCantidadSelect || !cantidadVariableInput || !totalVariableInput) {
                return;
            }

            const option = selectedOption(asignacionCantidadSelect);
            const costo = parseInt(option?.dataset?.costo || 0, 10);
            const cantidad = parseInt(cantidadVariableInput.value || 0, 10);
            const total = costo * cantidad;

            totalVariableInput.value = formatearCLP(total);
        }

        function actualizarTotalComisionActual() {
            if (!costoInput || !totalComisionInput) {
                return;
            }

            const costo = parseInt(costoInput.value || 0, 10);

            totalComisionInput.value = formatearCLP(costo);
        }

        function limpiarFormularioComision() {
            proveedorSelect.value = '';
            transportistaSelect.value = '';
            punto1Input.value = '';
            origenGastoInput.value = 'Suscripciones';
            punto2Input.value = '';
            servicioInput.value = 'Reparto fin de semana';
            costoInput.value = '';
            observacionInput.value = '';

            actualizarTotalComisionActual();
        }

        function agregarHidden(nombre, valor, container = hiddenContainer) {
            const input = document.createElement('input');

            input.type = 'hidden';
            input.name = nombre;
            input.value = valor ?? '';

            container.appendChild(input);
        }

        function renderizarComisiones() {
            hiddenContainer.innerHTML = '';
            resumenBody.innerHTML = '';

            if (comisiones.length === 0) {
                resumenBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-muted text-center">
                            No hay comisiones agregadas para este periodo.
                        </td>
                    </tr>
                `;

                cantidadComisiones.textContent = '0';
                totalComisiones.textContent = formatearCLP(0);

                return;
            }

            let total = 0;

            comisiones.forEach(function (comision, index) {
                total += parseInt(comision.costo || 0, 10);

                agregarHidden(`comisiones[${index}][suscripcion_proveedor_id]`, comision.suscripcion_proveedor_id);
                agregarHidden(`comisiones[${index}][suscripcion_transportista_id]`, comision.suscripcion_transportista_id);
                agregarHidden(`comisiones[${index}][punto_1]`, comision.punto_1);
                agregarHidden(`comisiones[${index}][origen_gasto]`, comision.origen_gasto);
                agregarHidden(`comisiones[${index}][punto_2]`, comision.punto_2);
                agregarHidden(`comisiones[${index}][servicio]`, comision.servicio);
                agregarHidden(`comisiones[${index}][costo]`, comision.costo);
                agregarHidden(`comisiones[${index}][observacion]`, comision.observacion);

                const row = document.createElement('tr');

                row.innerHTML = `
                    <td>${escaparHtml(comision.proveedor_label)}</td>
                    <td>${escaparHtml(comision.transportista_label)}</td>
                    <td>${escaparHtml(comision.punto_1 || '—')}</td>
                    <td>${escaparHtml(comision.servicio || '—')}</td>
                    <td class="text-end">${formatearCLP(comision.costo)}</td>
                    <td>${escaparHtml(comision.observacion || '—')}</td>
                    <td class="text-center">
                        <button
                            type="button"
                            class="btn btn-outline-danger btn-sm"
                            data-index="${index}"
                            data-action="eliminar-comision"
                        >
                            Eliminar
                        </button>
                    </td>
                `;

                resumenBody.appendChild(row);
            });

            cantidadComisiones.textContent = String(comisiones.length);
            totalComisiones.textContent = formatearCLP(total);
        }

        function agregarComisionDesdeFormulario() {
            const proveedorId = proveedorSelect.value;
            const transportistaId = transportistaSelect.value;
            const costo = parseInt(costoInput.value || '', 10);

            if (!proveedorId) {
                alert('Selecciona un proveedor para la comisión.');
                return;
            }

            if (!transportistaId) {
                alert('Selecciona un transportista para la comisión.');
                return;
            }

            if (Number.isNaN(costo) || costo < 0) {
                alert('Ingresa un costo válido para la comisión.');
                return;
            }

            const existeMismaComision = comisiones.some(function (comision) {
                return String(comision.suscripcion_proveedor_id) === String(proveedorId)
                    && String(comision.suscripcion_transportista_id) === String(transportistaId);
            });

            if (existeMismaComision) {
                alert('Ya agregaste una comisión para este proveedor y transportista.');
                return;
            }

            comisiones.push({
                suscripcion_proveedor_id: proveedorId,
                suscripcion_transportista_id: transportistaId,
                proveedor_label: optionLabel(proveedorSelect),
                transportista_label: optionLabel(transportistaSelect),
                punto_1: limpiarTexto(punto1Input.value),
                origen_gasto: limpiarTexto(origenGastoInput.value) || 'Suscripciones',
                punto_2: limpiarTexto(punto2Input.value),
                servicio: limpiarTexto(servicioInput.value) || 'Comisión mensual',
                costo: costo,
                observacion: limpiarTexto(observacionInput.value),
            });

            limpiarFormularioComision();
            renderizarComisiones();
        }

        function actualizarCamposAjustePorTipo() {
            const tipo = normalizarTipo(ajusteTipoSelect.value);

            bloqueAjusteAsignacion.classList.add('d-none');
            bloqueAjusteProveedor.classList.add('d-none');
            bloqueAjusteTransportista.classList.add('d-none');
            bloqueAjusteProveedorFacturacion.classList.add('d-none');
            bloqueAjusteTransportistaOverride.classList.add('d-none');

            ajusteTipoDescripcion.value = 'Selecciona un tipo de novedad para ver los campos necesarios.';

            if (tipo === 'INASISTENCIA') {
                bloqueAjusteAsignacion.classList.remove('d-none');
                ajusteTipoDescripcion.value = 'Actualiza cantidad, inasistencia y total de una asignación existente.';
            }

            if (tipo === 'FIJO_MENSUAL') {
                bloqueAjusteAsignacion.classList.remove('d-none');
                ajusteTipoDescripcion.value = 'Corrige una asignación que debe pagarse una sola vez en el mes, sin multiplicarse por calendario.';

                ajusteQCalendarioInput.value = '1';
                ajusteQInasistenciaInput.value = '0';
                ajusteCantidadInput.value = '1';
                ajusteTotalInput.value = ajusteCostoInput.value || '';
            }

            if (tipo === 'FACTURACION') {
                bloqueAjusteAsignacion.classList.remove('d-none');
                bloqueAjusteProveedorFacturacion.classList.remove('d-none');
                bloqueAjusteTransportistaOverride.classList.remove('d-none');
                ajusteTipoDescripcion.value = 'Cambia proveedor facturador, documentos o transportista efectivo sólo para el periodo.';
            }

            if (esTipoLineaAdicional(tipo)) {
                bloqueAjusteProveedor.classList.remove('d-none');
                bloqueAjusteTransportista.classList.remove('d-none');
                ajusteTipoDescripcion.value = 'Crea una línea mensual adicional mediante una asignación contenedora.';
            }

            actualizarTotalAjusteActual();
        }

        function aplicarDatosAsignacionSeleccionada() {
            const option = selectedOption(ajusteAsignacionSelect);

            if (!option || !option.value) {
                return;
            }

            if (!ajusteCodigoInput.value) {
                ajusteCodigoInput.value = limpiarTexto(option.dataset.codigo || '');
            }

            if (!ajusteCostoInput.value) {
                ajusteCostoInput.value = parseInt(option.dataset.costo || 0, 10) || '';
            }

            if (!ajustePunto1Input.value) {
                ajustePunto1Input.value = limpiarTexto(option.dataset.punto1 || '');
            }

            if (!ajusteOrigenGastoInput.value) {
                ajusteOrigenGastoInput.value = limpiarTexto(option.dataset.origenGasto || 'Suscripciones');
            }

            if (!ajustePunto2Input.value) {
                ajustePunto2Input.value = limpiarTexto(option.dataset.punto2 || '');
            }

            if (!ajusteServicioInput.value) {
                ajusteServicioInput.value = limpiarTexto(option.dataset.servicio || '');
            }

            if (!ajusteGrupoPrefacturaInput.value) {
                ajusteGrupoPrefacturaInput.value = limpiarTexto(option.dataset.grupoPrefactura || '');
            }

            if (normalizarTipo(ajusteTipoSelect.value) === 'FIJO_MENSUAL') {
                ajusteQCalendarioInput.value = '1';
                ajusteQInasistenciaInput.value = '0';
                ajusteCantidadInput.value = '1';

                if (!ajusteTotalInput.value) {
                    ajusteTotalInput.value = ajusteCostoInput.value || '';
                }
            }

            actualizarTotalAjusteActual();
        }

        function aplicarDatosProveedorFacturacion() {
            const option = selectedOption(ajusteProveedorFacturacionSelect);

            if (!option || !option.value) {
                return;
            }

            ajusteTipoDocumentoInput.value = limpiarTexto(option.dataset.tipo || ajusteTipoDocumentoInput.value);
            ajusteDetalleDocumentoInput.value = limpiarTexto(option.dataset.detalleDocumento || ajusteDetalleDocumentoInput.value);
            ajusteDetalleImpuestoInput.value = limpiarTexto(option.dataset.detalleImpuesto || ajusteDetalleImpuestoInput.value);
            ajusteFinalInput.value = limpiarTexto(option.dataset.final || ajusteFinalInput.value);
        }

        function aplicarDatosProveedorLineaAdicional() {
            const option = selectedOption(ajusteProveedorSelect);

            if (!option || !option.value) {
                return;
            }

            if (!ajusteTipoDocumentoInput.value) {
                ajusteTipoDocumentoInput.value = limpiarTexto(option.dataset.tipo || '');
            }

            if (!ajusteDetalleDocumentoInput.value) {
                ajusteDetalleDocumentoInput.value = limpiarTexto(option.dataset.detalleDocumento || '');
            }

            if (!ajusteDetalleImpuestoInput.value) {
                ajusteDetalleImpuestoInput.value = limpiarTexto(option.dataset.detalleImpuesto || '');
            }

            if (!ajusteFinalInput.value) {
                ajusteFinalInput.value = limpiarTexto(option.dataset.final || '');
            }
        }

        function calcularTotalAjusteEstimado() {

            const tipo = normalizarTipo(ajusteTipoSelect.value);
            const costo = parseInt(ajusteCostoInput.value || 0, 10);


            if (tipo === 'FIJO_MENSUAL') {
                return costo;
            }

            const qCalendario = parseInt(ajusteQCalendarioInput.value || '', 10);
            const qInasistencia = parseInt(ajusteQInasistenciaInput.value || 0, 10);
            const cantidadManual = parseInt(ajusteCantidadInput.value || '', 10);
            const totalManual = parseInt(ajusteTotalInput.value || '', 10);

            if (!Number.isNaN(totalManual) && totalManual >= 0) {
                return totalManual;
            }

            let cantidad = 0;

            if (!Number.isNaN(cantidadManual)) {
                cantidad = cantidadManual;
            } else if (!Number.isNaN(qCalendario)) {
                cantidad = Math.max(0, qCalendario - qInasistencia);
            }

            return costo * cantidad;
        }

        function actualizarTotalAjusteActual() {
            ajusteTotalEstimadoInput.value = formatearCLP(calcularTotalAjusteEstimado());
        }

        function limpiarFormularioAjuste() {
            ajusteTipoSelect.value = '';

            ajusteAsignacionSelect.value = '';
            ajusteProveedorSelect.value = '';
            ajusteTransportistaSelect.value = '';
            ajusteProveedorFacturacionSelect.value = '';
            ajusteTransportistaOverrideSelect.value = '';

            ajustePunto1Input.value = '';
            ajusteOrigenGastoInput.value = 'Suscripciones';
            ajustePunto2Input.value = '';
            ajusteCodigoInput.value = '';
            ajusteServicioInput.value = 'Reparto fin de semana';
            ajusteGrupoPrefacturaInput.value = '';

            ajusteCostoInput.value = '';
            ajusteQCalendarioInput.value = '';
            ajusteQInasistenciaInput.value = '';
            ajusteCantidadInput.value = '';
            ajusteTotalInput.value = '';

            ajusteTipoDocumentoInput.value = '';
            ajusteDetalleDocumentoInput.value = '';
            ajusteDetalleImpuestoInput.value = '';
            ajusteFinalInput.value = '';

            ajusteObservacionInput.value = '';

            actualizarCamposAjustePorTipo();
            actualizarTotalAjusteActual();
        }

        function agregarAjusteDesdeFormulario() {
            const tipo = normalizarTipo(ajusteTipoSelect.value);

            if (!tipo) {
                alert('Selecciona el tipo de novedad mensual.');
                return;
            }

            if (esTipoAsignacionExistente(tipo) && !ajusteAsignacionSelect.value) {
                alert('Selecciona una asignación existente para esta novedad.');
                return;
            }



            if (tipo === 'INASISTENCIA' && ajusteQInasistenciaInput.value === '') {
                alert('Ingresa la cantidad de inasistencias.');
                return;
            }


            if (tipo === 'FIJO_MENSUAL') {
                    if (ajusteCostoInput.value === '') {
                        alert('Ingresa el valor mensual fijo.');
                        return;
                    }

                    ajusteQCalendarioInput.value = '1';
                    ajusteQInasistenciaInput.value = '0';
                    ajusteCantidadInput.value = '1';
                    ajusteTotalInput.value = ajusteCostoInput.value;
            }


            if (tipo === 'FACTURACION' && !ajusteProveedorFacturacionSelect.value) {
                alert('Selecciona el proveedor facturador efectivo.');
                return;
            }

            if (esTipoLineaAdicional(tipo)) {
                if (!ajusteProveedorSelect.value) {
                    alert('Selecciona el proveedor de la línea adicional.');
                    return;
                }

                if (!limpiarTexto(ajusteCodigoInput.value)) {
                    alert('Ingresa el código de la línea adicional.');
                    return;
                }

                if (ajusteCostoInput.value === '') {
                    alert('Ingresa el costo de la línea adicional.');
                    return;
                }

                if (ajusteCantidadInput.value === '') {
                    alert('Ingresa la cantidad de la línea adicional.');
                    return;
                }
            }

            const asignacionLabel = ajusteAsignacionSelect.value
                ? optionLabel(ajusteAsignacionSelect)
                : '';

            const proveedorLabel = ajusteProveedorSelect.value
                ? optionLabel(ajusteProveedorSelect)
                : '';

            const proveedorFacturacionLabel = ajusteProveedorFacturacionSelect.value
                ? optionLabel(ajusteProveedorFacturacionSelect)
                : '';

            const transportistaLabel = ajusteTransportistaSelect.value
                ? optionLabel(ajusteTransportistaSelect)
                : '';

            const transportistaOverrideLabel = ajusteTransportistaOverrideSelect.value
                ? optionLabel(ajusteTransportistaOverrideSelect)
                : '';

            ajustesMensuales.push({
                tipo_ajuste: tipo,

                suscripcion_asignacion_id: ajusteAsignacionSelect.value,
                suscripcion_proveedor_id: ajusteProveedorSelect.value,
                suscripcion_transportista_id: ajusteTransportistaSelect.value,

                suscripcion_proveedor_facturacion_id: ajusteProveedorFacturacionSelect.value,
                suscripcion_transportista_override_id: ajusteTransportistaOverrideSelect.value,

                punto_1: limpiarTexto(ajustePunto1Input.value),
                origen_gasto: limpiarTexto(ajusteOrigenGastoInput.value) || 'Suscripciones',
                punto_2: limpiarTexto(ajustePunto2Input.value),
                codigo: limpiarTexto(ajusteCodigoInput.value),
                servicio: limpiarTexto(ajusteServicioInput.value),
                grupo_prefactura: limpiarTexto(ajusteGrupoPrefacturaInput.value),

                costo: ajusteCostoInput.value,
                q_calendario: ajusteQCalendarioInput.value,
                q_inasistencia: ajusteQInasistenciaInput.value,
                cantidad: ajusteCantidadInput.value,
                total: ajusteTotalInput.value,

                tipo_documento: limpiarTexto(ajusteTipoDocumentoInput.value),
                detalle_documento: limpiarTexto(ajusteDetalleDocumentoInput.value),
                detalle_impuesto: limpiarTexto(ajusteDetalleImpuestoInput.value),
                final: limpiarTexto(ajusteFinalInput.value),

                observacion: limpiarTexto(ajusteObservacionInput.value),

                asignacion_label: asignacionLabel,
                proveedor_label: proveedorLabel,
                proveedor_facturacion_label: proveedorFacturacionLabel,
                transportista_label: transportistaLabel,
                transportista_override_label: transportistaOverrideLabel,
                total_estimado: calcularTotalAjusteEstimado(),
            });

            limpiarFormularioAjuste();
            renderizarAjustes();
        }

        function renderizarAjustes() {
            ajustesHiddenContainer.innerHTML = '';
            ajustesResumenBody.innerHTML = '';

            if (ajustesMensuales.length === 0) {
                ajustesResumenBody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-muted text-center">
                            No hay novedades mensuales agregadas para este periodo.
                        </td>
                    </tr>
                `;

                cantidadAjustes.textContent = '0';
                totalAjustes.textContent = formatearCLP(0);

                return;
            }

            let total = 0;

            ajustesMensuales.forEach(function (ajuste, index) {
                total += parseInt(ajuste.total_estimado || 0, 10);

                agregarHidden(`ajustes_mensuales[${index}][tipo_ajuste]`, ajuste.tipo_ajuste, ajustesHiddenContainer);

                agregarHidden(`ajustes_mensuales[${index}][suscripcion_asignacion_id]`, ajuste.suscripcion_asignacion_id, ajustesHiddenContainer);
                agregarHidden(`ajustes_mensuales[${index}][suscripcion_proveedor_id]`, ajuste.suscripcion_proveedor_id, ajustesHiddenContainer);
                agregarHidden(`ajustes_mensuales[${index}][suscripcion_transportista_id]`, ajuste.suscripcion_transportista_id, ajustesHiddenContainer);

                agregarHidden(`ajustes_mensuales[${index}][suscripcion_proveedor_facturacion_id]`, ajuste.suscripcion_proveedor_facturacion_id, ajustesHiddenContainer);
                agregarHidden(`ajustes_mensuales[${index}][suscripcion_transportista_override_id]`, ajuste.suscripcion_transportista_override_id, ajustesHiddenContainer);

                agregarHidden(`ajustes_mensuales[${index}][punto_1]`, ajuste.punto_1, ajustesHiddenContainer);
                agregarHidden(`ajustes_mensuales[${index}][origen_gasto]`, ajuste.origen_gasto, ajustesHiddenContainer);
                agregarHidden(`ajustes_mensuales[${index}][punto_2]`, ajuste.punto_2, ajustesHiddenContainer);
                agregarHidden(`ajustes_mensuales[${index}][codigo]`, ajuste.codigo, ajustesHiddenContainer);
                agregarHidden(`ajustes_mensuales[${index}][servicio]`, ajuste.servicio, ajustesHiddenContainer);
                agregarHidden(`ajustes_mensuales[${index}][grupo_prefactura]`, ajuste.grupo_prefactura, ajustesHiddenContainer);

                agregarHidden(`ajustes_mensuales[${index}][costo]`, ajuste.costo, ajustesHiddenContainer);
                agregarHidden(`ajustes_mensuales[${index}][q_calendario]`, ajuste.q_calendario, ajustesHiddenContainer);
                agregarHidden(`ajustes_mensuales[${index}][q_inasistencia]`, ajuste.q_inasistencia, ajustesHiddenContainer);
                agregarHidden(`ajustes_mensuales[${index}][cantidad]`, ajuste.cantidad, ajustesHiddenContainer);
                agregarHidden(`ajustes_mensuales[${index}][total]`, ajuste.total, ajustesHiddenContainer);

                agregarHidden(`ajustes_mensuales[${index}][tipo_documento]`, ajuste.tipo_documento, ajustesHiddenContainer);
                agregarHidden(`ajustes_mensuales[${index}][detalle_documento]`, ajuste.detalle_documento, ajustesHiddenContainer);
                agregarHidden(`ajustes_mensuales[${index}][detalle_impuesto]`, ajuste.detalle_impuesto, ajustesHiddenContainer);
                agregarHidden(`ajustes_mensuales[${index}][final]`, ajuste.final, ajustesHiddenContainer);

                agregarHidden(`ajustes_mensuales[${index}][observacion]`, ajuste.observacion, ajustesHiddenContainer);

                const baseLabel = ajuste.asignacion_label
                    || ajuste.proveedor_label
                    || ajuste.proveedor_facturacion_label
                    || '—';

                const row = document.createElement('tr');

                row.innerHTML = `
                    <td>${escaparHtml(ajuste.tipo_ajuste)}</td>
                    <td>${escaparHtml(baseLabel)}</td>
                    <td>${escaparHtml(ajuste.codigo || '—')}</td>
                    <td>${escaparHtml(ajuste.servicio || '—')}</td>
                    <td class="text-end">${escaparHtml(ajuste.cantidad || '—')}</td>
                    <td class="text-end">${formatearCLP(ajuste.total_estimado)}</td>
                    <td>${escaparHtml(ajuste.observacion || '—')}</td>
                    <td class="text-center">
                        <button
                            type="button"
                            class="btn btn-outline-danger btn-sm"
                            data-index="${index}"
                            data-action="eliminar-ajuste"
                        >
                            Eliminar
                        </button>
                    </td>
                `;

                ajustesResumenBody.appendChild(row);
            });

            cantidadAjustes.textContent = String(ajustesMensuales.length);
            totalAjustes.textContent = formatearCLP(total);
        }

        if (asignacionCantidadSelect) {
            asignacionCantidadSelect.addEventListener('change', actualizarTotalVariable);
        }

        if (cantidadVariableInput) {
            cantidadVariableInput.addEventListener('input', actualizarTotalVariable);
        }

        if (costoInput) {
            costoInput.addEventListener('input', actualizarTotalComisionActual);
        }

        if (agregarComisionBtn) {
            agregarComisionBtn.addEventListener('click', agregarComisionDesdeFormulario);
        }

        if (resumenBody) {
            resumenBody.addEventListener('click', function (event) {
                const button = event.target.closest('[data-action="eliminar-comision"]');

                if (!button) {
                    return;
                }

                const index = parseInt(button.dataset.index, 10);

                comisiones.splice(index, 1);
                renderizarComisiones();
            });
        }

        if (ajusteTipoSelect) {
            ajusteTipoSelect.addEventListener('change', actualizarCamposAjustePorTipo);
        }

        if (ajusteAsignacionSelect) {
            ajusteAsignacionSelect.addEventListener('change', aplicarDatosAsignacionSeleccionada);
        }

        if (ajusteProveedorFacturacionSelect) {
            ajusteProveedorFacturacionSelect.addEventListener('change', aplicarDatosProveedorFacturacion);
        }

        if (ajusteProveedorSelect) {
            ajusteProveedorSelect.addEventListener('change', aplicarDatosProveedorLineaAdicional);
        }



        [
            ajusteCostoInput,
            ajusteQCalendarioInput,
            ajusteQInasistenciaInput,
            ajusteCantidadInput,
            ajusteTotalInput,
        ].forEach(function (input) {
            if (input) {
                input.addEventListener('input', function () {
                    if (normalizarTipo(ajusteTipoSelect.value) === 'FIJO_MENSUAL') {
                        ajusteQCalendarioInput.value = '1';
                        ajusteQInasistenciaInput.value = '0';
                        ajusteCantidadInput.value = '1';
                        ajusteTotalInput.value = ajusteCostoInput.value || '';
                    }

                    actualizarTotalAjusteActual();
                });
            }
        });

        if (agregarAjusteBtn) {
            agregarAjusteBtn.addEventListener('click', agregarAjusteDesdeFormulario);
        }

        if (ajustesResumenBody) {
            ajustesResumenBody.addEventListener('click', function (event) {
                const button = event.target.closest('[data-action="eliminar-ajuste"]');

                if (!button) {
                    return;
                }

                const index = parseInt(button.dataset.index, 10);

                ajustesMensuales.splice(index, 1);
                renderizarAjustes();
            });
        }

        comisionesIniciales.forEach(function (comision) {
            comisiones.push({
                suscripcion_proveedor_id: comision.suscripcion_proveedor_id,
                suscripcion_transportista_id: comision.suscripcion_transportista_id,
                proveedor_label: labelPorValor(proveedorSelect, comision.suscripcion_proveedor_id),
                transportista_label: labelPorValor(transportistaSelect, comision.suscripcion_transportista_id),
                punto_1: limpiarTexto(comision.punto_1 || ''),
                origen_gasto: limpiarTexto(comision.origen_gasto || 'Suscripciones'),
                punto_2: limpiarTexto(comision.punto_2 || ''),
                servicio: limpiarTexto(comision.servicio || 'Comisión mensual'),
                costo: parseInt(comision.costo || 0, 10),
                observacion: limpiarTexto(comision.observacion || ''),
            });
        });

        ajustesIniciales.forEach(function (ajuste) {
            const totalEstimado = parseInt(ajuste.total || 0, 10)
                || (parseInt(ajuste.costo || 0, 10) * parseInt(ajuste.cantidad || 0, 10));

            ajustesMensuales.push({
                tipo_ajuste: normalizarTipo(ajuste.tipo_ajuste || ''),

                suscripcion_asignacion_id: ajuste.suscripcion_asignacion_id || '',
                suscripcion_proveedor_id: ajuste.suscripcion_proveedor_id || '',
                suscripcion_transportista_id: ajuste.suscripcion_transportista_id || '',

                suscripcion_proveedor_facturacion_id: ajuste.suscripcion_proveedor_facturacion_id || '',
                suscripcion_transportista_override_id: ajuste.suscripcion_transportista_override_id || '',

                punto_1: limpiarTexto(ajuste.punto_1 || ''),
                origen_gasto: limpiarTexto(ajuste.origen_gasto || 'Suscripciones'),
                punto_2: limpiarTexto(ajuste.punto_2 || ''),
                codigo: limpiarTexto(ajuste.codigo || ''),
                servicio: limpiarTexto(ajuste.servicio || ''),
                grupo_prefactura: limpiarTexto(ajuste.grupo_prefactura || ''),

                costo: ajuste.costo || '',
                q_calendario: ajuste.q_calendario || '',
                q_inasistencia: ajuste.q_inasistencia || '',
                cantidad: ajuste.cantidad || '',
                total: ajuste.total || '',

                tipo_documento: limpiarTexto(ajuste.tipo_documento || ''),
                detalle_documento: limpiarTexto(ajuste.detalle_documento || ''),
                detalle_impuesto: limpiarTexto(ajuste.detalle_impuesto || ''),
                final: limpiarTexto(ajuste.final || ''),

                observacion: limpiarTexto(ajuste.observacion || ''),

                asignacion_label: labelPorValor(ajusteAsignacionSelect, ajuste.suscripcion_asignacion_id),
                proveedor_label: labelPorValor(ajusteProveedorSelect, ajuste.suscripcion_proveedor_id),
                proveedor_facturacion_label: labelPorValor(ajusteProveedorFacturacionSelect, ajuste.suscripcion_proveedor_facturacion_id),
                transportista_label: labelPorValor(ajusteTransportistaSelect, ajuste.suscripcion_transportista_id),
                transportista_override_label: labelPorValor(ajusteTransportistaOverrideSelect, ajuste.suscripcion_transportista_override_id),

                total_estimado: totalEstimado,
            });
        });

        actualizarTotalVariable();
        actualizarTotalComisionActual();
        actualizarCamposAjustePorTipo();
        actualizarTotalAjusteActual();

        renderizarComisiones();
        renderizarAjustes();
    });
</script>
@endsection