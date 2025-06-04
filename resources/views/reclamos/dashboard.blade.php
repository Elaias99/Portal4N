@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-4">
        📊 Dashboard de Reclamos
    </h3>

    <a href="{{ route('dashboard.reclamos.export') }}" class="btn btn-success btn-sm mb-3">
        <i class="fas fa-file-excel me-1"></i> Exportar Excel
    </a>

    @if ($reclamosDetallados->count())
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title">
                    📋 Reclamos Cerrados con Información de Tracking
                </h5>
                <p class="text-muted small">
                    Cada fila representa un reclamo cerrado, con detalle de su clasificación y trazabilidad logística.
                </p>

                <table class="table table-bordered table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>Código Bulto</th>
                            <th>Chofer Entregó</th>
                            <th>Fecha Retiro</th>
                            <th>Fecha Entrega</th>
                            <th>Tiempo Transcurrido</th>
                            <th>Área que generó</th>
                            <th>Área responsable final</th>
                            <th>Tipo de Solicitud</th>
                            <th>Casuística Inicial</th>
                            <th>Casuística Final</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reclamosDetallados as $fila)
                            <tr>
                                <td>{{ $fila->codigo_bulto ?? '—' }}</td>
                                <td>{{ $fila->nombre_chofer }} {{ $fila->apellido_chofer }}</td>
                                <td>{{ \Carbon\Carbon::parse($fila->fecha_retiro)->format('d-m-Y H:i') }}</td>
                                <td>{{ \Carbon\Carbon::parse($fila->fecha_entrega)->format('d-m-Y H:i') }}</td>
                                <td>
                                    {{ \Carbon\Carbon::parse($fila->fecha_retiro)->diffForHumans($fila->fecha_entrega, true) }}
                                </td>
                                <td>{{ $fila->area_que_genero ?? '—' }}</td>
                                <td>{{ $fila->area_que_cerro ?? '—' }}</td>
                                <td>{{ ucfirst($fila->tipo_solicitud) ?? '—' }}</td>
                                <td>{{ $fila->casuistica_inicial ?? '—' }}</td>
                                <td>{{ $fila->casuistica_final ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="alert alert-info">
            No hay reclamos cerrados con información suficiente para mostrar.
        </div>
    @endif
</div>
@endsection
