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
                    <strong>Descripción del Bulto:</strong> {{ $reclamo->bulto->descripcion_bulto ?? 'No disponible' }}<br>
                    <strong>Atención a:</strong> {{ $reclamo->bulto->atencion ?? 'No disponible' }}<br>
                    <strong>Dirección:</strong> {{ $reclamo->bulto->direccion ?? 'No disponible' }}<br>
                    <strong>Comuna:</strong> {{ $reclamo->bulto->comuna->Nombre ?? 'Sin comuna' }}<br>
                    <strong>Razón Social:</strong> {{ $reclamo->bulto->razon_social ?? 'No disponible' }}<br>
                    <strong>Nombre Campaña:</strong> {{ $reclamo->bulto->nombre_campana ?? 'No disponible' }}<br>
                    <strong>Ubicación Actual:</strong> {{ $reclamo->bulto->ubicacion ?? 'No disponible' }}<br>

                    <strong>Estado:</strong> <span class="badge bg-warning text-dark">{{ ucfirst($reclamo->estado) }}</span><br>
                    <small class="text-muted">Creado el {{ $reclamo->created_at->format('d-m-Y H:i') }}</small>
                </div>

                @if(is_null($reclamo->respuesta_admin))
                    <form action="{{ route('reclamos.responder', $reclamo->id) }}" method="POST" class="mt-3">
                        @csrf
                        <div class="mb-2">
                            <label for="respuesta_{{ $reclamo->id }}" class="form-label fw-semibold">Responder Reclamo:</label>
                            <textarea name="respuesta_admin" id="respuesta_{{ $reclamo->id }}" class="form-control" rows="2" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-success">Enviar Respuesta</button>
                    </form>
                @else
                    <div class="mt-2">
                        <strong>Respuesta enviada:</strong>
                        <p class="text-muted">{{ $reclamo->respuesta_admin }}</p>
                    </div>
                @endif



            @endforeach
        </div>
    @endif
</div>
@endsection
