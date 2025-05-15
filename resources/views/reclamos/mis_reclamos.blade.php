@extends('layouts.app')

@section('content')



<div class="container">
    <h4 class="mb-4">
        <i class="fa-solid fa-user-check me-2 text-primary"></i>
        Mis Reclamos
    </h4>

    {{-- Pestañas de navegación --}}
    <ul class="nav nav-tabs mb-3" id="reclamosTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="abiertos-tab" data-bs-toggle="tab" data-bs-target="#abiertos" type="button" role="tab" aria-controls="abiertos" aria-selected="true">
                🟡 Abiertos
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="cerrados-tab" data-bs-toggle="tab" data-bs-target="#cerrados" type="button" role="tab" aria-controls="cerrados" aria-selected="false">
                🔴 Cerrados
            </button>
        </li>
    </ul>

    {{-- Contenido de las pestañas --}}
    <div class="tab-content" id="reclamosTabContent">
        {{-- Reclamos Abiertos --}}
        <div class="tab-pane fade show active" id="abiertos" role="tabpanel" aria-labelledby="abiertos-tab">
            @if ($reclamosAbiertos->isEmpty())
                <div class="alert alert-info">No tienes reclamos abiertos actualmente.</div>
            @else
                @foreach ($reclamosAbiertos as $reclamo)
                    @include('reclamos._card', ['reclamo' => $reclamo])
                @endforeach
            @endif
        </div>

        {{-- Reclamos Cerrados --}}
        <div class="tab-pane fade" id="cerrados" role="tabpanel" aria-labelledby="cerrados-tab">
            @if ($reclamosCerrados->isEmpty())
                <div class="alert alert-secondary">No tienes reclamos cerrados.</div>
            @else
                @foreach ($reclamosCerrados as $reclamo)
                    @include('reclamos._card', ['reclamo' => $reclamo])
                @endforeach
            @endif
        </div>
    </div>
</div>





@unless(auth()->user()->hasAnyRole(['admin', 'jefe']))
    <div class="mb-3">
        <a href="{{ route('empleados.perfil') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver al Perfil
        </a>
    </div>
@endunless
@endsection
