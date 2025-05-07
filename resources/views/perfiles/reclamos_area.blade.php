@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-4">Reclamos dirigidos a tu área: {{ $trabajador->area->nombre }}</h3>

    @if ($reclamosArea->isEmpty())
        <div class="alert alert-secondary">
            No hay reclamos registrados para esta área.
        </div>
    @else
        <div class="list-group">


            @foreach ($reclamosArea as $reclamo)
                <div class="card mb-4 shadow-sm">
                    <div class="card-body">

                        {{-- Datos del reclamo --}}
                        <h5 class="card-title">📦 Bulto: {{ $reclamo->bulto->codigo_bulto ?? 'Sin código' }}</h5>
                        <p class="card-text mb-2">
                            <strong>Descripción:</strong> {{ $reclamo->descripcion }}<br>
                            <strong>Descripción del Bulto:</strong> {{ $reclamo->bulto->descripcion_bulto ?? 'No disponible' }}<br>
                            <strong>Atención a:</strong> {{ $reclamo->bulto->atencion ?? 'No disponible' }}<br>
                            <strong>Dirección:</strong> {{ $reclamo->bulto->direccion ?? 'No disponible' }}<br>
                            <strong>Comuna:</strong> {{ $reclamo->bulto->comuna->Nombre ?? 'Sin comuna' }}<br>
                            <strong>Razón Social:</strong> {{ $reclamo->bulto->razon_social ?? 'No disponible' }}<br>
                            <strong>Nombre Campaña:</strong> {{ $reclamo->bulto->nombre_campana ?? 'No disponible' }}<br>
                            <strong>Ubicación Actual:</strong> {{ $reclamo->bulto->ubicacion ?? 'No disponible' }}<br>
                            <strong>Estado:</strong> 
                                <span class="badge 
                                    {{ $reclamo->estado === 'cerrado' ? 'bg-danger' : 'bg-warning text-dark' }}">
                                    {{ ucfirst($reclamo->estado) }}
                                </span><br>
                            <small class="text-muted">Creado el {{ $reclamo->created_at->format('d-m-Y') }}</small>
                        </p>

                        {{-- Comentarios --}}
                        <h6 class="fw-bold mt-4">🗨️ Historial de comentarios:</h6>
                        @forelse ($reclamo->comentarios as $comentario)
                            <div class="border rounded p-2 mb-2 bg-light">
                                <strong>{{ $comentario->autor->name }}</strong>
                                <small class="text-muted">{{ $comentario->created_at->format('d-m-Y') }}</small>
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
@endsection
