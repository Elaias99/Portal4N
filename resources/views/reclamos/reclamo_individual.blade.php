@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-3">
        <i class="fa-solid fa-comment-dots me-2 text-primary"></i>
        Historial de Reclamo — Bulto: {{ $reclamo->bulto->codigo_bulto ?? 'N/A' }}
    </h4>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">

            <p><strong>Creado por:</strong>
                {{ $reclamo->trabajador->Nombre }} {{ $reclamo->trabajador->ApellidoPaterno }}
            </p>
            
            <p><strong>Área:</strong> {{ $reclamo->area->nombre ?? '—' }}</p>
            <p><strong>Descripción del problema:</strong> {{ $reclamo->descripcion }}</p>
            <p><strong>Estado:</strong>
                <span class="badge 
                    {{ $reclamo->estado === 'cerrado' ? 'bg-danger' : 'bg-warning text-dark' }}">
                    {{ ucfirst($reclamo->estado) }}
                </span>
            </p>
            <p class="text-muted">Fecha de creación: {{ $reclamo->created_at->format('d-m-Y H:i') }}</p>
        </div>
    </div>

    <h5 class="mb-3">🗨️ Comentarios</h5>

    @forelse ($reclamo->comentarios as $comentario)
        <div class="border rounded p-3 mb-2 bg-light">
            <strong>{{ $comentario->autor->name }}</strong>
            <small class="text-muted d-block">{{ $comentario->created_at->format('d-m-Y H:i') }}</small>
            <p class="mb-0">{{ $comentario->comentario }}</p>
        </div>
    @empty
        <p class="text-muted">No hay comentarios registrados para este reclamo.</p>
    @endforelse

    <a href="{{ url()->previous() }}" class="btn btn-secondary mt-4">
        <i class="fa-solid fa-arrow-left me-1"></i> Volver
    </a>
</div>
@endsection
