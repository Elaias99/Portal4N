@extends('layouts.app')

@section('content')
<div class="container">

    <h2 class="mb-4">Historial de movimientos de honorarios</h2>

    {{-- =========================
    TABLA DE MOVIMIENTOS
    ========================== --}}
    <div class="card">
        <div class="card-header">
            <strong>Libro de movimientos</strong>
        </div>

        <div class="card-body p-0">

            @if($movimientos->isEmpty())
                <div class="p-4 text-muted">
                    No existen movimientos registrados.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Empresa</th>
                                <th>Emisor</th>
                                <th>Folio</th>
                                <th>Movimiento</th>
                                <th>Estado</th>
                                <th>Detalle</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($movimientos as $mov)
                                @php
                                    $hon = $mov->honorario;
                                @endphp

                                <tr>
                                    {{-- Fecha --}}
                                    <td>
                                        {{ $mov->fecha_cambio?->format('d-m-Y H:i') }}
                                    </td>

                                    {{-- Usuario --}}
                                    <td>
                                        {{ $mov->user->name ?? 'Sistema' }}
                                    </td>

                                    {{-- Empresa --}}
                                    <td>
                                        {{ $hon?->empresa?->Nombre ?? '-' }}
                                    </td>

                                    {{-- Emisor --}}
                                    <td>
                                        {{ $hon?->razon_social_emisor ?? '-' }}
                                    </td>

                                    {{-- Folio --}}
                                    <td class="text-center fw-bold">
                                        {{ $hon?->folio ?? '-' }}
                                    </td>

                                    {{-- Tipo movimiento --}}
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ $mov->tipo_movimiento }}
                                        </span>
                                    </td>

                                    {{-- Estado --}}
                                    <td>
                                        {{ $mov->estado_anterior ?? '-' }}
                                        →
                                        <strong>{{ $mov->nuevo_estado ?? '-' }}</strong>
                                    </td>

                                    {{-- Detalle --}}
                                    <td>
                                        <div>{{ $mov->descripcion }}</div>

                                        @if($mov->datos_anteriores || $mov->datos_nuevos)
                                            <small class="text-muted d-block mt-1">
                                                @if(isset($mov->datos_anteriores['saldo']))
                                                    Saldo anterior:
                                                    ${{ number_format($mov->datos_anteriores['saldo'], 0, ',', '.') }}
                                                    →
                                                @endif

                                                @if(isset($mov->datos_nuevos['saldo']))
                                                    Saldo nuevo:
                                                    ${{ number_format($mov->datos_nuevos['saldo'], 0, ',', '.') }}
                                                @endif
                                            </small>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

        </div>

        {{-- PAGINACIÓN --}}
        @if($movimientos->hasPages())
            <div class="card-footer d-flex justify-content-center">
                {{ $movimientos->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </div>

    {{-- =========================
    VOLVER
    ========================== --}}
    <div class="text-center mt-4">
        <a href="{{ route('honorarios.mensual.index') }}"
           class="btn btn-outline-primary px-4 py-2 rounded-pill">
            ← Volver a Honorarios Mensuales
        </a>
    </div>

</div>
@endsection
