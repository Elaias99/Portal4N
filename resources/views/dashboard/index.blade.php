@extends('layouts.app')

@section('content')

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">

@vite(['resources/css/dashboard.css'])


<div class="container-fluid">
    <div class="row">
        <!-- Botón para ocultar/mostrar Sidebar -->

        <!-- Sidebar Izquierdo -->
        <div id="sidebarPanel" class="col-md-3 sidebar">
            <button id="toggleSidebar" class="btn btn-secondary sidebar-toggle">☰</button>

            <!-- Tarjeta del Usuario Autenticado -->
            <div class="card mt-3 shadow user-card">
                <div class="card-body p-2">
                    @php
                        $correoPerfil = resolvePerfilEmail(Auth::user()->email);
                        $usuario = \App\Models\User::where('email', $correoPerfil)->first();
                        $empleado = $usuario ? \App\Models\Trabajador::where('user_id', $usuario->id)->first() : null;
                    @endphp

                    @if($empleado)
                        <!-- Nombre y cargo -->
                        <div class="text-center mb-2">
                            <h5 class="card-title mb-1">
                                {{ $empleado->Nombre }} {{ $empleado->ApellidoPaterno }}
                            </h5>
                            <small class="text-muted">
                                {{ optional($empleado->cargo)->Nombre ?? 'Sin cargo' }}
                            </small>
                        </div>

                        <!-- Logo de la Empresa o Estado -->
                        <div class="text-center mb-2">
                            @if(optional($empleado->sistemaTrabajo)->nombre === 'Desvinculado' 
                                && optional($empleado->situacion)->Nombre === 'Desvinculado')
                                <i class="fa-solid fa-triangle-exclamation text-warning fa-lg"></i>
                                <h6 class="text-primary fw-bold">Desvinculado</h6>
                            @else
                                @if($empleado->empresa && $empleado->empresa->logo)
                                    <img src="{{ asset('storage/' . $empleado->empresa->logo) }}" 
                                        alt="Logo de {{ $empleado->empresa->Nombre }}" 
                                        style="max-height: 50px;">
                                @else
                                    <p class="text-muted">No hay logo disponible</p>
                                @endif
                            @endif
                        </div>

                    @else
                        <p class="text-muted text-center">No se encontró un perfil asociado.</p>
                    @endif
                </div>
            </div>

            <ul class="nav flex-column">

                <li class="nav-item">
                    <a class="nav-link active section-link" href="#" data-section="resumen">📊 Resumen de Empleados</a>
                </li>


                <li class="nav-item">
                    <a class="nav-link section-link" href="#" data-section="asistencia">📌 Asistencia de Empleados</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link section-link" href="#" data-section="solicitudes">📑 Solicitudes Pendientes</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link section-link" href="#" data-section="nuevos">🆕 Nuevos Empleados</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link section-link" href="#" data-section="desvinculados">❌ Empleados Desvinculados</a>
                </li>
            </ul>
        </div>

        <!-- Contenido Principal -->
        <div id="mainContent" class="col-md-9">
              

            <!-- Sección de Resumen -->
            <div id="section-resumen" class="content-section">
                <div class="row mt-4 justify-content-center">
                    <!-- Tarjeta Total de Empleados -->
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <div class="stat-card p-4 text-center shadow-lg">
                            <h6 class="text-muted">Total de Empleados</h6>

                            <h2 class="fw-bold text-primary">{{ $empleadosConSaldoCount }}</h2>

                        </div>

                    </div>
            
                    <!-- Tarjetas de Empresas -->
                    @foreach ($empresas as $empresa)
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <div class="stat-card p-4 text-center shadow-lg">
                            @if (!empty($empresa->logo))
                                <img src="{{ asset('storage/' . $empresa->logo) }}" alt="Logo" class="empresa-logo">
                            @endif
                            <h2 class="fw-bold text-primary">{{ $empresa->trabajadores_count }}</h2>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Nueva sección: Desglose por Cargo -->
                <div class="container mt-4">
                    <h5 class="fw-bold text-dark">📋 Desglose por Cargo</h5>
                    <div class="row">
                        @foreach ($cargosChunked as $grupo)
                        <div class="col-md-4">
                            <ul class="list-group list-group-flush">
                                @foreach ($grupo as $cargo)
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>{{ $cargo->Nombre }}</span>
                                    <span class="fw-bold text-primary">{{ $cargo->trabajadors_count }}</span>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endforeach
                    </div>
                </div>
                
            </div>


            <!-- Sección de Asistencia -->
            <div id="section-asistencia" class="content-section d-none">
                <div class="text-center p-4">
                    <h5 class="fw-bold text-dark">📌 Asistencia de Empleados</h5>
                    <p class="text-muted">Registra la asistencia de los empleados de hoy.</p>
                    <a href="{{ route('asistencia.index') }}" class="btn btn-primary">Ir a Marcar Asistencia</a>
                    
                </div>
                <div class="container mt-4" class="content-section d-none">
                    <p class="text-muted">Aquí puedes ver el calendario.</p>
                    <div id="calendar"></div> <!-- Calendario aquí -->
                </div>
            </div>

            <!-- Sección de Solicitudes Pendientes -->
            <div id="section-solicitudes" class="content-section d-none">
                <h5 class="fw-bold text-dark">📑 Solicitudes Pendientes</h5>

                @if (!$haySolicitudesPendientes)
                    <p class="text-muted">No hay solicitudes pendientes.</p>
                @else
                    <ul class="list-group">
                        @foreach ($solicitudesPendientes as $solicitud)
                            <li class="list-group-item">
                                {{ $solicitud->trabajador->Nombre }} - {{ $solicitud->campo }}
                                <span class="badge bg-warning text-dark">Pendiente</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <!-- Sección de Nuevos Empleados -->
            <div id="section-nuevos" class="content-section d-none">
                <h5 class="fw-bold text-dark">🆕 Nuevos Empleados</h5>

                @if (!$hayEmpleadosNuevos)
                    <p class="text-muted">No hay nuevos empleados en el último mes.</p>
                @else
                    <ul class="list-group">
                        @foreach ($empleadosNuevos as $empleado)
                            <li class="list-group-item">
                                {{ $empleado->Nombre }} - {{ $empleado->cargo->Nombre }}
                                <span class="badge bg-success">Nuevo</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <!-- Sección de Empleados Desvinculados -->
            <div id="section-desvinculados" class="content-section d-none">
                <h5 class="fw-bold text-dark">❌ Empleados Desvinculados</h5>
            
                @if (!$hayEmpleadosDesvinculados)
                    <p class="text-muted">No hay empleados desvinculados.</p>
                @else
                    <ul class="list-group">
                        @foreach ($empleadosDesvinculados as $empleado)
                            <li class="list-group-item">
                                {{ $empleado->Nombre }} {{ $empleado->ApellidoPaterno }} - 
                                {{ $empleado->cargo->Nombre ?? 'Sin cargo' }}
                                <span class="badge bg-danger">Desvinculado</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
            



        </div>
    </div>
