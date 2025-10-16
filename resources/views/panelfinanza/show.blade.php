@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold">Historial de Movimientos</h4>
        <a href="{{ route('cobranzas.general') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa fa-arrow-left"></i> Volver al Panel Principal
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle text-center">
                <thead class="table-light">
                    <tr>
                        <th>Tipo</th>
                        <th>Fecha</th>
                        <th>Monto</th>
                        <th>Documento Asociado</th>
                        <th>Cliente</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movimientos as $mov)
                        <tr>
                            <td>
                                <span class="badge {{ $mov['tipo'] === 'Abono' ? 'bg-warning' : 'bg-info' }}">
                                    {{ $mov['tipo'] }}
                                </span>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($mov['fecha'])->format('d-m-Y') }}</td>
                            <td>${{ number_format($mov['monto'], 0, ',', '.') }}</td>
                            <td>{{ $mov['documento']->folio ?? '-' }}</td>
                            <td>{{ $mov['documento']->razon_social ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">Sin movimientos registrados</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
