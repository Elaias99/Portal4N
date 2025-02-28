@extends('layouts.app')

@section('content')
<div class="container mt-5">

    <!-- Mostrar mensaje de éxito o error si existe -->
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

    @if (!isset($trabajador))
        <!-- Formulario para seleccionar un trabajador -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h3>Seleccionar Trabajador</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('historial-vacacion.index') }}" method="GET">
                    <div class="form-group">
                        <label for="trabajador_id">Trabajador</label>
                        <select name="trabajador_id" class="form-control" required>
                            <option value="" disabled selected>Seleccione un trabajador</option>
                            @foreach ($trabajadores as $trabajador)
                                <option value="{{ $trabajador->id }}">{{ $trabajador->Nombre }} {{ $trabajador->ApellidoPaterno }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">Ver Historial</button>
                </form>
            </div>
        </div>
    @else
        <!-- Historial del trabajador seleccionado -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-center">Historial de Solicitudes de Días de {{ $trabajador->Nombre }} {{ $trabajador->ApellidoPaterno }}</h1>
            <a href="{{ route('historial-vacacion.create') }}" class="btn btn-primary mt-3">Registrar Días Históricos</a>
        </div>

        <!-- Tabla de Historial de Vacaciones -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h3>Vacaciones Históricas</h3>
            </div>
            <div class="card-body">
                @if ($historialVacaciones->isEmpty())
                    <p class="text-center">No hay registros históricos de vacaciones.</p>
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
                                <th>Archivo Respaldo</th> <!-- Nueva columna -->
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($historialVacaciones as $vacacion)
                                <tr>
                                    <td>{{ $vacacion->id }}</td>
                                    <td>{{ $vacacion->fecha_inicio->format('Y-m-d') }}</td>
                                    <td>{{ $vacacion->fecha_fin->format('Y-m-d') }}</td>
                                    <td>{{ $vacacion->dias_laborales }}</td>
                                    <td>{{ $vacacion->dias_descontados }}</td>
                                    <td>{{ ucfirst($vacacion->tipo_dia) }}</td>

                                    <td>
                                        @if ($vacacion->archivo_respaldo)
                                            <a href="{{ route('historial-vacacion.descargar', $vacacion->id) }}" class="btn btn-sm btn-outline-primary">
                                                Descargar PDF
                                            </a>
                                        @else
                                            <!-- Formulario para subir archivo -->
                                            <form action="{{ route('historial-vacacion.subir', $vacacion->id) }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <input type="file" name="archivo_respaldo" class="form-control mb-2" required>
                                                <button type="submit" class="btn btn-sm btn-outline-success">Subir Archivo</button>
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

        <!-- Tabla de Solicitudes Aprobadas Recientes -->
        <!-- Tabla de Solicitudes Aprobadas Recientes -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h3>Solicitudes Aprobadas Recientes</h3>
            </div>
            <div class="card-body">
                @if ($solicitudesAprobadas->isEmpty())
                    <p class="text-center">No hay solicitudes de vacaciones aprobadas.</p>
                @else
                <div class="table-responsive">

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th>Días Tomados</th> <!-- Nueva columna para los días tomados -->
                                <th>Días Descontados</th> <!-- Nueva columna para los días descontados -->
                                <th>Tipo de Día</th>
                                <th>Comentario del Administrador</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($solicitudesAprobadas as $solicitud)
                                <tr>
                                    <td>{{ $solicitud->vacacion->fecha_inicio->format('Y-m-d') }}</td>
                                    <td>{{ $solicitud->vacacion->fecha_fin->format('Y-m-d') }}</td>
                                    <td>{{ $solicitud->dias_tomados }}</td> <!-- Mostrar los días tomados -->
                                    <td>{{ $solicitud->dias_descontados }}</td> <!-- Mostrar los días descontados -->
                                    <td>{{ ucfirst($solicitud->tipo_dia) }}</td> <!-- Mostrar el tipo de día -->
                                    <td>{{ $solicitud->comentario_admin ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>






                </div>

                @endif
            </div>
        </div>

        <a href="{{ url('/historial-vacacion') }}" class="btn btn-primary mt-3">
            <i class="fas fa-arrow-left"></i> Regresar a la selección
        </a>


        
    @endif
    <a href="{{ url('/empleados') }}" class="btn btn-primary mt-3">
        <i class="fas fa-arrow-left"></i> Regresar al listado de empleados
    </a>
</div>
@endsection