</div>

<!-- Script de interacción -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">

<!-- FullCalendar JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        let calendar = null;
        let calendarEl = document.getElementById('calendar');

        document.querySelector('[data-section="asistencia"]').addEventListener('click', function () {
            document.getElementById('section-asistencia').classList.remove('d-none');

            if (!calendar) {
                calendar = new FullCalendar.Calendar(calendarEl, {
                    locale: 'es',
                    initialView: 'dayGridMonth',
                    events: [
                        @foreach ($vacacionesAprobadas as $vacacion)
                        {
                            title: '{{ trim($vacacion->trabajador->Nombre . " " . $vacacion->trabajador->ApellidoPaterno) }} (Hasta: {{ \Carbon\Carbon::parse($vacacion->fecha_fin)->format("d/m/Y") }})',
                            start: '{{ $vacacion->fecha_inicio }}',
                            color: '#a2d9ff', // Azul claro como en la imagen
                            textColor: '#000', // Texto negro para mejor visibilidad
                            classNames: ['vacaciones-bubble'] // Agrega una clase personalizada
                        },
                        @endforeach
                    ]
                });

                calendar.render();
            } else {
                setTimeout(() => {
                    calendar.updateSize();
                }, 200);
            }
        });
    });
</script>

<style>
    .vacaciones-bubble {
        padding: 5px;
        border-radius: 8px;
        font-size: 8px;
        font-weight: bold;
        text-align: center;
        background-color: #a2d9ff; /* Color azul claro */
        color: black;
    }
</style>



<script>

    $(document).ready(function () {
        $("#toggleSidebar").click(function () {
            let sidebar = $("#sidebarPanel");
            let button = $("#toggleSidebar");

            if (sidebar.hasClass("sidebar-hidden")) {
                sidebar.removeClass("sidebar-hidden");
                button.css("left", "230px"); // Mueve el botón de regreso al borde del sidebar
            } else {
                sidebar.addClass("sidebar-hidden");
                button.css("left", "10px"); // Mueve el botón a la izquierda cuando el sidebar está oculto
            }
        });

        // Alternar entre secciones del dashboard
        $(".section-link").click(function (event) {
            event.preventDefault(); // Evita que se recargue la página al hacer clic

            $(".section-link").removeClass("active");
            $(this).addClass("active");

            $(".content-section").addClass("d-none");
            let sectionId = $(this).data("section");
            $("#section-" + sectionId).removeClass("d-none");
        });
    });


    
</script>


@endsection
