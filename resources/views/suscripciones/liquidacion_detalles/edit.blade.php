@extends('layouts.app')

@section('content')
<div class="container">
    @php
        $esValorFijo = str_ends_with(mb_strtoupper(trim($detalle->codigo)), '.COM');

        $meses = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];
    @endphp

    <h1>
        @if($esValorFijo)
            Detalle de valor fijo
        @else
            Editar inasistencia
        @endif
    </h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($esValorFijo)
        <div class="alert alert-info">
            Este código corresponde a un valor fijo mensual. No aplica cálculo por fines de semana ni inasistencias.
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <p>
                <strong>Proveedor:</strong>
                {{ $detalle->asignacion?->suscripcionProveedor?->cobranzaCompra?->razon_social ?? '—' }}
            </p>

            <p>
                <strong>Transportista:</strong>
                {{ $detalle->asignacion?->transportista?->nombre_transportista ?? '—' }}
            </p>

            <p>
                <strong>Código:</strong>
                {{ $detalle->codigo }}

                @if($esValorFijo)
                    <span class="badge bg-secondary ms-2">Valor fijo</span>
                @endif
            </p>

            <p>
                <strong>Año / Mes:</strong>
                {{ $detalle->anio }} / {{ $meses[$detalle->mes] ?? $detalle->mes }}
            </p>

            <p>
                <strong>Costo:</strong>
                ${{ number_format($detalle->costo, 0, ',', '.') }}
            </p>

            <p>
                <strong>Q calendario:</strong>
                @if($esValorFijo)
                    No aplica
                @else
                    {{ $detalle->q_calendario }}
                @endif
            </p>

            <p>
                <strong>Q inasistencia:</strong>
                @if($esValorFijo)
                    No aplica
                @else
                    {{ $detalle->q_inasistencia }}
                @endif
            </p>

            <p>
                <strong>Cantidad actual:</strong>
                @if($esValorFijo)
                    No aplica
                @else
                    {{ $detalle->cantidad }}
                @endif
            </p>

            <p>
                <strong>Total actual:</strong>
                ${{ number_format($detalle->total, 0, ',', '.') }}
            </p>
        </div>
    </div>

    @if(!$esValorFijo)
        <form action="{{ route('suscripciones.liquidacion-detalles.update', $detalle->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Q inasistencia</label>
                <input 
                    type="number"
                    name="q_inasistencia"
                    class="form-control"
                    value="{{ old('q_inasistencia', $detalle->q_inasistencia) }}"
                    min="0"
                    max="{{ $detalle->q_calendario }}"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary">
                Recalcular total
            </button>

            <a href="{{ route('suscripciones.liquidacion-detalles.index') }}" class="btn btn-secondary">
                Volver
            </a>
        </form>
    @else
        <a href="{{ route('suscripciones.liquidacion-detalles.index') }}" class="btn btn-secondary">
            Volver
        </a>
    @endif
</div>
@endsection