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
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Preparar generación mensual</h1>
            <div class="small text-muted">
                Define las cantidades variables y las comisiones antes de generar el mes completo.
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
        registrará las comisiones agregadas si existen y luego generará el mes.
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
                <strong>Comisiones del mes</strong>

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

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                    <div class="small text-muted">
                        Comisiones agregadas:
                        <strong id="comisiones-cantidad">0</strong>
                        <span class="mx-1">|</span>
                        Total estimado:
                        <strong id="comisiones-total">$0</strong>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Guardar datos y generar mes completo
                    </button>
                </div>
            </div>
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

        let comisiones = [];
        const comisionesIniciales = @json(collect(old('comisiones', []))->values()->all());

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

        function actualizarTotalVariable() {
            if (!asignacionCantidadSelect || !cantidadVariableInput || !totalVariableInput) {
                return;
            }

            const selectedOption = asignacionCantidadSelect.options[asignacionCantidadSelect.selectedIndex];
            const costo = parseInt(selectedOption?.dataset?.costo || 0, 10);
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

        function agregarHidden(nombre, valor) {
            const input = document.createElement('input');

            input.type = 'hidden';
            input.name = nombre;
            input.value = valor ?? '';

            hiddenContainer.appendChild(input);
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

        actualizarTotalVariable();
        actualizarTotalComisionActual();
        renderizarComisiones();
    });
</script>
@endsection