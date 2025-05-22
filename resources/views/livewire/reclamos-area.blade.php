<div class="row" wire:poll.2s>
    {{-- PANEL IZQUIERDO --}}
    {{-- PANEL IZQUIERDO --}}
    <div class="col-md-2">
        <div class="card shadow-sm border-0">
            <div class="card-body p-3">
                <h6 class="text-muted mb-3">🧭 Panel de Reclamos</h6>

                {{-- Filtros activos --}}
                {{-- Filtros activos --}}
                <div class="mb-3">
                    <label class="text-muted d-block mb-1">🔍 Filtros</label>

                    <select wire:model.defer="filtroEstado" class="form-control form-control-sm mb-2">
                        <option value="">Estado: Todos</option>
                        <option value="pendiente">Pendientes</option>
                        <option value="cerrado">Cerrados</option>
                    </select>

                    <select wire:model.defer="filtroImportancia" class="form-control form-control-sm mb-2">
                        <option value="">Importancia: Todas</option>
                        <option value="baja">Baja</option>
                        <option value="media">Media</option>
                        <option value="alta">Alta</option>
                        <option value="urgente">Urgente</option>
                    </select>

                    <button wire:click="aplicarFiltrado" class="btn btn-sm btn-primary mt-2 w-100">
                        Aplicar Filtrado
                    </button>

                    <button wire:click="resetFiltros" class="btn btn-sm btn-outline-secondary mt-2 w-100">
                        Limpiar Filtros
                    </button>
                </div>


                {{-- Resumen --}}
                <div>
                    <small class="text-muted d-block mb-1">📊 Resumen</small>
                    <ul class="list-unstyled small mb-0">
                        <li>🟡 Pendientes: <strong>{{ $reclamos->where('estado', 'pendiente')->count() }}</strong></li>
                        <li>🔴 Cerrados: <strong>{{ $reclamos->where('estado', 'cerrado')->count() }}</strong></li>
                        <li>📋 Consultas: <strong>{{ $reclamos->where('tipo_solicitud', 'consulta')->count() }}</strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>



    {{-- PANEL DERECHO: FORO --}}
    <div class="col-md-9">
        <h4 class="mb-4">💬 Reclamos relacionados con tu área: {{ $trabajador->area->nombre }}</h4>

        @if ($reclamos->isEmpty())
            <div class="alert alert-secondary">
                No hay reclamos registrados para esta área.
            </div>
        @else
            @foreach ($reclamos as $reclamo)


                <div class="card mb-4 shadow-sm">
                    {{-- Cabecera --}}
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 font-weight-bold text-primary">
                                📦 Bulto: {{ $reclamo->bulto->codigo_bulto ?? '—' }} 
                                <small class="text-muted">#{{ $reclamo->id }}</small>
                            </h6>
                            <small class="text-muted">
                                Publicado por <strong>{{ $reclamo->trabajador->Nombre }} {{ $reclamo->trabajador->ApellidoPaterno }}</strong> 
                                el {{ $reclamo->created_at->format('d-m-Y H:i') }}
                            </small>
                            <div class="text-muted small">
                                Área que generó: <strong>{{ $reclamo->trabajador->area->nombre ?? '—' }}</strong>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge {{ $reclamo->estado === 'cerrado' ? 'badge-light text-danger' : 'badge-warning text-dark' }}">
                                {{ ucfirst($reclamo->estado) }}
                            </span>
                            <br>
                            <span class="badge 
                                @switch($reclamo->importancia)
                                    @case('urgente') badge-light text-danger @break
                                    @case('alta') badge-warning text-dark @break
                                    @case('media') badge-info text-dark @break
                                    @default badge-secondary text-dark
                                @endswitch
                            ">
                                {{ ucfirst($reclamo->importancia) }}
                            </span>
                        </div>
                    </div>

                    {{-- Cuerpo --}}
                    <div class="card-body bg-white">
                        {{-- Detalles del Bulto --}}
                        @if ($reclamo->bulto)
                            <div class="mb-3">
                                <h6 class="text-secondary"><i class="fa-solid fa-box me-1"></i> Detalles del Bulto</h6>
                                <div class="small text-muted">
                                    <p><strong>Dirección:</strong> {{ $reclamo->bulto->direccion ?? '—' }}</p>
                                    <p><strong>Comuna:</strong> {{ $reclamo->bulto->comuna->Nombre ?? '—' }}</p>
                                    <p><strong>Razón Social:</strong> {{ $reclamo->bulto->razon_social ?? '—' }}</p>
                                </div>
                            </div>
                        @endif

                        {{-- Imagen principal --}}
                        @if ($reclamo->foto)
                            <div class="mb-3">
                                <img src="{{ url($reclamo->foto) }}" class="img-thumbnail" style="max-width: 150px;">
                            </div>
                        @endif

                        {{-- Descripción --}}
                        <div class="mb-4">
                            <h6 class="text-primary"><i class="fa-solid fa-info-circle me-1"></i> Descripción del Problema</h6>
                            <p class="mb-0">{{ $reclamo->descripcion }}</p>
                        </div>

                        {{-- Conversación --}}
                        <div class="p-3 bg-light rounded border">
                            <h6 class="text-primary"><i class="fa-solid fa-comments me-1"></i> Conversación</h6>
                            @forelse ($reclamo->comentarios as $comentario)
                                <div class="media mb-3 pb-2 border-bottom">
                                    <div class="media-body">
                                        <h6 class="mt-0 mb-1">
                                            {{ $comentario->autor->name }}
                                            <small class="text-muted">— {{ $comentario->created_at->format('d-m-Y H:i') }}</small>
                                        </h6>
                                        <p class="mb-1">{{ $comentario->comentario }}</p>
                                        @if ($comentario->foto_comentario)
                                            <img src="{{ url($comentario->foto_comentario) }}" class="img-thumbnail mt-2" style="max-width: 200px;">
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted">Aún no hay respuestas en este hilo.</p>
                            @endforelse
                        </div>

                        {{-- Formulario de respuesta (si no está cerrado) --}}
                        @if ($reclamo->estado !== 'cerrado')
                            <form action="{{ route('reclamos.comentar', $reclamo->id) }}" method="POST" enctype="multipart/form-data" class="mt-3">
                                @csrf
                                <div class="form-group mb-2">
                                    <textarea name="comentario" class="form-control" rows="2" placeholder="Escribe tu respuesta..." required></textarea>
                                </div>
                                <div class="form-group mb-2">
                                    <label for="foto_comentario" class="form-label">Adjuntar imagen (opcional):</label>
                                    <input type="file" name="foto_comentario" class="form-control">
                                </div>
                                <button type="submit" class="btn btn-sm btn-primary">Responder</button>
                            </form>
                        @else
                            <div class="alert alert-danger mt-3 p-2">
                                🛑 Este hilo está cerrado. No se pueden agregar más respuestas.
                            </div>
                        @endif
                    </div>
                </div>




            @endforeach
        @endif
    </div>
</div>
