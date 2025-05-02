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
                <div class="list-group-item mb-2">
                    <strong>Bulto:</strong> {{ $reclamo->bulto->codigo_bulto ?? 'Sin código' }}<br>
                    <strong>Descripción:</strong> {{ $reclamo->descripcion }}<br>
                    <strong>Estado:</strong> <span class="badge bg-warning text-dark">{{ ucfirst($reclamo->estado) }}</span><br>
                    <small class="text-muted">Creado el {{ $reclamo->created_at->format('d-m-Y H:i') }}</small>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
