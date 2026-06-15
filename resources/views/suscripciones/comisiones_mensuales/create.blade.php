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
                Define las cantidades variables y la comisión antes de generar el mes completo.
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
        <strong>Guardar datos y generar mes completo</strong>, el sistema registrará la cantidad variable,
        registrará la comisión y luego generará el mes.
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
                            <label for="cantidad_mensual_observacion" class="form-label">Observación cantidad variable</label>
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

        <div class="card">
            <div class="card-header">
                <strong>Datos de la comisión</strong>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-6">
                        <label for="suscripcion_proveedor_id" class="form-label">
                            Proveedor a integrar comisión
                        </label>

                        <select
                            name="suscripcion_proveedor_id"
                            id="suscripcion_proveedor_id"
                            class="form-select"
                            required
                        >
                            <option value="">Seleccionar proveedor...</option>

                            @foreach($proveedores as $proveedor)
                                @php
                                    $cobranza = $proveedor->cobranzaCompra;
                                @endphp

                                <option
                                    value="{{ $proveedor->id }}"
                                    @selected((int) old('suscripcion_proveedor_id') === (int) $proveedor->id)
                                >
                                    {{ $cobranza?->razon_social ?? 'Sin razón social' }}
                                    |
                                    {{ $cobranza?->rut_cliente ?? 'Sin RUT' }}
                                    |
                                    {{ $proveedor->tipo ?? 'Sin tipo' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="suscripcion_transportista_id" class="form-label">
                            Transportista
                        </label>

                        <select
                            name="suscripcion_transportista_id"
                            id="suscripcion_transportista_id"
                            class="form-select"
                            required
                        >
                            <option value="">Seleccionar transportista...</option>

                            @foreach($transportistas as $transportista)
                                <option
                                    value="{{ $transportista->id }}"
                                    @selected((int) old('suscripcion_transportista_id') === (int) $transportista->id)
                                >
                                    {{ $transportista->nombre_transportista }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="punto_1" class="form-label">Punto</label>
                        <input
                            type="text"
                            name="punto_1"
                            id="punto_1"
                            class="form-control"
                            value="{{ old('punto_1') }}"
                            placeholder="Ej: LA DEHESA"
                        >
                    </div>

                    <div class="col-md-3">
                        <label for="origen_gasto" class="form-label">Origen gasto</label>
                        <input
                            type="text"
                            name="origen_gasto"
                            id="origen_gasto"
                            class="form-control"
                            value="{{ old('origen_gasto', 'Suscripciones') }}"
                        >
                    </div>

                    <div class="col-md-3">
                        <label for="punto_2" class="form-label">Punto 2</label>
                        <input
                            type="text"
                            name="punto_2"
                            id="punto_2"
                            class="form-control"
                            value="{{ old('punto_2') }}"
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
                        <label for="servicio" class="form-label">Servicio</label>
                        <input
                            type="text"
                            name="servicio"
                            id="servicio"
                            class="form-control"
                            value="{{ old('servicio', 'Reparto fin de semana') }}"
                        >
                    </div>

                    <div class="col-md-4">
                        <label for="costo" class="form-label">Costo comisión</label>
                        <input
                            type="number"
                            name="costo"
                            id="costo"
                            class="form-control"
                            value="{{ old('costo') }}"
                            min="0"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label for="cantidad" class="form-label">Cantidad comisión</label>

                        <input
                            type="number"
                            id="cantidad"
                            class="form-control"
                            value="1"
                            disabled
                        >

                        <input
                            type="hidden"
                            name="cantidad"
                            value="1"
                        >

                        <div class="form-text">
                            Las comisiones siempre se registran con cantidad 1.
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Total estimado comisión</label>
                        <input
                            type="text"
                            id="total_estimado"
                            class="form-control"
                            value="$0"
                            disabled
                        >
                    </div>

                    <div class="col-md-8">
                        <label for="observacion" class="form-label">Observación comisión</label>
                        <input
                            type="text"
                            name="observacion"
                            id="observacion"
                            class="form-control"
                            value="{{ old('observacion') }}"
                            placeholder="Ej: comisión informada para este mes"
                        >
                    </div>

                </div>

                <div class="d-flex justify-content-end mt-4">
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
        const costoInput = document.getElementById('costo');
        const cantidadInput = document.getElementById('cantidad');
        const totalInput = document.getElementById('total_estimado');

        const asignacionCantidadSelect = document.getElementById('cantidad_mensual_asignacion_id');
        const cantidadVariableInput = document.getElementById('cantidad_mensual_cantidad');
        const totalVariableInput = document.getElementById('total_variable_estimado');

        function formatearCLP(valor) {
            return '$' + new Intl.NumberFormat('es-CL').format(valor);
        }

        function actualizarTotalComision() {
            const costo = parseInt(costoInput.value || 0, 10);
            const cantidad = parseInt(cantidadInput.value || 1, 10);
            const total = costo * cantidad;

            totalInput.value = formatearCLP(total);
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

        costoInput.addEventListener('input', actualizarTotalComision);

        if (asignacionCantidadSelect) {
            asignacionCantidadSelect.addEventListener('change', actualizarTotalVariable);
        }

        if (cantidadVariableInput) {
            cantidadVariableInput.addEventListener('input', actualizarTotalVariable);
        }

        actualizarTotalComision();
        actualizarTotalVariable();
    });
</script>
@endsection