@extends('layouts.app')

@php
$notificaciones = Auth::user()->unreadNotifications
    ->whereIn('type', [
        'App\Notifications\SolicitudActualizada',
        'App\Notifications\NuevoReclamoAreaNotification',
        'App\Notifications\ReclamoRespondidoNotification',
        'App\Notifications\NuevoComentarioReclamoNotification',
        'App\Notifications\ReclamoCerradoNotification',
    ])
    ->sortByDesc('created_at');
@endphp


@vite(['resources/css/custom.css'])

@section('content')
@if (session('warning'))
    <div class="alert alert-warning">
        {{ session('warning') }}
    </div>
@endif

@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

<div class="container mb-4">
    <h4 class="mb-3">🔔 Notificaciones</h4>

    <form method="POST" action="{{ route('notifications.markAllAsRead') }}" class="mb-3 text-end">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-primary">
            Marcar todas como leídas
        </button>
    </form>

    <div class="list-group" id="notificacionesEmpleado">
        {{-- JavaScript insertará las notificaciones aquí --}}
    </div>
</div>





<div class="container">
    <h1 class="mb-4 text-center">Perfil del Empleado</h1>

    <!-- Contenedor de perfil de empleado -->
    <div class="row">
        <!-- Columna izquierda: Foto del empleado -->
        <div class="col-12 col-md-4">

            <div class="card p-3 mb-4 text-center">
                @if($trabajador->Foto)
                    <img 
                        src="{{ url('storage/' . $trabajador->Foto) }}" 
                        class="img-fluid mb-3 profile-picture" 
                        alt="Foto de {{ $trabajador->Nombre }}">
                @else
                    <img 
                        src="{{ url('images/default-avatar.png') }}" 
                        class="img-fluid mb-3 rounded-circle" 
                        alt="Imagen predeterminada">
                @endif

                <a href="{{ route('perfiles.editar', $trabajador->id) }}" 
                   class="btn btn-outline-primary btn-custom-width mb-2">
                   Actualizar mi perfil
                </a>

                <a href="{{ route('perfiles.cambiar_contraseña', $trabajador->id) }}" 
                   class="btn btn-outline-primary btn-custom-width mb-3">
                   Cambiar mi contraseña
                </a>

                <a href="{{ route('perfiles.solicitudes') }}" 
                   class="btn btn-primary btn-custom-width mb-3">
                   Consultar mis solicitudes
                </a>

                <a href="{{ route('solicitudes.create') }}" 
                   class="btn btn-primary btn-custom-width mb-3">
                   Solicitar cambio de datos
                </a>


                <a href="{{ route('vacaciones.create') }}" 
                   class="btn btn-primary btn-custom-width mb-3">
                    Solicitar Permiso de Días
                </a>

                {{-- <a href="{{ route('perfiles.reclamos.area') }}" 
                    class="btn btn-primary btn-custom-width mb-3">
                        Ver Reclamos del Área
                </a>


                <a href="{{ route('bultos.index') }}" 
                    class="btn btn-primary btn-custom-width mb-3">
                        Buscar Bulto
                </a>

                <a href="{{ route('reclamos.mios') }}" 
                    class="btn btn-primary btn-custom-width mb-3">
                        Mis Reclamos
                </a> --}}






            </div>
        </div>

        <!-- Columna derecha: Información del empleado -->
        <div class="col-12 col-md-8">
            <div class="card p-4 mb-4">

                <!-- Acordeón para Información Personal -->
                <div class="accordion" id="accordionPerfil">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingPersonal">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapsePersonal" aria-expanded="true" aria-controls="collapsePersonal">
                                <i class="fa-solid fa-user me-2"></i> Información Personal
                            </button>
                        </h2>
                        <div id="collapsePersonal" class="accordion-collapse collapse show"
                             aria-labelledby="headingPersonal" data-bs-parent="#accordionPerfil">
                            <div class="table-responsive">
                                <table class="table table-borderless">
                                    <tbody>
                                        <tr>
                                            <th><i class="fa-solid fa-id-card me-2"></i>RUT</th>
                                            <td>{{ $trabajador->Rut }}</td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-user me-2"></i>Nombre Completo</th>
                                            <td>
                                                {{ $trabajador->Nombre }} {{ $trabajador->SegundoNombre }}
                                                {{ $trabajador->ApellidoPaterno }} {{ $trabajador->ApellidoMaterno }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-calendar-days me-2"></i>Fecha de Nacimiento</th>
                                            <td>{{ $trabajador->FechaNacimiento->translatedFormat('d F, Y') }}</td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-cake-candles me-2"></i>Edad</th>
                                            <td>{{ $trabajador->edad }} años</td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-phone me-2"></i>Número Celular</th>
                                            <td>{{ $trabajador->numero_celular }}</td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-user-group me-2"></i>Contacto de Emergencia</th>
                                            <td>{{ $trabajador->nombre_emergencia }} |
                                                Número: {{ $trabajador->contacto_emergencia }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-location-dot me-2"></i>Dirección</th>
                                            <td>{{ $trabajador->calle }} | {{ $trabajador->comuna->Nombre }}</td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-envelope me-2"></i>Correo Personal</th>
                                            <td>{{ $trabajador->CorreoPersonal }}</td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-university me-2"></i>Banco</th>
                                            <td>{{ $trabajador->banco->nombre }}</td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-credit-card me-2"></i>Tipo de Cuenta</th>
                                            <td>{{ $trabajador->tipo_cuenta }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Acordeón para Información de Empleo -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingEmpleo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseEmpleo" aria-expanded="false" aria-controls="collapseEmpleo">
                                <i class="fa-solid fa-briefcase me-2"></i> Información de Empleo
                            </button>
                        </h2>
                        <div id="collapseEmpleo" class="accordion-collapse collapse"
                             aria-labelledby="headingEmpleo" data-bs-parent="#accordionPerfil">
                            <div class="table-responsive">
                                <table class="table table-borderless">
                                    <tbody>
                                        <tr>
                                            <th><i class="fa-solid fa-building me-2"></i>Empresa</th>
                                            <td>{{ $trabajador->empresa->Nombre }}</td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-envelope me-2"></i>Correo Corporativo</th>
                                            <td>{{ $trabajador->user->email }}</td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-calendar-check me-2"></i>Fecha de Contratación</th>
                                            <td>{{ $trabajador->fecha_inicio_trabajo->translatedFormat('d F, Y') }}</td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-user-tie me-2"></i>Jefe Área</th>
                                            <td>{{ $trabajador->jefe->nombre }}</td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-user-tie me-2"></i>Cargo</th>
                                            <td>{{ $trabajador->cargo->Nombre }}</td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-piggy-bank me-2"></i>AFP</th>
                                            <td>{{ $trabajador->afp->Nombre }}</td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-heart-pulse me-2"></i>Salud</th>
                                            <td>{{ $trabajador->salud->Nombre }}</td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-ring me-2"></i>Estado Civil</th>
                                            <td>{{ $trabajador->estadoCivil->Nombre }}</td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-check me-2"></i>Contrato Firmado</th>
                                            <td>{{ $trabajador->ContratoFirmado }}</td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-calendar-check me-2"></i>Fecha Inicio Contrato</th>
                                            <td>{{ $trabajador->fecha_inicio_contrato->translatedFormat('d F, Y') }}</td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-file-signature me-2"></i>Anexo Contrato</th>
                                            <td>{{ $trabajador->AnexoContrato }}</td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-clipboard-list me-2"></i>Situación</th>
                                            <td>{{ $trabajador->situacion->Nombre }}</td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-clock me-2"></i>Turno</th>
                                            <td>{{ $trabajador->turno ? $trabajador->turno->nombre : 'No asignado' }}</td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-utensils me-2"></i>Casino</th>
                                            <td>{{ $trabajador->Casino }}</td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-child me-2"></i>Hijos</th>
                                            <td>{{ $trabajador->hijos->count() }} hijo(s)</td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-calendar-days me-2"></i>Sistema de Trabajo</th>
                                            <td>{{ $trabajador->sistemaTrabajo ? $trabajador->sistemaTrabajo->nombre : 'No asignado' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div> <!-- Fin accordion-item -->
                </div> <!-- Fin accordion -->

            </div> <!-- Fin card p-4 -->
        </div> <!-- Fin col-md-8 -->
    </div> <!-- Fin row -->
</div> <!-- Fin container -->



@push('scripts')
<script>
    function cargarNotificacionesEmpleado() {
        $.get('{{ url('/notificaciones/empleado') }}', function(data) {
            const contenedor = $('#notificacionesEmpleado');
            contenedor.empty(); // limpiar contenido

            const iconos = {
                'App\\Notifications\\SolicitudActualizada': 'fa-file-signature text-primary',
                'App\\Notifications\\NuevoReclamoAreaNotification': 'fa-box text-warning',
                'App\\Notifications\\ReclamoRespondidoNotification': 'fa-reply text-success',
                'App\\Notifications\\NuevoComentarioReclamoNotification': 'fa-comment-dots text-secondary',
                'App\\Notifications\\ReclamoCerradoNotification': 'fa-lock text-danger'
            };

            if (data.items.length === 0) {
                contenedor.append(`<p class="text-muted text-center">No tienes notificaciones nuevas.</p>`);
                return;
            }

            data.items.forEach(function(n) {
                const icono = iconos[n.tipo] || 'fa-info-circle text-muted';
                const tarjeta = `
                    <div class="list-group-item d-flex justify-content-between align-items-center shadow-sm p-3 mb-3 bg-light rounded">
                        <div class="d-flex align-items-center">
                            <i class="fa-solid fa-lg me-3 ${icono}"></i>
                            <span>${n.mensaje}</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <a href="{{ url('notifications/mark-as-read') }}/${n.id}" class="btn btn-sm btn-outline-secondary me-2">Ver</a>


                        </div>
                    </div>
                `;
                contenedor.append(tarjeta);
            });
        });
    }

    $(document).ready(function() {
        cargarNotificacionesEmpleado(); // primera carga
        setInterval(cargarNotificacionesEmpleado, 9000); // cada 30 segundos
    });
</script>
@endpush







@endsection
