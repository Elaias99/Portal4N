@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-4">
        <i class="fa-solid fa-user-check me-2 text-primary"></i>
        Mis Reclamos
    </h4>

    @if ($reclamos->isEmpty())
        <div class="alert alert-info">
            No has registrado reclamos aún.
        </div>
    @else
        <div class="list-group">
            @foreach ($reclamos as $reclamo)
                <div class="card mb-3 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">📦 Bulto: {{ $reclamo->bulto->codigo_bulto ?? '—' }}</h5>

                        <p class="mb-1">
                            <strong>Descripción:</strong> {{ $reclamo->descripcion }}
                        </p>
                        <p class="mb-1">
                            <strong>Área:</strong> {{ $reclamo->area->nombre ?? '—' }}
                        </p>
                        <p class="mb-1">
                            <strong>Estado:</strong>
                            <span class="badge 
                                {{ $reclamo->estado === 'cerrado' ? 'bg-danger' : 'bg-warning text-dark' }}">
                                {{ ucfirst($reclamo->estado) }}
                            </span>
                        </p>
                        <p class="text-muted mb-2">
                            Creado el {{ $reclamo->created_at->format('d-m-Y H:i') }}
                        </p>

                        <a href="{{ route('reclamos.ver', $reclamo->id) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fa-solid fa-comments me-1"></i> Ver Historial
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@unless(auth()->user()->hasAnyRole(['admin', 'jefe']))
    <div class="mb-3">
        <a href="{{ route('empleados.perfil') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver al Perfil
        </a>
    </div>
@endunless
@endsection
