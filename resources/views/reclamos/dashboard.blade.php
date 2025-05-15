@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-4">
        📊 Dashboard de Reclamos
    </h3>

    <a href="{{ route('dashboard.reclamos.export') }}" class="btn btn-success btn-sm mb-3">
        <i class="fas fa-file-excel me-1"></i> Exportar Excel
    </a>


    @if ($resumenPorPareja->count())
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title">
                    📌 Comparación: Casuística Inicial vs Casuística Final (Reclamos Cerrados)
                </h5>
                <p class="text-muted small">
                    Este resumen muestra cómo se clasificaron inicialmente los reclamos y cómo fueron diagnosticados finalmente tras su investigación.
                </p>


                <table class="table table-bordered table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>Área que generó</th>
                            <th>Área responsable final</th>
                            <th>Tipo de Solicitud</th>
                            <th>Casuística Inicial</th>
                            <th>Casuística Final</th>
                            <th class="text-center">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($resumenPorPareja as $fila)
                            <tr>
                                <td>{{ $fila->area_que_genero ?? '—' }}</td>
                                <td>{{ $fila->area_que_cerro ?? '—' }}</td>
                                <td>{{ ucfirst($fila->tipo_solicitud) ?? '—' }}</td>
                                <td>{{ $fila->casuistica_inicial ?? '—' }}</td>
                                <td>{{ $fila->casuistica_final ?? '—' }}</td>
                                <td class="text-center">{{ $fila->cantidad }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>
        </div>
    @else
        <div class="alert alert-info">
            No hay reclamos cerrados con casuísticas registradas aún.
        </div>
    @endif
</div>
@endsection
