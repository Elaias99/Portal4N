<div class="row" wire:poll.2s>
    {{-- PANEL IZQUIERDO --}}
    <div class="col-md-2">
        <div class="card shadow-sm border-0">
            <div class="card-body p-3">
                <h6 class="text-muted mb-3">🧭 Panel de Reclamos</h6>

                {{-- Accesos --}}
                {{-- Filtros (placeholder futuro) --}}
                <div class="mb-3">
                    <small class="text-muted d-block mb-1">🔍 Filtros (próx.)</small>
                    <small class="text-muted">Búsqueda por trabajador, área o estado.</small>
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
                    {{-- Cabecera tipo foro --}}
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 font-weight-bold text-primary">
                                @if ($reclamo->tipo_solicitud === 'consulta')
                                    📋 Consulta #{{ $reclamo->id }}
                                @elseif ($reclamo->bulto)
                                    📦 Bulto: {{ $reclamo->bulto->codigo_bulto }} 
                                    @if ($reclamo->tipo_solicitud === 'consulta')
                                        <span class="badge badge-info">Consulta</span>
                                    @endif
                                    <small class="text-muted">#{{ $reclamo->id }}</small>
                                @else
                                    📄 Reclamo #{{ $reclamo->id }}
                                @endif
                            </h6>
                            <small class="text-muted">
                                Publicado por <strong>{{ $reclamo->trabajador->Nombre }} {{ $reclamo->trabajador->ApellidoPaterno }}</strong> 
                                el {{ $reclamo->created_at->format('d-m-Y H:i') }}
                            </small>
                        </div>
                        <span class="badge badge-{{ $reclamo->estado === 'cerrado' ? 'danger' : 'warning' }}">
                            {{ ucfirst($reclamo->estado) }}
                        </span>
                    </div>

                    {{-- Cuerpo del post --}}
                    <div class="card-body">
                        <p class="mb-2"><strong>Descripción:</strong> {{ $reclamo->descripcion }}</p>

                        @if ($reclamo->foto)
                            <div class="mb-3">
                                <img src="{{ url($reclamo->foto) }}" class="img-thumbnail" style="max-width: 200px;">
                            </div>
                        @endif

                        @if ($reclamo->bulto)
                            <div class="bg-light rounded p-3 mb-3">
                                <h6 class="mb-2 font-weight-bold">📦 Detalles del Bulto</h6>
                                <p class="mb-1"><strong>Dirección:</strong> {{ $reclamo->bulto->direccion ?? '—' }}</p>
                                <p class="mb-1"><strong>Comuna:</strong> {{ $reclamo->bulto->comuna->Nombre ?? '—' }}</p>
                                <p class="mb-1"><strong>Razón Social:</strong> {{ $reclamo->bulto->razon_social ?? '—' }}</p>
                            </div>
                        @endif

                        {{-- Comentarios (respuestas tipo foro) --}}
                        <h6 class="mb-3">🗨️ Conversación:</h6>
                        @forelse ($reclamo->comentarios as $comentario)
                            <div class="media mb-3">
                                <div class="media-body">
                                    <h6 class="mt-0 mb-1">
                                        {{ $comentario->autor->name }}
                                        <small class="text-muted">— {{ $comentario->created_at->format('d-m-Y H:i') }}</small>
                                    </h6>

                                    {{-- Comentario de texto --}}
                                    <p class="mb-1">{{ $comentario->comentario }}</p>

                                    {{-- Imagen adjunta al comentario, si existe --}}
                                    @if ($comentario->foto_comentario)
                                        <div class="mt-2">
                                            <img src="{{ url($comentario->foto_comentario) }}" class="img-thumbnail" style="max-width: 200px;" alt="Imagen adjunta al comentario">
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-muted">Aún no hay respuestas en este hilo.</p>
                        @endforelse


                        {{-- Responder --}}
                        @if ($reclamo->estado !== 'cerrado')
                            <form action="{{ route('reclamos.comentar', $reclamo->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="form-group">
                                    <textarea name="comentario" class="form-control" rows="2" placeholder="Escribe tu respuesta..." required></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="foto_comentario">Adjuntar imagen (opcional):</label>
                                    <input type="file" name="foto_comentario" class="form-control-file">
                                </div>
                                <button type="submit" class="btn btn-sm btn-primary">Responder</button>
                            </form>

                        @else
                            <div class="alert alert-danger p-2">
                                🛑 Este hilo está cerrado. No se pueden agregar más respuestas.
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
