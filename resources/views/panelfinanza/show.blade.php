@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold">Historial de Movimientos</h4>
        <a href="{{ route('cobranzas.general') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa fa-arrow-left"></i> Volver al Panel Principal
        </a>
    </div>

    <form method="GET" action="{{ route('panelfinanza.show') }}" class="mb-4">
        <div class="row align-items-end">
            {{-- Campo Desde --}}
            <div class="col-md-2 mb-3">
                <label class="form-label small text-muted">Desde</label>
                <input type="date" name="fecha_inicio" class="form-control form-control-sm"
                    value="{{ request('fecha_inicio') }}">
            </div>

            {{-- Campo Hasta --}}
            <div class="col-md-2 mb-3">
                <label class="form-label small text-muted">Hasta</label>
                <input type="date" name="fecha_fin" class="form-control form-control-sm"
                    value="{{ request('fecha_fin') }}">
            </div>

            {{-- Botón Buscar --}}
            <div class="col-md-1 mb-3">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="fa fa-search"></i>
                </button>
            </div>

            <div class="col-md-1 mb-3">
                <a href="{{ route('panelfinanza.export', request()->query()) }}" class="btn btn-success btn-sm w-100">
                    Exportar
                </a>
            </div>



        </div>
    </form>



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
                                @php
                                    $estado = strtolower($mov['documento']->status_original ?? '');
                                    $color = match ($estado) {
                                        'al día'   => 'bg-success',
                                        'vencido'  => 'bg-danger',
                                        default    => 'bg-secondary',
                                    };
                                @endphp

                                <span class="badge {{ $color }}">
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
