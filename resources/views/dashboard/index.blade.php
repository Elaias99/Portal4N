@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Botón para ocultar/mostrar Sidebar -->
        

        <!-- Sidebar Izquierdo -->
        <div id="sidebarPanel" class="col-md-3 sidebar">
            <h4 class="fw-bold text-dark mt-3">📋 Información</h4>
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

            <button id="toggleSidebar" class="btn btn-secondary sidebar-toggle">☰ Mostrar/Ocultar Sidebar</button>             

            <!-- Sección de Resumen -->
            <div id="section-resumen" class="content-section">
                <div class="row mt-4 justify-content-center">
                    <!-- Tarjeta Total de Empleados -->
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <div class="stat-card p-4 text-center shadow-lg">
                            <h6 class="text-muted">Total de Empleados</h6>
                            <h2 class="fw-bold text-primary">{{ count($empleadosConSaldo) }}</h2>
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
                        @foreach ($cargos->chunk(ceil($cargos->count() / 3)) as $grupo)
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
                @if ($solicitudesPendientes->isEmpty())
                    <p class="text-muted">No hay solicitudes pendientes.</p>
                @else
                    <ul class="list-group">
                        @foreach ($solicitudesPendientes as $solicitud)
                            <li class="list-group-item">
                                {{ $solicitud->trabajador->Nombre }} - {{ ucfirst($solicitud->campo) }} 
                                <span class="badge bg-warning text-dark">Pendiente</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <!-- Sección de Nuevos Empleados -->
            <div id="section-nuevos" class="content-section d-none">
                <h5 class="fw-bold text-dark">🆕 Nuevos Empleados</h5>
                @if ($empleadosNuevos->isEmpty())
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
                @if ($empleadosDesvinculados->isEmpty())
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

<!-- Estilos Mejorados -->
<style>
    .sidebar {
        background: white;
        height: 100vh;
        box-shadow: 2px 0px 10px rgba(0, 0, 0, 0.1);
        padding: 20px;
        width: 250px; /* Asegura un ancho definido */
        transition: all 0.3s ease-in-out;
        position: fixed; /* Fijarlo a la izquierda */
        left: 0; /* Asegurar que inicie en la posición correcta */
    }

    .sidebar-hidden {
        left: -250px;
    }

    .sidebar-toggle {
        position: absolute;
        left: 10px;
        top: 10px;
        z-index: 1000;
    }

    .nav-link {
        font-size: 16px;
        font-weight: bold;
        color: #333;
        padding: 10px;
        cursor: pointer;
    }

    .nav-link.active {
        color: #007bff;
    }

    .content-section {
        padding: 20px;
        background: white;
        border-radius: 10px;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
    }

    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        min-height: 140px; /* Para mantener las tarjetas del mismo tamaño */
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    }

    .empresa-logo {
        max-width: 80px;
        max-height: 50px;
        object-fit: contain;
        margin-bottom: 10px;
    }

    #mainContent {
        transition: margin-left 0.3s ease-in-out;
        margin-left: 250px;
    }

    .main-expanded {
        margin-left: 0 !important;
    }


    .cargo-card {
        background: white;
        border-radius: 6px; /* Redondeamos un poco menos */
        padding: 8px;  /* Reducimos el padding */
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        min-height: 80px; /* Reducimos la altura */
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-size: 12px;  /* Hacemos el texto más pequeño */
    }







</style>

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
                    initialView: 'dayGridMonth'
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



<script>

    $(document).ready(function () {
        // Alternar visibilidad del sidebar
        $("#toggleSidebar").click(function () {
            let sidebar = $("#sidebarPanel");
            let mainContent = $("#mainContent");

            if (sidebar.hasClass("sidebar-hidden")) {
                sidebar.removeClass("sidebar-hidden");
                mainContent.css("margin-left", "250px");
            } else {
                sidebar.addClass("sidebar-hidden");
                mainContent.css("margin-left", "0px");
            }
        });

        // Alternar entre secciones del dashboard
        $(".section-link").click(function (event) {
            event.preventDefault(); // Evita que se recargue la página al hacer clic

            // Remover la clase "active" de todos los enlaces y añadirla solo al seleccionado
            $(".section-link").removeClass("active");
            $(this).addClass("active");

            // Ocultar todas las secciones y mostrar solo la seleccionada
            $(".content-section").addClass("d-none");
            let sectionId = $(this).data("section");
            $("#section-" + sectionId).removeClass("d-none");
        });
    });

    
</script>


@endsection
