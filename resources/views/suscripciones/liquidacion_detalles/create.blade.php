@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Crear detalle mensual de suscripción</h1>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('suscripciones.liquidacion-detalles.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label class="form-label">Asignación</label>
            <select name="suscripcion_asignacion_id" class="form-control" required>
                <option value="">Seleccione una asignación</option>

                @foreach($asignaciones as $asignacion)
                    <option value="{{ $asignacion->id }}">
                        {{ $asignacion->codigo }}
                        -
                        {{ $asignacion->servicio }}
                        -
                        ${{ number_format($asignacion->costo, 0, ',', '.') }}
                        -
                        {{ $asignacion->transportista?->nombre_transportista }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Año</label>
            <input type="number" name="anio" class="form-control" value="2026" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Mes</label>
            <input type="number" name="mes" class="form-control" value="4" min="1" max="12" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Q inasistencia</label>
            <input type="number" name="q_inasistencia" class="form-control" value="0" min="0">
        </div>

        <button type="submit" class="btn btn-primary">
            Calcular y guardar
        </button>
    </form>
</div>
@endsection