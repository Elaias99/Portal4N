{{-- @extends('layouts.app')

@section('content')

<script>

    .border-success {
        border: 2px solid #28a745 !important; /* Verde */
    }

    .border-danger {
        border: 2px solid #dc3545 !important; /* Rojo */
    }

    .border-secondary {
        border: 2px solid #6c757d !important; /* Gris */
    }

</script>

<div class="container">
    <h1 class="text-center mb-4">Solicitudes de Modificación</h1>

    <!-- Filtros por estado -->
    <form action="{{ route('solicitudes.index') }}" method="GET" class="mb-3">
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

    <div class="row">
        @foreach($solicitudes as $solicitud)
            @if($solicitud->campo != 'Vacaciones') 
            <div class="col-md-3">
                <!-- Aplicar la clase condicional según el estado -->
                <div class="card mb-3 shadow-sm position-relative @if($solicitud->estado === 'aprobado') border-success 
                                @elseif($solicitud->estado === 'rechazado') border-danger 
                                @else border-secondary @endif" 
                    style="border-radius: 10px;">
                    <div class="card-body p-3 text-center">
                        <h5 class="card-title mb-2">{{ $solicitud->trabajador->Nombre }} {{ $solicitud->trabajador->ApellidoPaterno }}</h5>
    
                        <!-- Información del campo solicitado y descripción -->
                        <p><strong>Campo Solicitado:</strong> {{ ucfirst($solicitud->campo) }}</p>
                        <p><strong>Descripción:</strong> {{ Str::limit($solicitud->descripcion, 40) }}</p>
    
                        <!-- Estado con clase de estilo personalizada -->
                        <p><strong>Estado:</strong> <span class="badge-estado">{{ ucfirst($solicitud->estado) }}</span></p>
    
                        <!-- Archivo adjunto con un icono simple -->
                        @if($solicitud->archivo)
                        <p><strong>Archivo:</strong> 
                            <a href="{{ route('solicitudes.descargar', $solicitud->id) }}" class="text-secondary">
                                <i class="fa-solid fa-file-arrow-down"></i> Descargar
                            </a>
                        </p>
                        @endif
    
                        <!-- Formularios de aprobar y rechazar con comentario -->
                        <div class="d-flex flex-column gap-2 mt-3">
                            <!-- Formulario de aprobación -->
                            <form action="{{ route('solicitudes.approve', $solicitud->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                
                                <!-- Campo de comentario solo es requerido si la solicitud está en estado pendiente -->
                                @if($solicitud->estado === 'pendiente')
                                    <div class="form-group">
                                        <label for="comentario_admin">Comentario del Administrador</label>
                                        <textarea name="comentario_admin" id="comentario_admin" class="form-control" rows="2" required></textarea>
                                    </div>
                                @endif
                                
                                <!-- Campo para adjuntar archivo (siempre disponible) -->
                                <div class="form-group">
                                    <label for="archivo_admin">Adjuntar archivo (opcional):</label>
                                    <input type="file" class="form-control" name="archivo_admin" id="archivo_admin" @if($solicitud->estado === 'aprobado') required @endif>
                                </div>
                            
                                <button type="submit" class="btn btn-sm btn-outline-success mt-2">
                                    {{ $solicitud->estado === 'pendiente' ? 'Aprobar' : 'Subir Respaldo' }}
                                </button>
                            </form>

                            <!-- Formulario de rechazo (si está en pendiente) -->
                            @if($solicitud->estado === 'pendiente')
                                <form action="{{ route('solicitudes.reject', $solicitud->id) }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <!-- Campo de comentario del administrador -->
                                    <div class="form-group">
                                        <label for="comentario_admin">Comentario del Administrador</label>
                                        <textarea name="comentario_admin" id="comentario_admin" class="form-control" rows="2" required></textarea>
                                    </div>
                                
                                    <!-- Campo para adjuntar archivo -->
                                    <div class="form-group">
                                        <label for="archivo_admin">Adjuntar archivo (opcional):</label>
                                        <input type="file" class="form-control" name="archivo_admin" id="archivo_admin">
                                    </div>
                                
                                    <button type="submit" class="btn btn-sm btn-outline-danger mt-2">Rechazar</button>
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
    <div class="text-center mt-4">
        <a href="{{ url('/empleados') }}" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Regresar al listado de empleados
        </a>
    </div>
</div>
@endsection
 --}}
 @extends('layouts.app')

 @section('content')
 
 <script>
 
     .border-success {
         border: 2px solid #28a745 !important; /* Verde */
     }
 
     .border-danger {
         border: 2px solid #dc3545 !important; /* Rojo */
     }
 
     .border-secondary {
         border: 2px solid #6c757d !important; /* Gris */
     }
 
 </script>
 
 <div class="container">
     <h1 class="text-center mb-4">Solicitudes de Modificación</h1>
 
     <!-- Filtros por estado -->
     <form action="{{ route('solicitudes.index') }}" method="GET" class="mb-3">
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
 
     <div class="row">
         @foreach($solicitudes as $solicitud)
             @if($solicitud->campo != 'Vacaciones') 
             <div class="col-md-3">
                 <!-- Aplicar la clase condicional según el estado -->
                 <div class="card mb-3 shadow-sm position-relative @if($solicitud->estado === 'aprobado') border-success 
                                 @elseif($solicitud->estado === 'rechazado') border-danger 
                                 @else border-secondary @endif" 
                     style="border-radius: 10px;">
                     <div class="card-body p-3 text-center">
                         <h5 class="card-title mb-2">{{ $solicitud->trabajador->Nombre }} {{ $solicitud->trabajador->ApellidoPaterno }}</h5>
     
                         <!-- Información del campo solicitado y descripción -->
                         <p><strong>Campo Solicitado:</strong> {{ ucfirst($solicitud->campo) }}</p>
                         <p><strong>Descripción:</strong> {{ Str::limit($solicitud->descripcion, 40) }}</p>
     
                         <!-- Estado con clase de estilo personalizada -->
                         <p><strong>Estado:</strong> <span class="badge-estado">{{ ucfirst($solicitud->estado) }}</span></p>
     
                         <!-- Archivo adjunto con un icono simple -->
                         @if($solicitud->archivo)
                         <p><strong>Archivo:</strong> 
                             <a href="{{ route('solicitudes.descargar', $solicitud->id) }}" class="text-secondary">
                                 <i class="fa-solid fa-file-arrow-down"></i> Descargar
                             </a>
                         </p>
                         @endif
     
                         <!-- Formularios de aprobar y rechazar con comentario -->
                         <div class="d-flex flex-column gap-2 mt-3">
                             <!-- Solo mostrar el formulario si no hay archivo de respaldo subido -->
                             @if(is_null($solicitud->archivo_admin))
                                 <!-- Formulario de aprobación -->
                                 <form action="{{ route('solicitudes.approve', $solicitud->id) }}" method="POST" enctype="multipart/form-data">
                                     @csrf
                                     
                                     <!-- Campo de comentario solo es requerido si la solicitud está en estado pendiente -->
                                     @if($solicitud->estado === 'pendiente')
                                         <div class="form-group">
                                             <label for="comentario_admin">Comentario del Administrador</label>
                                             <textarea name="comentario_admin" id="comentario_admin" class="form-control" rows="2" required></textarea>
                                         </div>
                                     @endif
                                     
                                     <!-- Campo para adjuntar archivo (siempre disponible si no hay archivo_admin) -->
                                     <div class="form-group">
                                         <label for="archivo_admin">Adjuntar archivo (opcional):</label>
                                         <input type="file" class="form-control" name="archivo_admin" id="archivo_admin" @if($solicitud->estado === 'aprobado') required @endif>
                                     </div>
                                 
                                     <button type="submit" class="btn btn-sm btn-outline-success mt-2">
                                         {{ $solicitud->estado === 'pendiente' ? 'Aprobar' : 'Subir Respaldo' }}
                                     </button>
                                 </form>
                             @else
                                 <p class="text-muted">El archivo de respaldo ya fue subido.</p>
                             @endif
 
                             <!-- Formulario de rechazo (si está en pendiente) -->
                             @if($solicitud->estado === 'pendiente')
                                 <form action="{{ route('solicitudes.reject', $solicitud->id) }}" method="POST" enctype="multipart/form-data">
                                     @csrf
                                     <!-- Campo de comentario del administrador -->
                                     <div class="form-group">
                                         <label for="comentario_admin">Comentario del Administrador</label>
                                         <textarea name="comentario_admin" id="comentario_admin" class="form-control" rows="2" required></textarea>
                                     </div>
                                 
                                     <!-- Campo para adjuntar archivo -->
                                     <div class="form-group">
                                         <label for="archivo_admin">Adjuntar archivo (opcional):</label>
                                         <input type="file" class="form-control" name="archivo_admin" id="archivo_admin">
                                     </div>
                                 
                                     <button type="submit" class="btn btn-sm btn-outline-danger mt-2">Rechazar</button>
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
     <div class="text-center mt-4">
         <a href="{{ url('/empleados') }}" class="btn btn-primary">
             <i class="fas fa-arrow-left"></i> Regresar al listado de empleados
         </a>
     </div>
 </div>
 @endsection
 