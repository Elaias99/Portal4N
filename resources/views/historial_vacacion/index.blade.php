@extends('layouts.app')

@section('content')
<div class="container mt-5">

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">
                Historial de Días de {{ $trabajador->Nombre }} {{ $trabajador->ApellidoPaterno }}
            </h1>
            <p class="text-muted mb-0">
                Vista de detalle para revisar vacaciones generadas, días usados y saldo actual del trabajador.
            </p>
        </div>

        <a href="{{ route('historial-vacacion.create') }}" class="btn btn-primary">
            Registrar Días Históricos
        </a>
    </div>

    {{-- Resumen del trabajador --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h3 class="mb-0">Resumen del Trabajador</h3>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6 col-lg-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Nombre</div>
                        <div class="fw-semibold">
                            {{ $trabajador->Nombre }} {{ $trabajador->ApellidoPaterno }} {{ $trabajador->ApellidoMaterno }}
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Fecha de contratación</div>
                        <div class="fw-semibold">
                            {{ $trabajador->fecha_inicio_trabajo ? $trabajador->fecha_inicio_trabajo->format('d-m-Y') : 'No disponible' }}
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Empresa</div>
                        <div class="fw-semibold">
                            {{ $trabajador->empresa->Nombre ?? 'No disponible' }}
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Jefe directo</div>
                        <div class="fw-semibold">
                            {{ $trabajador->jefe?->nombre ?? 'Sin jefe asignado' }}
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Estado actual</div>
                        <div class="fw-semibold">
                            {{ $trabajador->situacion->Nombre ?? 'No disponible' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Resumen de vacaciones --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h3 class="mb-0">Resumen de Vacaciones</h3>
        </div>
        <div class="card-body">

            @if (($resumen['saldo_real'] ?? 0) < 0)
                <div class="alert alert-warning mb-4">
                    Este trabajador presenta un déficit de
                    <strong>{{ number_format(abs($resumen['saldo_real']), 2, ',', '.') }}</strong>
                    días.
                </div>
            @endif

            <div class="row g-3">
                <div class="col-md-6 col-lg-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Días generados desde contratación</div>
                        <div class="h4 mb-1">
                            {{ number_format($resumen['dias_acumulados_teoricos'] ?? 0, 2, ',', '.') }}
                        </div>
                        <div class="text-muted small">
                            Total teórico acumulado desde la fecha de ingreso del trabajador.
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Total días usados</div>
                        <div class="h4 mb-1">
                            {{ number_format($resumen['dias_total_descontados'] ?? 0, 2, ',', '.') }}
                        </div>
                        <div class="text-muted small">
                            Suma de días históricos/manuales y solicitudes aprobadas que descuentan saldo.
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Saldo actual disponible</div>
                        <div class="h4 mb-1">
                            {{ number_format($resumen['saldo_mostrado'] ?? 0, 2, ',', '.') }}
                        </div>
                        <div class="text-muted small">
                            Días disponibles que hoy muestra el sistema.
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Usados por históricos / cargas manuales</div>
                        <div class="h5 mb-1">
                            {{ number_format($resumen['dias_historicos_descontados'] ?? 0, 2, ',', '.') }}
                        </div>
                        <div class="text-muted small">
                            Registros ingresados manualmente en el historial del trabajador.
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Usados por solicitudes aprobadas</div>
                        <div class="h5 mb-1">
                            {{ number_format($resumen['dias_solicitudes_descontados'] ?? 0, 2, ',', '.') }}
                        </div>
                        <div class="text-muted small">
                            Solicitudes aprobadas dentro del flujo normal del sistema.
                        </div>
                    </div>
                </div>
            </div>

            @if (($resumen['saldo_real'] ?? 0) < 0)
                <div class="mt-4">
                    <div class="border rounded p-3 bg-light">
                        <div class="fw-semibold mb-1">Observación</div>
                        <div class="text-muted">
                            El trabajador ha usado más días de los que lleva generados teóricamente.
                            El saldo real calculado es
                            <strong>{{ number_format($resumen['saldo_real'], 2, ',', '.') }}</strong>.
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Historial manual --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h3 class="mb-0">Vacaciones Históricas / Cargas Manuales</h3>
        </div>
        <div class="card-body">
            <p class="text-muted">
                Aquí se muestran los registros cargados manualmente para este trabajador.
            </p>

            @if ($historialVacaciones->isEmpty())
                <p class="text-center mb-0">No hay registros históricos de vacaciones.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th>Días Tomados</th>
                                <th>Días que descuentan saldo</th>
                                <th>Tipo de Día</th>
                                <th>Archivo Respaldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($historialVacaciones as $vacacion)
                                <tr>
                                    <td>{{ $vacacion->id }}</td>
                                    <td>{{ $vacacion->fecha_inicio->format('d-m-Y') }}</td>
                                    <td>{{ $vacacion->fecha_fin->format('d-m-Y') }}</td>
                                    <td>{{ $vacacion->dias_laborales }}</td>
                                    <td>{{ $vacacion->dias_descontados }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $vacacion->tipo_dia)) }}</td>
                                    <td>
                                        @if ($vacacion->archivo_respaldo)
                                            <a href="{{ route('historial-vacacion.descargar', $vacacion->id) }}"
                                               class="btn btn-sm btn-outline-primary">
                                                Descargar PDF
                                            </a>
                                        @else
                                            <form action="{{ route('historial-vacacion.subir', $vacacion->id) }}"
                                                  method="POST"
                                                  enctype="multipart/form-data">
                                                @csrf
                                                <input type="file"
                                                       name="archivo_respaldo"
                                                       class="form-control mb-2"
                                                       required>
                                                <button type="submit" class="btn btn-sm btn-outline-success">
                                                    Subir Archivo
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Solicitudes aprobadas --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h3 class="mb-0">Solicitudes Aprobadas</h3>
        </div>
        <div class="card-body">
            <p class="text-muted">
                Aquí se muestran las solicitudes aprobadas dentro del flujo regular del sistema.
            </p>

            @if ($solicitudesAprobadas->isEmpty())
                <p class="text-center mb-0">No hay solicitudes de vacaciones aprobadas.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th>Días Tomados</th>
                                <th>Días que descuentan saldo</th>
                                <th>Tipo de Día</th>
                                <th>Comentario del Administrador</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($solicitudesAprobadas as $solicitud)
                                <tr>
                                    <td>{{ optional($solicitud->vacacion)->id ?? $solicitud->id }}</td>
                                    <td>{{ optional(optional($solicitud->vacacion)->fecha_inicio)->format('d-m-Y') ?? 'No disponible' }}</td>
                                    <td>{{ optional(optional($solicitud->vacacion)->fecha_fin)->format('d-m-Y') ?? 'No disponible' }}</td>
                                    <td>{{ $solicitud->dias_tomados ?? 0 }}</td>
                                    <td>{{ $solicitud->dias_descontados ?? 0 }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $solicitud->tipo_dia)) }}</td>
                                    <td>{{ $solicitud->comentario_admin ?? 'Sin comentario' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <a href="{{ url('/empleados') }}" class="btn btn-primary">
        Regresar al listado de empleados
    </a>
</div>
@endsection