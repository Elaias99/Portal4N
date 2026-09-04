@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">

    {{-- ====== VOLVER ====== --}}
    <a href="{{ route('courier.agentes.index', ['periodo' => $periodo->codigo]) }}"
       class="btn btn-sm btn-link ps-0 mb-2">
        Volver a proveedores
    </a>

    {{-- ====== CABECERA ====== --}}
    <div class="d-flex flex-wrap justify-content-between align-items-start mb-4">
        <div>
            <h3 class="mb-2">{{ $agente->nombre }}</h3>

            <div class="mb-2">
                @forelse($zonas as $zona)
                    <span class="badge bg-secondary">Zona {{ $zona }}</span>
                @empty
                    <span class="text-muted small">Sin zona registrada</span>
                @endforelse
            </div>

            <p class="text-muted mb-0">
                {{ $cobertura->count() }} comunas · {{ $periodo->nombre }}
            </p>
        </div>

        {{-- Selector de período --}}
        <form method="GET"
              action="{{ route('courier.agentes.show', ['agente' => $agente->id]) }}">
            <div class="d-flex align-items-center gap-2">
                <label for="periodo" class="form-label mb-0 text-muted small">
                    Período
                </label>
                <select name="periodo" id="periodo"
                        class="form-select form-select-sm"
                        onchange="this.form.submit()">
                    @foreach($periodos as $p)
                        <option value="{{ $p->codigo }}"
                            @selected($p->id === $periodo->id)>
                            {{ $p->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    {{-- ====== TITULARES ====== --}}
    <div class="card mb-4">
        <div class="card-header bg-white fw-semibold">
            Titular del pago
        </div>
        <div class="card-body">
            @forelse($agente->proveedores as $prov)
                <div class="mb-1">
                    {{ $prov->nombre_proveedor }}
                    @if($agente->proveedores->count() > 1 && $prov->principal)
                        <span class="badge bg-light text-dark">principal</span>
                    @endif
                </div>
            @empty
                <span class="text-muted">Sin titular registrado.</span>
            @endforelse

            @if($agente->proveedores->count() > 1)
                <div class="alert alert-warning mt-3 mb-0 py-2 small">
                    Este agente tiene más de un titular según la comuna.
                    Falta confirmar con Operaciones a quién se le paga.
                </div>
            @endif
        </div>
    </div>

    {{-- ====== COBERTURA ====== --}}
    <div class="card">
        <div class="card-header bg-white fw-semibold">
            Comunas que cubre
        </div>

        @if($cobertura->isEmpty())
            <div class="card-body text-muted">
                Este agente no tiene comunas asignadas en {{ $periodo->nombre }}.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Comuna</th>
                            <th>Zona</th>
                            <th>Paga retorno</th>
                            <th class="text-end">Valor retorno</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cobertura as $fila)
                            <tr>
                                <td>{{ $fila->localidad }}</td>

                                <td>
                                    {{ $fila->zona ?? '—' }}
                                </td>

                                <td>
                                    @if($fila->pagar_retorno)
                                        <span class="text-success fw-semibold">Sí</span>
                                    @else
                                        <span class="text-muted">No</span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    @if($fila->valor_retorno)
                                        ${{ number_format($fila->valor_retorno, 0, ',', '.') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection