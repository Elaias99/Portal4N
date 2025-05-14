@extends('layouts.app')

@section('content')


<div class="container">

    {{-- CABECERA ORGANIZADA CON TÍTULO + ACCIÓN DE IMPORTACIÓN INTEGRADA --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">

        {{-- TÍTULO --}}
        <div>
            <h3 class="text-dark fw-semibold mb-1">
                <i class="fa-solid fa-box-open me-2 text-secondary"></i> Gestión de Bultos del Día
            </h3>
            <p class="text-muted small mb-0">Administra, busca o carga los bultos del día desde un archivo Excel</p>
        </div>

        {{-- BLOQUE DE IMPORTACIÓN COMO UN COMPONENTE --}}
        <form action="{{ route('bultos.import') }}" method="POST" enctype="multipart/form-data"
            class="bg-light rounded shadow-sm px-3 py-2 d-flex align-items-center gap-2"
            style="max-width: 100%;">
            @csrf
            <i class="fa-solid fa-file-excel text-success"></i>
            <input type="file" name="file" class="form-control form-control-sm" required style="max-width: 180px;">
            <button type="submit" class="btn btn-success btn-sm">
                <i class="fa-solid fa-upload me-1"></i> Importar
            </button>
        </form>
    </div>




    {{-- ALERTAS DE ÉXITO O ERROR --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <!-- Buscador -->
    {{-- BUSCADOR DE BULTOS EN TARJETA --}}
    <div class="card shadow-sm p-3 mb-4" style="max-width: 600px; margin: 0 auto;">
        <h6 class="mb-3 text-center text-muted">
            <i class="fa-solid fa-magnifying-glass me-2"></i> Buscar Bulto por Código
        </h6>
        <form action="{{ route('bultos.index') }}" method="GET">
            <div class="input-group input-group-sm">
                <input type="text" name="codigo_bulto" autofocus class="form-control" 
                       placeholder="Ej: 003936646 — Ingrese el código exacto del bulto" required>
                <button class="btn btn-primary" type="submit">
                    <i class="fa-solid fa-search me-1"></i> Buscar
                </button>
            </div>
        </form>
    </div>

    @if ($bultos && count($bultos) > 0)
    <div class="row justify-content-center">
        @foreach ($bultos as $bulto)
            @php
                $ultimoReclamo = $bulto->reclamos()->latest()->first();
            @endphp

            <div class="card shadow-sm mb-4" style="max-width: 800px;">
                <div class="card-body">

                    {{-- Cabecera --}}
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0 fw-bold text-primary">
                            <i class="fa-solid fa-box me-1"></i> {{ $bulto->codigo_bulto }}
                        </h5>
                        <small class="text-muted">ID #{{ $bulto->id }}</small>
                    </div>

                    {{-- Info agrupada --}}
                    <div class="row small text-muted">
                        <div class="col-md-6 mb-2">
                            <strong>Dirección:</strong> {{ $bulto->direccion }}, {{ $bulto->comuna->Nombre ?? '—' }} ({{ $bulto->depto_destino }})
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Región:</strong> {{ $bulto->comuna->region->Nombre ?? '—' }}
                        </div>

                        <div class="col-md-6 mb-2">
                            <strong>Campaña:</strong> {{ $bulto->nombre_campana }}
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Entrega:</strong>
                            <span class="badge bg-light text-dark">{{ $bulto->fecha_entrega }}</span>
                        </div>

                        <div class="col-md-6 mb-2">
                            <strong>Atención:</strong> {{ $bulto->atencion }} ({{ $bulto->numero_destino }})
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Ubicación:</strong> {{ $bulto->ubicacion }}
                        </div>

                        <div class="col-md-6 mb-2">
                            <strong>Razón Social:</strong> {{ $bulto->razon_social }}
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Peso:</strong> {{ $bulto->peso }} kg
                        </div>

                        <div class="col-md-6 mb-2">
                            <strong>Teléfono:</strong> {{ $bulto->telefono }}
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Email:</strong> {{ $bulto->mail }}
                        </div>

                        <div class="col-md-6 mb-2">
                            <strong>Observación:</strong> {{ $bulto->observacion }}
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Referencia:</strong> {{ $bulto->referencia }}
                        </div>

                        <div class="col-md-6 mb-2">
                            <strong>Descripción:</strong> {{ $bulto->descripcion_bulto }}
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Unidad:</strong> {{ $bulto->unidad }}
                        </div>

                        <div class="col-md-6 mb-2">
                            <strong>Estado Reclamo:</strong>
                            @if ($ultimoReclamo)
                                @if ($ultimoReclamo->estado === 'cerrado')
                                    <span class="badge bg-success">Cerrado</span>
                                @elseif ($ultimoReclamo->estado === 'pendiente')
                                    <span class="badge bg-warning text-dark">Pendiente</span>
                                @else
                                    <span class="badge bg-info">Otro</span>
                                @endif
                            @else
                                <span class="badge bg-secondary">Sin Reclamo</span>
                            @endif
                        </div>
                    </div>

                    {{-- Acción --}}
                    {{-- Acción --}}
                    <div class="mt-3 d-flex flex-column gap-2">
                        @if (!$ultimoReclamo)
                            {{-- No hay reclamo todavía --}}
                            <button type="button" class="btn btn-outline-danger btn-sm"
                                    data-toggle="modal"
                                    data-target="#reclamoModal"
                                    data-bulto-id="{{ $bulto->id }}"
                                    data-bulto-codigo="{{ $bulto->codigo_bulto }}">
                                <i class="fa-solid fa-circle-exclamation me-1"></i> Reportar Reclamo
                            </button>
                        @elseif ($ultimoReclamo->estado === 'cerrado')
                            {{-- Reclamo cerrado: se puede reabrir --}}
                            <form action="{{ route('reclamos.reabrir', $ultimoReclamo->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-sm">
                                    <i class="fa-solid fa-rotate-left me-1"></i> Reabrir Reclamo
                                </button>
                            </form>
                        @else
                            {{-- Reclamo pendiente: deshabilitado --}}
                            <button class="btn btn-secondary btn-sm" disabled title="Ya existe un reclamo pendiente">
                                <i class="fa-solid fa-ban me-1"></i> Reclamo en curso
                            </button>
                        @endif

                        @if ($ultimoReclamo)
                            <a href="{{ route('reclamos.ver', $ultimoReclamo->id) }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fa-solid fa-comments me-1"></i> Ver Historial de Reclamo
                            </a>
                        @endif
                    </div>

                    




                </div>
            </div>
        @endforeach

        {{-- MODAL --}}
        @include('reclamos._modal')
        @include('reclamos._modal-script')

    </div>
    @else
        <div class="alert alert-warning text-center mt-3">
            No se encontraron registros de bultos con ese código.
        </div>
    @endif




</div>
@unless(auth()->user()->hasAnyRole(['admin', 'jefe']))
    <div class="mb-3">
        <a href="{{ route('empleados.perfil') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver al Perfil
        </a>
    </div>
@endunless
@endsection

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
