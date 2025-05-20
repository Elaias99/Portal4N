<div class="container" wire:poll.2s>
    <h3 class="mb-4">Reclamos relacionados con tu área: {{ $trabajador->area->nombre }}</h3>


    @if ($reclamos->isEmpty())
        <div class="alert alert-secondary">
            No hay reclamos registrados para esta área.
        </div>
    @else
        <div class="list-group">

            @foreach ($reclamos as $reclamo)
                <div class="card mb-4 shadow-sm">

                    <div class="card-body">
                        {{-- Encabezado --}}
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h5 class="fw-bold text-primary mb-1">
                                    @if ($reclamo->bulto && $reclamo->tipo_solicitud === 'consulta')
                                        📦 Bulto: {{ $reclamo->bulto->codigo_bulto }}
                                        <span class="badge bg-info text-white">Consulta</span>
                                    @elseif ($reclamo->bulto)
                                        📦 Bulto: {{ $reclamo->bulto->codigo_bulto }}
                                    @elseif ($reclamo->tipo_solicitud === 'consulta')
                                        📋 Consulta #{{ $reclamo->id }}
                                    @else
                                        📄 Reclamo sin bulto #{{ $reclamo->id }}
                                    @endif
                                </h5>


                                
                                <small class="text-muted">Creado por <strong>{{ $reclamo->trabajador->Nombre }} {{ $reclamo->trabajador->ApellidoPaterno }}</strong> el {{ $reclamo->created_at->format('d-m-Y H:i') }}</small>
                            </div>
                            <div>
                                <small class="text-muted">ID #{{ $reclamo->id }}</small>
                                <span class="badge {{ $reclamo->estado === 'cerrado' ? 'bg-danger' : 'bg-warning text-dark' }}">
                                    {{ ucfirst($reclamo->estado) }}
                                </span>
                            </div>
                        </div>

                        {{-- Descripción del reclamo --}}
                        <div class="mb-3">
                            <p class="mb-1"><strong>Descripción del Reclamo:</strong><br> {{ $reclamo->descripcion }}</p>
                        </div>


                        @if ($reclamo->foto)
                            <div class="mt-2">
                                <strong>Foto Adjunta:</strong><br>
                                <img src="{{ url($reclamo->foto) }}" alt="Foto del reclamo" class="img-fluid rounded shadow-sm" style="max-width: 300px;">
                            </div>
                        @endif


                        {{-- Área asignada --}}
                        <p class="mb-3">
                            <strong>Área asignada:</strong> {{ $reclamo->area->nombre ?? '—' }}
                        </p>

                        {{-- Datos del bulto --}}
                        <div class="bg-light rounded p-3 mb-3">
                            <h6 class="fw-bold mb-2"><i class="fas fa-truck me-1"></i> Datos del Bulto</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Descripción:</strong> {{ $reclamo->bulto->descripcion_bulto ?? '—' }}</p>
                                    <p class="mb-1"><strong>Atención a:</strong> {{ $reclamo->bulto->atencion ?? '—' }}</p>
                                    <p class="mb-1"><strong>Dirección:</strong> {{ $reclamo->bulto->direccion ?? '—' }}</p>
                                    <p class="mb-1"><strong>Comuna:</strong> {{ $reclamo->bulto->comuna->Nombre ?? '—' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Razón Social:</strong> {{ $reclamo->bulto->razon_social ?? '—' }}</p>
                                    <p class="mb-1"><strong>Campaña:</strong> {{ $reclamo->bulto->nombre_campana ?? '—' }}</p>
                                    <p class="mb-1"><strong>Ubicación Actual:</strong> {{ $reclamo->bulto->ubicacion ?? '—' }}</p>
                                </div>
                            </div>
                        </div>

                        {{-- Historial de comentarios --}}
                        <h6 class="fw-bold mt-4">🗨️ Historial de comentarios:</h6>
                        @forelse ($reclamo->comentarios as $comentario)
                            <div class="border rounded p-2 mb-2 {{ str_starts_with($comentario->comentario, '🛑') || str_starts_with($comentario->comentario, '🔁') ? 'bg-warning-subtle' : 'bg-light' }}">
                                <strong>{{ $comentario->autor->name }}</strong>
                                <small class="text-muted">{{ $comentario->created_at->format('d-m-Y H:i') }}</small>
                                <p class="mb-0">{{ $comentario->comentario }}</p>
                            </div>
                        @empty
                            <p class="text-muted">Aún no hay comentarios en este reclamo.</p>
                        @endforelse

                        {{-- Comentario nuevo (si está permitido) --}}
                        @if ($reclamo->estado !== 'cerrado')
                            <form action="{{ route('reclamos.comentar', $reclamo->id) }}" method="POST" class="mt-3">
                                @csrf
                                <div class="mb-2">
                                    <label for="comentario_{{ $reclamo->id }}" class="form-label">Agregar comentario:</label>
                                    <textarea name="comentario" id="comentario_{{ $reclamo->id }}" class="form-control" rows="2" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-sm btn-primary">Enviar Comentario</button>
                            </form>
                        @else
                            <div class="alert alert-danger mt-3 mb-0 p-2">
                                🛑 Este reclamo ha sido cerrado. No se pueden agregar más comentarios.
                            </div>
                        @endif
                    </div>



                    
                </div>
            @endforeach

        </div>
    @endif
</div>


