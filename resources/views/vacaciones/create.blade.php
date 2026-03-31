@extends('layouts.app')

@section('content')
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
                        <option value="vacaciones">Días de Vacaciones</option>
                        <option value="administrativo">Días Administrativos</option>
                        <option value="sin_goce_de_sueldo">Permiso sin goce de sueldo</option>
                        <option value="Permiso_fuerza_mayor">Permiso fuerza mayor</option>
                        <option value="licencia_medica">Licencia Médica</option>
                    </select>
                    <small class="form-text text-muted">
                        * Nota: Solo los <strong>Días de Vacaciones</strong> afectan el saldo de días proporcionales acumulados.
                        Los demás tipos de permisos no afectan tu saldo.
                    </small>
                </div>

                <div class="form-group mb-3">
                    <label for="fecha_inicio" class="form-label">Fecha de Inicio</label>
                    <input type="date" class="form-control" name="fecha_inicio" id="fecha_inicio" required>
                </div>

                <div class="form-group mb-3">
                    <label for="fecha_fin" class="form-label">Fecha de Fin</label>
                    <input type="date" class="form-control" name="fecha_fin" id="fecha_fin" required>
                </div>

                <div class="form-group">
                    <label for="archivo">Adjuntar archivo (opcional):</label>
                    <input type="file" name="archivo" class="form-control" id="archivo">
                </div>

                <div class="form-group mb-3">
                    <label for="dias" class="form-label">Número de Días</label>
                    <input type="number" class="form-control" name="dias" id="dias" min="1" required>
                    <small class="form-text text-muted">
                        Ingresa manualmente el número de días de la solicitud. Verifica que no incluyas días feriados o fines de semana si aplican.
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
        const form = document.getElementById('formSolicitudVacaciones');
        const btn = document.getElementById('btnEnviarSolicitud');

        if (!form || !btn) return;

        form.addEventListener('submit', function () {
            btn.disabled = true;
            btn.innerText = 'Enviando solicitud...';
            btn.classList.add('disabled');
        });
    });
</script>
@endsection