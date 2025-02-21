@extends('layouts.app')

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

@if (Auth::user()->unreadNotifications->count() > 0)
    <div class="container mb-4">
        <h4 class="mb-3">Notificaciones</h4>
        <div class="list-group">
            @foreach (Auth::user()->unreadNotifications as $notification)
                <div class="list-group-item d-flex justify-content-between align-items-center shadow-sm p-3 mb-3 bg-light rounded">
                    <!-- Icono y mensaje de notificación -->
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle fa-lg text-info mr-3"></i>
                        <span>{{ $notification->data['mensaje'] }}</span>
                    </div>

                    <!-- Acciones -->
                    <div class="d-flex align-items-center">
                        <!-- Link a la lista de solicitudes del empleado -->
                        <a href="{{ route('perfiles.solicitudes') }}" class="btn btn-sm btn-outline-primary me-2">Ver solicitudes</a>
                        
                        <!-- Enlace para marcar como leída -->
                        <a href="{{ route('notifications.markAsRead', $notification->id) }}" class="btn btn-sm btn-primary">Marcar como leída</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@else
    <div class="container">
        <p class="alert alert-info">No tienes notificaciones nuevas.</p>
    </div>
@endif




<div class="container">
    <h1 class="mb-4">Perfil del Empleado</h1>

    <!-- Contenedor de perfil de empleado -->
    <div class="row">
        <!-- Columna izquierda: Foto del empleado -->
        <div class="col-md-4">
            <div class="card p-3 mb-4">
                @if($trabajador->Foto)
                    <img src="{{ url('storage/' . $trabajador->Foto) }}" class="img-fluid rounded-circle mb-3" alt="Foto de {{ $trabajador->Nombre }}">
                @else
                    <img src="{{ url('images/default-avatar.png') }}" class="img-fluid rounded-circle mb-3" alt="Imagen predeterminada">
                @endif
                <a href="{{ route('perfiles.editar', $trabajador->id) }}" class="btn btn-outline-primary btn-block">Editar Perfil</a>
                <br>
                <a href="{{ route('perfiles.cambiar_contraseña', $trabajador->id) }}" class="btn btn-outline-primary btn-block">Editar Contraseña</a>
                <a href="{{ route('perfiles.solicitudes') }}" class="btn btn-primary mt-4">Ver solicitudes</a>
                <a href="{{ route('solicitudes.create') }}" class="btn btn-primary mt-4">Solicitar modificación</a>
                <br>
                <!-- Botón para solicitar vacaciones -->
                <a href="{{ route('vacaciones.create') }}" class="btn btn-primary">
                    Solicitar Días
                </a>

            </div>
        </div>

        <!-- Columna derecha: Información del empleado -->
        <div class="col-md-8">
            <div class="card p-4">
                <h4 class="mb-3"><i class="fas fa-user"></i> Información Personal</h4>
                <table class="table table-borderless">
                    <tbody>
                        <tr>
                            <th><i class="fas fa-id-card"></i> RUT</th>
                            <td>{{ $trabajador->Rut }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-user"></i> Nombre Completo</th>
                            <td>{{ $trabajador->Nombre }} {{ $trabajador->SegundoNombre }} {{ $trabajador->ApellidoPaterno }} {{ $trabajador->ApellidoMaterno }}</td>
                        </tr>

                        <tr>
                            <th><i class="fas fa-calendar-alt"></i> Fecha de Nacimiento</th>
                            <td>{{ $trabajador->FechaNacimiento->translatedFormat('d F, Y') }}</td>
                        </tr>
                        

                        <tr>
                            <th><i class="fas fa-birthday-cake"></i> Edad</th>
                            <td>{{ $trabajador->edad }} años</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-phone"></i> Número Celular</th>
                            <td>{{ $trabajador->numero_celular }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-user-friends"></i> Contacto de Emergencia</th>
                            <td>{{ $trabajador->nombre_emergencia }} | Número: {{ $trabajador->contacto_emergencia }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-map-marker-alt"></i> Dirección</th>
                            <td>{{ $trabajador->calle }} | {{ $trabajador->comuna->Nombre }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-envelope"></i> Correo Personal</th>
                            <td>{{ $trabajador->CorreoPersonal }}</td>
                        </tr>

                        <tr>
                            <th><i class="fas fa-envelope"></i> Banco</th>
                            <td>{{ $trabajador->banco }}</td>
                        </tr>

                        <tr>
                            <th><i class="fas fa-envelope"></i>Tipo de Cuenta</th>
                            <td>{{ $trabajador->tipo_cuenta }}</td>
                        </tr>



                    </tbody>
                </table>

                <h4 class="mb-3 mt-4"><i class="fas fa-briefcase"></i> Información de Empleo</h4>
                <table class="table table-borderless">
                    <tbody>
                        <tr>
                            <th><i class="fas fa-building"></i> Empresa</th>
                            <td>{{ $trabajador->empresa->Nombre }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-envelope"></i> Correo Corporativo</th>
                            <td>{{ $trabajador->user->email }}</td>
                        </tr>




                        <tr>
                            <th><i class="fas fa-calendar-check"></i> Fecha de Contratación</th>
                           
                            <td>{{ $trabajador->fecha_inicio_trabajo->translatedFormat('d F, Y') }}</td>
                        </tr>




                        <tr>
                            <th><i class="fas fa-user-tie"></i>Jefe Área</th>
                            <td>{{ $trabajador->jefe->nombre }}</td>
                          </tr>

                        <tr>
                            <th><i class="fas fa-user-tie"></i> Cargo</th>
                            <td>{{ $trabajador->cargo->Nombre }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-piggy-bank"></i> AFP</th>
                            <td>{{ $trabajador->afp->Nombre }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-heartbeat"></i> Salud</th>
                            <td>{{ $trabajador->salud->Nombre }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-ring"></i> Estado Civil</th>
                            <td>{{ $trabajador->estadoCivil->Nombre }}</td>
                        </tr>

                        <tr>
                            <th><i class="fas fa-check-circle"></i> Contrato Firmado</th>
                            <td>{{ $trabajador->ContratoFirmado }}</td>
                        </tr>

                        <tr>
                            <th><i class="fas fa-calendar-check"></i> Fecha Inicio Contrato</th>
                            <td>{{ $trabajador->fecha_inicio_contrato->translatedFormat('d F, Y') }}</td>
                        </tr>


                        <tr>
                            <th><i class="fas fa-file-signature"></i> Anexo Contrato</th>
                            <td>{{ $trabajador->AnexoContrato }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-clipboard-list"></i> Situación</th>
                            <td>{{ $trabajador->situacion->Nombre }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-clock"></i> Turno</th>
                            <td>{{ $trabajador->turno ? $trabajador->turno->nombre : 'No asignado' }}</td>
                        </tr>
                        <tr>
                            <th> <i class="fa-solid fa-utensils"></i> Casino</th>
                            <td>{{ $trabajador->Casino }}</td>
                        </tr>
                        
                        <tr>
                            <th> <i class="fa-solid fa-child"></i>  Hijos</th>
                            <td>{{ $trabajador->hijos->count() }} hijo(s)</td>
                        </tr>


                        <tr>
                            <th><i class="fas fa-calendar-alt"></i> Sistema de Trabajo</th>
                            <td>{{ $trabajador->sistemaTrabajo ? $trabajador->sistemaTrabajo->nombre : 'No asignado' }}</td>
                        </tr>
                    </tbody>
                </table>


            </div>
        </div>
    </div>
</div>

@endsection
