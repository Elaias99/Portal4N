@extends('layouts.app')

@section('content')
<div class="container mt-5">

    <h1 class="mb-4 text-center">Mis Días Solicitados</h1>

    <!-- Mensajes -->
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-header">
            <h4>Vacaciones Históricas</h4>
        </div>
        <div class="card-body">
            @if ($historialVacaciones->isEmpty())
                <p>No tienes registros históricos.</p>
            @else
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Días Tomados</th>
                            <th>Días Descontados</th>
                            <th>Tipo de Día</th>
                            <th>Archivo Respaldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($historialVacaciones as $item)
                        <tr>
                            <td>{{ $item->id }}</td>
                            <td>{{ $item->fecha_inicio->format('Y-m-d') }}</td>
                            <td>{{ $item->fecha_fin->format('Y-m-d') }}</td>
                            <td>{{ $item->dias_laborales }}</td>
                            <td>{{ $item->dias_descontados }}</td>
                            <td>{{ ucfirst($item->tipo_dia) }}</td>
                            <td>
                                @if ($item->archivo_respaldo)
                                    <a href="{{ route('historial-vacacion.descargar', $item->id) }}" class="btn btn-sm btn-outline-primary">Descargar PDF</a>
                                @else
                                    <span class="text-muted">Sin archivo</span>
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

    <div class="card">
        <div class="card-header">
            <h4>Solicitudes Aprobadas</h4>
        </div>
        <div class="card-body">
            @if ($solicitudesAprobadas->isEmpty())
                <p>No tienes solicitudes aprobadas.</p>
            @else
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Días Tomados</th>
                            <th>Días Descontados</th>
                            <th>Tipo de Día</th>
                            <th>Comentario Admin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($solicitudesAprobadas as $solicitud)
                        <tr>
                            <td>{{ $solicitud->id }}</td>
                            <td>{{ $solicitud->vacacion->fecha_inicio->format('Y-m-d') }}</td>
                            <td>{{ $solicitud->vacacion->fecha_fin->format('Y-m-d') }}</td>
                            <td>{{ $solicitud->dias_tomados }}</td>
                            <td>{{ $solicitud->dias_descontados }}</td>
                            <td>{{ ucfirst($solicitud->tipo_dia) }}</td>
                            <td>{{ $solicitud->comentario_admin ?? 'N/A' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    <a href="{{ route('vacaciones.create') }}" class="btn btn-outline-primary mt-4">
        <i class="fas fa-arrow-left"></i> Volver a crear solicitud
    </a>
</div>
@endsection
