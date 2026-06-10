@extends('layouts.app')

@section('content')
<div class="container">

    @php
        $cantidadPuntosAsignados = $asignacion->opvPuntos?->count() ?? 0;
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Puntos OPV registrados</h1>

        <a href="{{ url()->previous() }}" class="btn btn-secondary">
            Volver
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Ruta OPV consultada</strong>

            <span class="small text-muted">
                {{ $cantidadPuntosAsignados }} punto{{ $cantidadPuntosAsignados === 1 ? '' : 's' }} asociado{{ $cantidadPuntosAsignados === 1 ? '' : 's' }}
            </span>
        </div>

        <div class="card-body">
            <p class="mb-1">
                <strong>Proveedor:</strong>
                {{ $asignacion->suscripcionProveedor?->cobranzaCompra?->razon_social ?? '—' }}
            </p>

            <p class="mb-1">
                <strong>RUT:</strong>
                {{ $asignacion->suscripcionProveedor?->cobranzaCompra?->rut_cliente ?? '—' }}
            </p>

            <p class="mb-1">
                <strong>Transportista:</strong>
                {{ $asignacion->transportista?->nombre_transportista ?? '—' }}
            </p>

            <p class="mb-0">
                <strong>Punto OPV consultado:</strong>
                {{ $asignacion->punto_1 ?? '—' }}
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <strong>Tabla completa de puntos OPV</strong>
        </div>

        <div class="card-body">
            @if($opvPuntos->isEmpty())
                <div class="border rounded p-3 text-muted">
                    Esta asignación OPV no tiene locales asociados.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="fw-normal">Ruta</th>
                                <th class="text-center">Local</th>
                                <th>Nombre de Local</th>
                                <th>Nombre corto</th>
                                <th>Dirección</th>
                                <th>Comuna</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($opvPuntos as $punto)
                                <tr>
                                    <td>{{ $punto->ruta_nombre ?? '—' }}</td>
                                    <td class="text-center">{{ $punto->local ?? '—' }}</td>
                                    <td>{{ $punto->nombre_local ?? '—' }}</td>
                                    <td>{{ $punto->nombre_local_corto ?? '—' }}</td>
                                    <td>{{ $punto->direccion ?? '—' }}</td>
                                    <td>{{ $punto->comuna ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection