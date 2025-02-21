@extends('layouts.app')

@section('content')

<!-- Estilos personalizados para tarjetas -->
<style>
    .card {
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
        transition: transform 0.3s ease;
    }
    .card:hover {
        transform: translateY(-5px);
    }
    .card-title {
        font-size: 1.2rem;
        font-weight: bold;
    }
    .card-body {
        padding: 15px;
    }
    .btn {
        border-radius: 30px;
        padding: 8px 16px;
        font-size: 0.9rem;
        transition: background-color 0.3s ease;
    }
    .btn-success:hover {
        background-color: #28a745;
    }
    .btn-danger:hover {
        background-color: #dc3545;
    }
</style>

<div class="container">
    <h1 class="text-center" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);">Solicitudes de Días</h1>

    <!-- Filtros por estado -->
    <form action="{{ route('solicitudes.vacaciones') }}" method="GET" class="mb-3">
        <div class="input-group">
            <select name="estado" id="estado" class="form-control">
                <option value="">Todos</option>
                <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendientes</option>
                <option value="aprobado" {{ request('estado') == 'aprobado' ? 'selected' : '' }}>Aprobadas</option>
                <option value="rechazado" {{ request('estado') == 'rechazado' ? 'selected' : '' }}>Rechazadas</option>
            </select>
            <div class="input-group-append">
                <button type="submit" class="btn btn-primary">Aplicar Filtro</button>
            </div>
        </div>
    </form>

    <!-- Mostrar mensajes de éxito y advertencia -->
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning">
            {{ session('warning') }}
        </div>
    @endif

    <!-- Tarjetas de solicitudes -->
    <div class="row">
        @foreach($solicitudes as $solicitud)
            @if($solicitud->campo == 'Vacaciones') <!-- Mostrar solo solicitudes de vacaciones -->
            <div class="col-md-4">
                <div class="card @if($solicitud->estado === 'aprobado') border-success 
                                @elseif($solicitud->estado === 'rechazado') border-danger 
                                @else border-warning @endif">
                    <div class="card-body">
                        <h5 class="card-title">{{ $solicitud->trabajador->Nombre }} {{ $solicitud->trabajador->ApellidoPaterno }}</h5>
                        <p><strong>Tipo de Día:</strong> {{ ucfirst($solicitud->tipo_dia) }}</p>

                        <p><strong>Descripción:</strong> {{ Str::limit($solicitud->descripcion, 60) }}</p>
                        
                        <!-- Mostrar fechas relevantes -->
                        <p><strong>Fecha Inicio:</strong> {{ $solicitud->vacacion->fecha_inicio->format('Y-m-d') }}</p>
                        <p><strong>Fecha Fin:</strong> {{ $solicitud->vacacion->fecha_fin->format('Y-m-d') }}</p>
                        
                        <!-- Días Solicitados y Días Descontados -->
                        <p><strong>Días Solicitados:</strong> {{ $solicitud->vacacion->dias }}</p>

                        @if($solicitud->tipo_dia === 'vacaciones')
                            <p><strong>Días Descontados:</strong> {{ $solicitud->vacacion->dias }}</p>
                        @else
                            <p><strong>Días Descontados:</strong> 0</p>
                        @endif

                        <!-- Mostrar archivo adjunto si existe -->
                        @if($solicitud->vacacion->archivo)
                            <p><strong>Archivo Adjunto:</strong> 
                                <a href="{{ route('vacaciones.descargar', $solicitud->vacacion->id) }}" target="_blank">Descargar Archivo</a>
                            </p>
                        @endif




                        <p><strong>Estado:</strong> {{ ucfirst($solicitud->estado) }}</p>

                        <!-- Formularios de aprobación y rechazo con comentario -->
                        <div class="mt-3">
                            <!-- Formulario de aprobación, visible solo si el estado es 'pendiente' -->
                            @if($solicitud->estado === 'pendiente')
                                <form action="{{ route('solicitudes.vacaciones.approve', $solicitud->id) }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="form-group">
                                        <label for="comentario_admin">Comentario del Administrador</label>
                                        <textarea name="comentario_admin" id="comentario_admin" class="form-control" rows="2" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-outline-primary mt-2">Aprobar</button>
                                </form>

                                <form action="{{ route('solicitudes.vacaciones.reject', $solicitud->id) }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="form-group">
                                        <label for="comentario_admin">Comentario del Administrador</label>
                                        <textarea name="comentario_admin" id="comentario_admin" class="form-control" rows="2" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-outline-danger mt-2">Rechazar</button>
                                </form>
                            @endif

                            <!-- Campo para subir archivo de respaldo, solo visible cuando el estado es 'aprobado' o 'rechazado' y no existe ya un archivo de respaldo -->
                            @if(($solicitud->estado === 'aprobado' || $solicitud->estado === 'rechazado') && is_null($solicitud->vacacion->archivo_respuesta_admin))
                                <form action="{{ route('solicitudes.vacaciones.approve', $solicitud->id) }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="form-group">

                                        <label for="archivo_respuesta_admin">Subir archivo escaneado y firmado</label>
                                        <input type="file" name="archivo_respuesta_admin" class="form-control" id="archivo_respuesta_admin" required>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-outline-primary mt-2">Subir Respaldo</button>

                                    @if($solicitud->estado === 'aprobado' && $solicitud->vacacion && $solicitud->vacacion->archivo_admin)
                                        <a href="{{ route('vacaciones.descargarArchivoAdmin', $solicitud->vacacion->id) }}" class="btn btn-sm btn-outline-danger mt-2">
                                            <i class="fa-solid fa-file-pdf"></i> Descargar PDF de solicitud de días
                                        </a>
                                    @endif
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif
        @endforeach
    </div>
    
    <!-- Botón de regreso -->
    <a href="{{ url('/empleados') }}" class="btn btn-primary mt-3">
        <i class="fas fa-arrow-left"></i> Regresar al listado de empleados
    </a>
</div>

@endsection
