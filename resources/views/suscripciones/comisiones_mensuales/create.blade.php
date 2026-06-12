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
                Antes de generar el mes, registra una comisión si corresponde.
            </div>
        </div>

        <a href="{{ route('suscripciones.liquidacion-detalles.index', [
            'anio' => $anio,
            'mes' => $mes,
        ]) }}" class="btn btn-secondary">
            Volver
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
        Si este mes tiene comisión, completa el formulario y presiona
        <strong>Guardar comisión y generar mes</strong>.  
        Si no existen comisiones, presiona
        <strong>Generar mes sin comisión</strong>.
    </div>

    <div class="card">
        <div class="card-header">
            <strong>Datos de la comisión</strong>
        </div>

        <div class="card-body">
            <form id="form-comision" method="POST" action="{{ route('suscripciones.comisiones-mensuales.store') }}">
                @csrf

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
                        <label for="costo" class="form-label">Costo</label>
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
                        <label for="cantidad" class="form-label">Cantidad</label>

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
                        <label class="form-label">Total estimado</label>
                        <input
                            type="text"
                            id="total_estimado"
                            class="form-control"
                            value="$0"
                            disabled
                        >
                    </div>

                    <div class="col-md-8">
                        <label for="observacion" class="form-label">Observación</label>
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
            </form>

            <form id="form-sin-comision"
                  method="POST"
                  action="{{ route('suscripciones.liquidacion-detalles.generar-mes') }}">
                @csrf

                <input type="hidden" name="anio_generar" id="anio_generar_sin_comision" value="{{ old('anio', $anio) }}">
                <input type="hidden" name="mes_generar" id="mes_generar_sin_comision" value="{{ old('mes', $mes) }}">
                <input type="hidden" name="proveedor_actual" value="{{ request('proveedor_actual') }}">
            </form>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('suscripciones.liquidacion-detalles.index', [
                    'anio' => $anio,
                    'mes' => $mes,
                ]) }}" class="btn btn-outline-secondary">
                    Cancelar
                </a>

                <button type="submit" form="form-sin-comision" class="btn btn-outline-primary">
                    Generar mes sin comisión
                </button>

                <button type="submit" form="form-comision" class="btn btn-primary">
                    Guardar comisión y generar mes
                </button>
            </div>
        </div>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const anioInput = document.getElementById('anio');
        const mesInput = document.getElementById('mes');
        const anioSinComisionInput = document.getElementById('anio_generar_sin_comision');
        const mesSinComisionInput = document.getElementById('mes_generar_sin_comision');

        const costoInput = document.getElementById('costo');
        const cantidadInput = document.getElementById('cantidad');
        const totalInput = document.getElementById('total_estimado');

        function formatearCLP(valor) {
            return '$' + new Intl.NumberFormat('es-CL').format(valor);
        }

        function actualizarTotal() {
            const costo = parseInt(costoInput.value || 0, 10);
            const cantidad = parseInt(cantidadInput.value || 1, 10);
            const total = costo * cantidad;

            totalInput.value = formatearCLP(total);
        }

        function sincronizarPeriodoSinComision() {
            anioSinComisionInput.value = anioInput.value;
            mesSinComisionInput.value = mesInput.value;
        }

        costoInput.addEventListener('input', actualizarTotal);
        anioInput.addEventListener('input', sincronizarPeriodoSinComision);
        mesInput.addEventListener('change', sincronizarPeriodoSinComision);

        actualizarTotal();
        sincronizarPeriodoSinComision();
    });
</script>
@endsection