@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Archivos de Respaldo Adjuntados por el Administrador</h1>

    <div class="row">
        @foreach($empleadosConRespaldo as $trabajador_id => $respaldo)
        <div class="col-md-3">
            <div class="card mb-3 shadow-sm position-relative" style="border-radius: 10px;">
                <div class="card-body p-2">
                    <div class="text-center mb-2">
                        <h5 class="card-title mb-1">
                            @if(isset($respaldo['vacaciones']) && $respaldo['vacaciones']->isNotEmpty())
                                {{ $respaldo['vacaciones']->first()->trabajador->Nombre }} 
                                {{ $respaldo['vacaciones']->first()->trabajador->ApellidoPaterno }}
                            @elseif(isset($respaldo['modificaciones']) && $respaldo['modificaciones']->isNotEmpty())
                                {{ $respaldo['modificaciones']->first()->trabajador->Nombre }} 
                                {{ $respaldo['modificaciones']->first()->trabajador->ApellidoPaterno }}
                            @endif
                        </h5>
                        <small class="text-muted">
                            @if(isset($respaldo['vacaciones']) && $respaldo['vacaciones']->isNotEmpty())
                                {{ $respaldo['vacaciones']->first()->trabajador->cargo->Nombre }}
                            @elseif(isset($respaldo['modificaciones']) && $respaldo['modificaciones']->isNotEmpty())
                                {{ $respaldo['modificaciones']->first()->trabajador->cargo->Nombre }}
                            @endif
                        </small>
                    </div>

                    <div class="text-center mb-2">
                        @if(isset($respaldo['vacaciones']) && $respaldo['vacaciones']->isNotEmpty() && $respaldo['vacaciones']->first()->trabajador->empresa && $respaldo['vacaciones']->first()->trabajador->empresa->logo)
                            <img src="{{ asset('storage/' . $respaldo['vacaciones']->first()->trabajador->empresa->logo) }}" alt="Logo de {{ $respaldo['vacaciones']->first()->trabajador->empresa->Nombre }}" style="max-height: 50px;">
                        @elseif(isset($respaldo['modificaciones']) && $respaldo['modificaciones']->isNotEmpty() && $respaldo['modificaciones']->first()->trabajador->empresa && $respaldo['modificaciones']->first()->trabajador->empresa->logo)
                            <img src="{{ asset('storage/' . $respaldo['modificaciones']->first()->trabajador->empresa->logo) }}" alt="Logo de {{ $respaldo['modificaciones']->first()->trabajador->empresa->Nombre }}" style="max-height: 50px;">
                        @else
                            <p class="text-muted">No hay logo disponible</p>
                        @endif
                    </div>

                    <!-- Botón para desplegar opciones de archivos de respaldo -->
                    <div class="text-center mt-3">
                        <div class="dropdown">
                            <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton{{ $trabajador_id }}" data-toggle="dropdown" aria-expanded="false">
                                Ver Archivos de Respaldo
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton{{ $trabajador_id }}">
                                @if(isset($respaldo['vacaciones']) && $respaldo['vacaciones']->isNotEmpty())
                                    <li>
                                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#modalVacaciones{{ $trabajador_id }}">Archivos de Respaldo de Vacaciones</a>
                                    </li>
                                @endif
                                @if(isset($respaldo['modificaciones']) && $respaldo['modificaciones']->isNotEmpty())
                                    <li>
                                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#modalModificaciones{{ $trabajador_id }}">Archivos de Respaldo de Modificaciones</a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para Archivos de Respaldo de Vacaciones -->
        <div class="modal fade" id="modalVacaciones{{ $trabajador_id }}" tabindex="-1" role="dialog" aria-labelledby="modalVacacionesLabel{{ $trabajador_id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalVacacionesLabel{{ $trabajador_id }}">Archivos de Respaldo de Vacaciones</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Fecha de Solicitud</th>
                                    <th>Hora de Envío</th> 
                                    <th>Fecha Inicio</th>
                                    <th>Fecha Fin</th>
                                    <th>Tipo de Día</th>
                                    <th>Estado</th>
                                    <th>Hora de Respuesta</th>
                                    <th>Comentario del Administrador</th>
                                    <th>Archivo de Respaldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @isset($respaldo['vacaciones'])
                                    @foreach($respaldo['vacaciones'] as $vacacion)
                                        <tr>
                                            <td>{{ $vacacion->solicitud->created_at->format('d-m-Y') }}</td>
                                            <td>{{ $vacacion->solicitud->created_at->format('H:i:s') }}</td>
                                            <td>{{ $vacacion->fecha_inicio->format('Y-m-d') }}</td>
                                            <td>{{ $vacacion->fecha_fin->format('Y-m-d') }}</td>
                                            <td>{{ $vacacion->solicitud->tipo_dia }}</td>
                                            <td>{{ ucfirst($vacacion->solicitud->estado) }}</td>
                                            <td>{{ $vacacion->solicitud->updated_at->format('H:i:s') }}</td>
                                            <td>{{ $vacacion->solicitud->comentario_admin ?? 'N/A' }}</td>
                                            <td>
                                                @if($vacacion->archivo_respuesta_admin)
                                                    <a href="{{ route('vacaciones.descargarArchivoRespuestaAdmin', $vacacion->id) }}" class="btn btn-outline-primary btn-sm" target="_blank">
                                                        Descargar Respaldo
                                                    </a>
                                                @else
                                                    No disponible
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="7">No hay archivos de respaldo de vacaciones disponibles.</td>
                                    </tr>
                                @endisset
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para Archivos de Respaldo de Modificaciones -->
        <div class="modal fade" id="modalModificaciones{{ $trabajador_id }}" tabindex="-1" role="dialog" aria-labelledby="modalModificacionesLabel{{ $trabajador_id }}" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalModificacionesLabel{{ $trabajador_id }}">Archivos de Respaldo de Modificaciones</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Fecha de Solicitud</th>

                                    <th>Hora de Envío</th>
                                    <th>Descripción</th>

                                    <th>Estado</th>

                                    <th>Hora de Respuesta</th>

                                    <th>Comentario del Administrador</th>
                                    <th>Archivo de Respaldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @isset($respaldo['modificaciones'])
                                    @foreach($respaldo['modificaciones'] as $modificacion)
                                        <tr>
                                            <td>{{ $modificacion->created_at->format('d-m-Y') }}</td>

                                            <td>{{ $modificacion->created_at->format('H:i:s') }}</td>

                                            <td>{{ $modificacion->descripcion ?? 'Modificación de datos' }}</td>
                                            <td>{{ ucfirst($modificacion->estado) }}</td>
                                            <td>{{ $modificacion->updated_at->format('H:i:s') }}</td>
                                            <td>{{ $modificacion->comentario_admin ?? 'N/A' }}</td>
                                            <td>
                                                @if($modificacion->archivo_admin)
                                                    <a href="{{ route('solicitudes.descargar-archivo-admin', $modificacion->id) }}" class="btn btn-outline-primary btn-sm" target="_blank">
                                                        Descargar Respaldo
                                                    </a>
                                                @else
                                                    No disponible
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="5">No hay archivos de respaldo de modificaciones disponibles.</td>
                                    </tr>
                                @endisset
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <a href="{{ url('/empleados') }}" class="btn btn-primary mt-3">
        <i class="fas fa-arrow-left"></i> Regresar al listado de empleados
    </a>
</div>
@endsection
