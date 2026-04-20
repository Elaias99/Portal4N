@extends('layouts.app')

@section('content')

<style>
    .card {
        position: relative;
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

    .id-container {
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(0, 0, 0, 0.1);
        border-radius: 12px;
        padding: 5px 10px;
    }

    .id-label {
        font-size: 14px;
        font-weight: bold;
        color: #333;
    }
</style>

<div class="container">
    <h1 class="text-center" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);">
        Solicitudes de Días
    </h1>

    <br>

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

    <div class="row">

        {{-- Columna izquierda --}}
        <div class="col-lg-2">
            @component('layouts.columna_izquierda', [
                'tituloTarjeta' => 'Acciones de Vacaciones',
                'tituloFiltros' => 'Filtrar Solicitudes',
                'action' => route('solicitudes.vacaciones')
            ])
                @slot('acciones')
                    <div class="d-grid gap-2">
                        <a href="{{ route('rrhh.formulario') }}"
                           class="btn btn-outline-danger text-start"
                           data-bs-toggle="tooltip"
                           title="PDF Manual">
                            <i class="fas fa-file-pdf me-2"></i> PDF Manual
                        </a>

                        <a href="{{ route('vacaciones.exportarDisponibles') }}"
                           class="btn btn-outline-success text-start"
                           data-bs-toggle="tooltip"
                           title="Exportar Excel">
                            <i class="fas fa-file-excel me-2"></i> Exportar Excel
                        </a>
                    </div>
                @endslot

                @slot('filtros')
                    <div class="mb-3">
                        <label for="buscar" class="form-label">Buscar trabajador</label>
                        <input
                            type="text"
                            name="buscar"
                            id="buscar"
                            class="form-control"
                            placeholder="Nombre, apellido o rut"
                            value="{{ request('buscar') }}"
                        >
                    </div>

                    <div class="mb-3">
                        <label for="estado" class="form-label">Estado</label>
                        <select name="estado" id="estado" class="form-select">
                            <option value="">Todos</option>
                            <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>
                                Pendientes
                            </option>
                            <option value="aprobado" {{ request('estado') == 'aprobado' ? 'selected' : '' }}>
                                Aprobadas
                            </option>
                            <option value="rechazado" {{ request('estado') == 'rechazado' ? 'selected' : '' }}>
                                Rechazadas
                            </option>
                        </select>
                    </div>
                @endslot
            @endcomponent
        </div>

        {{-- Columna derecha --}}
        <div class="col-lg-10">

            @php
                $totalSolicitudes = $solicitudes instanceof \Illuminate\Contracts\Pagination\Paginator
                    ? $solicitudes->total()
                    : $solicitudes->count();
            @endphp

            {{-- <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0">Resultados</h5>
                    <small class="text-muted">
                        {{ $totalSolicitudes }} solicitud(es) encontrada(s)
                    </small>
                </div>
            </div> --}}

            @if($solicitudes->count() > 0)
                <div class="row">
                    @foreach($solicitudes as $solicitud)
                        <div class="col-md-4 mb-4 d-flex align-items-stretch">
                            <div class="card w-100
                                @if($solicitud->estado === 'aprobado') border-success
                                @elseif($solicitud->estado === 'rechazado') border-danger
                                @else border-warning
                                @endif">

                                <div class="card-body">
                                    <div class="id-container">
                                        <span class="id-label">
                                            ID {{ optional($solicitud->vacacion)->id ?? $solicitud->id }}
                                        </span>
                                    </div>

                                    <h5 class="card-title">
                                        {{ $solicitud->trabajador->Nombre ?? 'Sin nombre' }}
                                        {{ $solicitud->trabajador->ApellidoPaterno ?? '' }}
                                    </h5>

                                    <p><strong>Tipo de Día:</strong> {{ ucfirst(str_replace('_', ' ', $solicitud->tipo_dia)) }}</p>

                                    <p>
                                        <strong>Descripción:</strong>
                                        {{ \Illuminate\Support\Str::limit($solicitud->descripcion, 60) }}
                                    </p>

                                    <p>
                                        <strong>Fecha Inicio:</strong>
                                        {{ optional(optional($solicitud->vacacion)->fecha_inicio)->format('Y-m-d') ?? 'N/A' }}
                                    </p>

                                    <p>
                                        <strong>Fecha Fin:</strong>
                                        {{ optional(optional($solicitud->vacacion)->fecha_fin)->format('Y-m-d') ?? 'N/A' }}
                                    </p>

                                    <p>
                                        <strong>Días Solicitados:</strong>
                                        {{ optional($solicitud->vacacion)->dias ?? 0 }}
                                    </p>

                                    @if($solicitud->tipo_dia === 'vacaciones')
                                        <p>
                                            <strong>Días Descontados:</strong>
                                            {{ optional($solicitud->vacacion)->dias ?? 0 }}
                                        </p>
                                    @else
                                        <p><strong>Días Descontados:</strong> 0</p>
                                    @endif

                                    @if(optional($solicitud->vacacion)->archivo)
                                        <p>
                                            <strong>Archivo Adjunto:</strong>
                                            <a href="{{ route('vacaciones.descargar', $solicitud->vacacion->id) }}"
                                               target="_blank">
                                                Descargar Archivo
                                            </a>
                                        </p>
                                    @endif

                                    <p><strong>Estado:</strong> {{ ucfirst($solicitud->estado) }}</p>

                                    <div class="mt-3">
                                        @if($solicitud->estado === 'pendiente' && Auth::id() !== 376)
                                            <form action="{{ route('solicitudes.vacaciones.approve', $solicitud->id) }}"
                                                  method="POST"
                                                  enctype="multipart/form-data"
                                                  class="mb-3">
                                                @csrf
                                                <div class="form-group">
                                                    <label for="comentario_admin_aprobar_{{ $solicitud->id }}">
                                                        Comentario del Administrador
                                                    </label>
                                                    <textarea
                                                        name="comentario_admin"
                                                        id="comentario_admin_aprobar_{{ $solicitud->id }}"
                                                        class="form-control"
                                                        rows="2"
                                                        required
                                                    ></textarea>
                                                </div>
                                                <button type="submit" class="btn btn-sm btn-outline-primary mt-2">
                                                    Aprobar
                                                </button>
                                            </form>

                                            <form action="{{ route('solicitudes.vacaciones.reject', $solicitud->id) }}"
                                                  method="POST"
                                                  enctype="multipart/form-data">
                                                @csrf
                                                <div class="form-group">
                                                    <label for="comentario_admin_rechazar_{{ $solicitud->id }}">
                                                        Comentario del Administrador
                                                    </label>
                                                    <textarea
                                                        name="comentario_admin"
                                                        id="comentario_admin_rechazar_{{ $solicitud->id }}"
                                                        class="form-control"
                                                        rows="2"
                                                        required
                                                    ></textarea>
                                                </div>
                                                <button type="submit" class="btn btn-sm btn-outline-danger mt-2">
                                                    Rechazar
                                                </button>
                                            </form>
                                        @endif

                                        @if(
                                            ($solicitud->estado === 'aprobado' || $solicitud->estado === 'rechazado')
                                            && optional($solicitud->vacacion)->archivo_respuesta_admin === null
                                            && $solicitud->vacacion
                                        )
                                            <form action="{{ route('solicitudes.vacaciones.approve', $solicitud->id) }}"
                                                  method="POST"
                                                  enctype="multipart/form-data">
                                                @csrf
                                                <div class="form-group">
                                                    <label for="archivo_respuesta_admin_{{ $solicitud->id }}">
                                                        Subir archivo escaneado y firmado
                                                    </label>
                                                    <input
                                                        type="file"
                                                        name="archivo_respuesta_admin"
                                                        class="form-control"
                                                        id="archivo_respuesta_admin_{{ $solicitud->id }}"
                                                        required
                                                    >
                                                </div>

                                                <button type="submit" class="btn btn-sm btn-outline-primary mt-2">
                                                    Subir Respaldo
                                                </button>

                                                @if(
                                                    $solicitud->estado === 'aprobado'
                                                    && optional($solicitud->vacacion)->archivo_admin
                                                )
                                                    <a href="{{ route('vacaciones.descargarArchivoAdmin', $solicitud->vacacion->id) }}"
                                                       class="btn btn-sm btn-outline-danger mt-2">
                                                        <i class="fa-solid fa-file-pdf"></i>
                                                        Descargar PDF de solicitud de días
                                                    </a>
                                                @endif
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($solicitudes instanceof \Illuminate\Pagination\LengthAwarePaginator && $solicitudes->hasPages())
                    <div class="py-3 d-flex justify-content-center">
                        {{ $solicitudes->links('pagination::bootstrap-4') }}
                    </div>
                @endif
            @else
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <h5 class="mb-2">No se encontraron solicitudes</h5>
                        <p class="text-muted mb-0">
                            Intenta ajustar el buscador o el filtro de estado.
                        </p>
                    </div>
                </div>
            @endif

        </div>
    </div>

    <a href="{{ url('/empleados') }}" class="btn btn-primary mt-3">
        <i class="fas fa-arrow-left"></i> Regresar al listado de empleados
    </a>
</div>

@endsection