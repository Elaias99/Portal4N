@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Mis Solicitudes</h1>

    <!-- Sección para solicitudes de modificación -->
    <h2>Modificación</h2>
    @php
    $modificacionCampos = [
        'afp', 
        'cargo', 
        'salario_bruto', 
        'fecha_ingreso', 
        'fecha_inicio_contrato',
        'banco',
        'numero_cuenta',
        'tipo_cuenta',
        'estado_civil',
        'sistema_trabajo',
        'turno',
        'situacion',
        'comuna',
        'contrato_firmado',
        'anexo_contrato'
    ]; // Lista actualizada de campos
@endphp
    @if($solicitudes->whereIn('campo', $modificacionCampos)->isEmpty())
        <p>No tienes solicitudes de modificación.</p>
    @else
        <div class="row">
            @foreach($solicitudes as $solicitud)
                @if(in_array($solicitud->campo, $modificacionCampos)) <!-- Solo mostrar solicitudes de modificación -->
                <div class="col-md-4">
                    <div class="card mb-3 @if($solicitud->estado == 'aprobado') border-success 
                                            @elseif($solicitud->estado == 'rechazado') border-danger 
                                            @else border-warning @endif">
                        <div class="card-body">
                            <h5 class="card-title">Campo Solicitado: {{ ucfirst($solicitud->campo) }}</h5>
                            
                            <!-- Mostrar comentario del administrador en lugar de descripción -->
                            <p><strong>Comentario del Administrador:</strong> {{ Str::limit($solicitud->comentario_admin ?? 'Sin comentario', 50) }}</p>

                            <p><strong>Estado:</strong> 
                                <span class="@if($solicitud->estado == 'aprobado') text-success 
                                            @elseif($solicitud->estado == 'rechazado') text-danger 
                                            @else text-warning @endif">
                                    {{ ucfirst($solicitud->estado) }}
                                </span>
                            </p>

                            <!-- Botón Ver detalles -->
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#solicitudModal{{ $solicitud->id }}">
                                Ver Detalles
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Incluir el modal -->
                @include('partials.solicitud_modal', ['solicitud' => $solicitud])
                @endif
            @endforeach
        </div>
    @endif

    <!-- Sección para solicitudes de días (vacaciones) -->
    <!-- Sección para solicitudes de días (vacaciones) -->
    <h2>Días</h2>
@if($solicitudes->where('campo', 'Vacaciones')->isEmpty())
    <p>No tienes solicitudes de días (vacaciones).</p>
@else
    <div class="row">
        @foreach($solicitudes as $solicitud)
            @if($solicitud->campo == 'Vacaciones')
            <div class="col-md-4">
                <div class="card mb-3 @if($solicitud->estado == 'aprobado') border-success 
                                        @elseif($solicitud->estado == 'rechazado') border-danger 
                                        @else border-warning @endif">
                    <div class="card-body">
                        <h5 class="card-title">Solicitud de {{ 
                            $solicitud->tipo_dia === 'vacaciones' ? 'Vacaciones' : 
                            ($solicitud->tipo_dia === 'administrativo' ? 'Día Administrativo' : 
                            ($solicitud->tipo_dia === 'sin_goce_de_sueldo' ? 'Permiso sin goce de sueldo' : 
                            ($solicitud->tipo_dia === 'permiso_fuerza_mayor' ? 'Permiso fuerza mayor' : 
                            'Licencia Médica'))) 
                        }}</h5>
                        
                        
                        
                        @if($solicitud->vacacion)
                            <p><strong>Fecha Inicio:</strong> {{ $solicitud->vacacion->fecha_inicio->format('Y-m-d') }}</p>
                            <p><strong>Fecha Fin:</strong> {{ $solicitud->vacacion->fecha_fin->format('Y-m-d') }}</p>
                        @else
                            <p>No hay detalles de fechas para esta solicitud.</p>
                        @endif
                        
                        <p><strong>Comentario del Administrador:</strong> {{ Str::limit($solicitud->comentario_admin ?? 'Sin comentario', 50) }}</p>
                        
                        <p><strong>Estado:</strong> 
                            <span class="@if($solicitud->estado == 'aprobado') text-success 
                                        @elseif($solicitud->estado == 'rechazado') text-danger 
                                        @else text-warning @endif">
                                {{ ucfirst($solicitud->estado) }}
                            </span>
                        </p>
                        
                        <!-- Botón Ver detalles -->
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#solicitudModal{{ $solicitud->id }}">
                            Ver Detalles
                        </button>
                        
                        <!-- Enlace para descargar el PDF generado automáticamente, solo si la solicitud está aprobada y el PDF existe -->
                        @if($solicitud->estado === 'aprobado' && $solicitud->vacacion && $solicitud->vacacion->archivo_admin)
                            <a href="{{ route('vacaciones.descargarArchivoAdmin', $solicitud->vacacion->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fa-solid fa-file-pdf"></i> Descargar PDF de solicitud de días
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Incluir el modal -->
            @include('partials.solicitud_modal', ['solicitud' => $solicitud])
            @endif
        @endforeach
    </div>
@endif



    <!-- Botón para volver -->
    <a href="{{ route('empleados.perfil') }}" class="btn btn-primary mt-4">Volver</a>
</div>
@endsection
