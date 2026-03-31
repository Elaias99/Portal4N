@extends('layouts.app')

@section('content')
@php
    $feriadosChile = [];

    foreach (glob(app_path('Data/Calendars/chile_*.php')) as $archivoCalendario) {
        $data = require $archivoCalendario;

        if (is_array($data)) {
            $feriadosChile = array_merge($feriadosChile, $data);
        }
    }
@endphp

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Crear Peticiones de Días</h1>
        <a href="{{ route('vacaciones.mis') }}" class="btn btn-outline-dark">
            Historial de días Tomados
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="alert alert-info d-flex align-items-center justify-content-between">
        <div>
            <strong>Días Proporcionales Acumulados:</strong> {{ $diasProporcionales }}
        </div>
        <button type="button" class="btn btn-link p-0 ml-2" data-toggle="modal" data-target="#infoDiasModal">
            <i class="fa fa-info-circle fa-lg text-dark"></i>
        </button>
    </div>

    @if ($solicitudPendiente)
        <div class="alert alert-warning">
            Ya tienes una solicitud de vacaciones pendiente. Debes esperar la respuesta antes de hacer otra.
        </div>
    @else
        <div class="card shadow-sm p-4">
            <form id="formSolicitudVacaciones" action="{{ route('vacaciones.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="form-group mb-3">
                    <label for="tipo_dia" class="form-label">Tipo de Solicitud de Día</label>
                    <select name="tipo_dia" id="tipo_dia" class="form-control" required>
                        <option value="vacaciones" {{ old('tipo_dia') == 'vacaciones' ? 'selected' : '' }}>Días de Vacaciones</option>
                        <option value="administrativo" {{ old('tipo_dia') == 'administrativo' ? 'selected' : '' }}>Días Administrativos</option>
                        <option value="sin_goce_de_sueldo" {{ old('tipo_dia') == 'sin_goce_de_sueldo' ? 'selected' : '' }}>Permiso sin goce de sueldo</option>
                        <option value="Permiso_fuerza_mayor" {{ old('tipo_dia') == 'Permiso_fuerza_mayor' ? 'selected' : '' }}>Permiso fuerza mayor</option>
                        <option value="licencia_medica" {{ old('tipo_dia') == 'licencia_medica' ? 'selected' : '' }}>Licencia Médica</option>
                    </select>
                    <small class="form-text text-muted">
                        * Nota: Solo los <strong>Días de Vacaciones</strong> afectan el saldo de días proporcionales acumulados.
                        Los demás tipos de permisos no afectan tu saldo.
                    </small>
                </div>

                <div class="form-group mb-3">
                    <label for="fecha_inicio" class="form-label">Fecha de Inicio</label>
                    <input type="date" class="form-control" name="fecha_inicio" id="fecha_inicio" value="{{ old('fecha_inicio') }}" required>
                </div>

                <div class="form-group mb-3">
                    <label for="fecha_fin" class="form-label">Fecha de Fin</label>
                    <input type="date" class="form-control" name="fecha_fin" id="fecha_fin" value="{{ old('fecha_fin') }}" required>
                </div>

                <div class="form-group">
                    <label for="archivo">Adjuntar archivo (opcional):</label>
                    <input type="file" name="archivo" class="form-control" id="archivo">
                </div>

                <div class="form-group mb-3">
                    <label for="dias_calculados" class="form-label">Número de Días</label>
                    <input type="text" class="form-control" id="dias_calculados" readonly placeholder="Se calculará automáticamente">
                    <small class="form-text text-muted">
                        El sistema calcula automáticamente los días hábiles según la fecha de inicio y fecha de fin, excluyendo fines de semana y feriados.
                    </small>
                </div>



                <button type="submit" id="btnEnviarSolicitud" class="btn btn-primary btn-block">
                    Enviar Solicitud
                </button>
            </form>
        </div>
    @endif

    <a href="{{ route('empleados.perfil') }}" class="btn btn-outline-secondary btn-block mt-4">Atrás</a>
</div>

@include('vacaciones.modal_info_dias')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const feriadosChile = @json($feriadosChile);
        const form = document.getElementById('formSolicitudVacaciones');
        const btn = document.getElementById('btnEnviarSolicitud');
        const fechaInicioInput = document.getElementById('fecha_inicio');
        const fechaFinInput = document.getElementById('fecha_fin');
        const diasCalculadosInput = document.getElementById('dias_calculados');

        if (!form || !btn || !fechaInicioInput || !fechaFinInput || !diasCalculadosInput) return;

        const feriadosMap = {};
        feriadosChile.forEach(function (feriado) {
            feriadosMap[feriado.date] = feriado;
        });

        function formatearFechaLocal(fecha) {
            const anio = fecha.getFullYear();
            const mes = String(fecha.getMonth() + 1).padStart(2, '0');
            const dia = String(fecha.getDate()).padStart(2, '0');
            return `${anio}-${mes}-${dia}`;
        }

        function calcularDias() {
            const fechaInicio = fechaInicioInput.value;
            const fechaFin = fechaFinInput.value;

            diasCalculadosInput.value = '';
            btn.disabled = false;

            if (!fechaInicio || !fechaFin) {
                return;
            }

            if (fechaFin < fechaInicio) {
                diasCalculadosInput.value = '0';
                btn.disabled = true;
                return;
            }

            let actual = new Date(fechaInicio + 'T00:00:00');
            const fin = new Date(fechaFin + 'T00:00:00');

            let diasHabiles = 0;

            while (actual <= fin) {
                const fechaTexto = formatearFechaLocal(actual);
                const diaSemana = actual.getDay();

                if (diaSemana !== 0 && diaSemana !== 6 && !feriadosMap[fechaTexto]) {
                    diasHabiles++;
                }

                actual.setDate(actual.getDate() + 1);
            }

            diasCalculadosInput.value = diasHabiles;

            if (diasHabiles <= 0) {
                btn.disabled = true;
            }
        }

        fechaInicioInput.addEventListener('change', calcularDias);
        fechaFinInput.addEventListener('change', calcularDias);

        form.addEventListener('submit', function () {
            btn.disabled = true;
            btn.innerText = 'Enviando solicitud...';
            btn.classList.add('disabled');
        });

        calcularDias();
    });
</script>
@endsection