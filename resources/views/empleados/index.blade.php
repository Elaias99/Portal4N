@extends('layouts.app')

@php
$notificaciones = Auth::user()->unreadNotifications
    ->whereIn('type', [
        'App\Notifications\NotificacionAdmin',
        'App\Notifications\NotificacionAdminVacaciones',
        'App\Notifications\NuevoReclamoAreaNotification',
        'App\Notifications\ReclamoRespondidoNotification',
        'App\Notifications\NuevoComentarioReclamoNotification',
        'App\Notifications\ReclamoCerradoNotification',
    ])
    ->sortByDesc('created_at');
@endphp
@section('content')

<div class="container"> {{-- O si quieres ocupar todo el ancho: container-fluid --}}


       @if(Session::has('Mensaje'))
        <div class="alert alert-success" role="alert">
            {{ Session::get('Mensaje') }}
        </div>
    @endif

    <h1 class="text-center" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);">Listado de Empleados</h1>
    <br>

    <div class="row">

        <!-- Sidebar de filtros -->
        <div class="col-lg-2">
            @component('layouts.columna_izquierda', [
                'tituloTarjeta' => 'Accesos rápidos',
                'tituloFiltros' => 'Filtrar Por',
                'action' => route('empleados.index')
            ])
                @slot('acciones')
                    <div class="d-grid gap-2 mt-2">
                        {{-- 🔹 Nuevo acceso al Reporte de Documentos Financieros --}}
                        @if (in_array(Auth::id(), [1, 405]))
                            <a href="{{ route('cobranzas.documentos') }}" 
                            class="btn btn-outline-secondary text-start" 
                            data-bs-toggle="tooltip" 
                            title="Reporte de Documentos Financieros">
                                <i class="fa-solid fa-file-invoice-dollar me-2"></i> Cobranzas
                            </a>
                        @endif



                        <a href="{{ route('historial-vacacion.index') }}" class="btn btn-outline-secondary text-start" data-bs-toggle="tooltip" title="Vacaciones">
                            <i class="fa-solid fa-plane-departure me-2"></i> Vacaciones
                        </a>

                        <a href="{{ route('areas.index') }}" class="btn btn-outline-secondary text-start" data-bs-toggle="tooltip" title="Áreas">
                            <i class="fa-solid fa-person me-2"></i> Áreas
                        </a>




                    </div>
                @endslot

                @slot('filtros')
                    <div class="mb-3">
                        <label class="form-label">Nombre:</label>
                        <input type="text" name="search" class="form-control" placeholder="Buscar..." value="{{ request('search') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Cargo:</label>
                        <select name="cargo_id" class="form-select form-select-sm">
                            <option value="">- Seleccionar Cargo -</option>
                            @foreach($cargos as $cargo)
                                <option value="{{ $cargo->id }}" {{ request('cargo_id') == $cargo->id ? 'selected' : '' }}>
                                    {{ $cargo->Nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Empresa:</label>
                        <select name="empresa_id" class="form-select form-select-sm">
                            <option value="">- Seleccionar Empresa -</option>
                            @foreach($empresas as $empresa)
                                <option value="{{ $empresa->id }}" {{ request('empresa_id') == $empresa->id ? 'selected' : '' }}>
                                    {{ $empresa->Nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        {{-- <h6 class="fw-bold">Beneficios:</h6>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="casino" value="1" {{ request('casino') ? 'checked' : '' }}>
                            <label class="form-check-label">Acceso al Casino</label>
                        </div> --}}

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="mostrar_desvinculados" value="1" {{ request('mostrar_desvinculados') ? 'checked' : '' }}>
                            <label class="form-check-label">Mostrar desvinculados</label>
                        </div>
                    </div>
                @endslot
            @endcomponent
        </div>



        <!-- Contenido principal -->
        <div class="col-lg-10">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <!-- Exportar -->
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle shadow-sm" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-regular fa-file-excel me-2"></i> Exportar
                    </button>
                    <div class="dropdown-menu shadow-sm fade" aria-labelledby="exportDropdown">


                        <a class="dropdown-item d-flex align-items-center" href="#" data-toggle="modal" data-target="#modalExportarExcel">

                            <i class="fa-solid fa-file-excel text-success me-2"></i> Exportar a Excel
                        </a>




                        <a class="dropdown-item d-flex align-items-center" href="{{ route('empleados.exportPdf') }}">
                            <i class="fa-solid fa-file-pdf text-danger me-2"></i> Exportar a PDF
                        </a>
                    </div>
                </div>

                <!-- Botón para crear empleado -->
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('empleados.create') }}" class="btn btn-outline-dark shadow-sm" data-bs-toggle="tooltip" title="Agregar Empleado">
                        <i class="fa-solid fa-user-plus fa-lg"></i>
                    </a>
                    
                </div>

            </div>

            <!-- Listado de Empleados -->
            <!-- NOTA: quitamos la columna col-lg-9 para aprovechar todo el ancho de col-lg-10 -->
            <div class="row">
                @foreach($empleados as $empleado)
                    <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-3">
                        <div class="card mb-3 shadow-sm position-relative" style="border-radius: 10px;">
                            <!-- Imagen de cumpleaños -->
                            @if ($empleado->is_birthday)
                                <img src="{{ asset('images/gorra1.png') }}" alt="Cumpleaños" 
                                     class="position-absolute" 
                                     style="top: -50px; right: -49px; width: 120px; height: auto;">
                            @endif

                            <div class="card-body p-2">
                                <!-- Nombre y cargo -->
                                <div class="text-center mb-2">
                                    <h5 class="card-title mb-1">
                                        {{ $empleado->Nombre }} {{ $empleado->ApellidoPaterno }}
                                    </h5>
                                    <small class="text-muted">
                                        {{ $empleado->cargo->Nombre }}
                                    </small>
                                </div>

                                <!-- Logo y estado -->
                                <div class="text-center mb-2">
                                    @if(optional($empleado->sistemaTrabajo)->nombre === 'Desvinculado' 
                                        && optional($empleado->situacion)->Nombre === 'Desvinculado')
                                        <i class="fa-solid fa-triangle-exclamation text-warning fa-lg"></i>
                                        <h6 class="text-primary fw-bold">Desvinculado</h6>
                                    @else



                                        @if($empleado->empresa && $empleado->empresa->logo)


                                            <img src="{{ url($empleado->empresa->logo) }}" 
                                            alt="Logo de {{ $empleado->empresa->Nombre }}" 
                                            style="max-height: 50px;">


                                        @else



                                            <p class="text-muted">No hay logo disponible</p>
                                        @endif
                                    @endif
                                </div>

                                <!-- Botones -->
                                <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                                    @role('admin')
                                    <a href="{{ route('empleados.edit', $empleado->id) }}" class="btn btn-outline-primary btn-sm">Editar</a>
                                    <form method="POST" action="{{ route('empleados.destroy', $empleado->id) }}" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm" type="submit" onclick="return confirm('¿Seguro que deseas eliminar a este Empleado?');">Eliminar</button>
                                    </form>
                                    @endrole
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#employeeModal{{ $empleado->id }}">
                                        Ver Detalles
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @include('partials.employee_modal', ['empleado' => $empleado])

                
                @endforeach
            </div> <!-- fin .row del listado -->
        </div> <!-- fin .col-lg-10 -->
    </div> <!-- fin .row -->
</div> <!-- fin .container -->

@include('empleados.modal_exportar_excel')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });
</script>
@endsection
