@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">

    {{-- ====== CABECERA ====== --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">Courier · Proveedores</h3>
            <p class="text-muted mb-0">
                Agentes Courier y las comunas que cubren en el período.
            </p>
        </div>

        {{-- Selector de período --}}
        @if($periodos->isNotEmpty())
            <form method="GET" action="{{ route('courier.agentes.index') }}">
                <div class="d-flex align-items-center gap-2">
                    <label for="periodo" class="form-label mb-0 text-muted small">
                        Período
                    </label>
                    <select name="periodo" id="periodo"
                            class="form-select form-select-sm"
                            onchange="this.form.submit()">
                        @foreach($periodos as $p)
                            <option value="{{ $p->codigo }}"
                                @selected($periodo && $p->id === $periodo->id)>
                                {{ $p->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        @endif
    </div>


        {{-- ====== NAVEGACIÓN DEL MÓDULO ====== --}}
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link active"
               href="{{ route('courier.agentes.index', ['periodo' => $periodo?->codigo]) }}">
                Proveedores
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link"
               href="{{ route('courier.tarifas', ['periodo' => $periodo?->codigo]) }}">
                Tablas tarifarias
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link"
               href="{{ route('courier.configuraciones', ['periodo' => $periodo?->codigo]) }}">
                Configuración de pago
            </a>
        </li>
    </ul>




    {{-- ====== SIN DATOS ====== --}}
    @if($periodo === null)
        <div class="alert alert-warning">
            Todavía no hay períodos cargados en el módulo Courier.
        </div>
    @elseif($agentes->isEmpty())
        <div class="alert alert-info">
            No hay agentes activos para {{ $periodo->nombre }}.
        </div>
    @else

        {{-- ====== RESUMEN ====== --}}
        <p class="text-muted small mb-3">
            {{ $agentes->count() }} agentes ·
            {{ $agentes->sum('comunas_count') }} comunas con cobertura ·
            {{ $periodo->nombre }}
        </p>

        {{-- ====== TABLA ====== --}}
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Agente</th>
                            <th>Zona</th>
                            <th>Titular</th>
                            <th class="text-end">Comunas</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($agentes as $agente)
                            <tr>
                                <td class="fw-semibold">
                                    {{ $agente->nombre }}
                                </td>

                                <td>
                                    @forelse($agente->zonas as $zona)
                                        <span class="badge bg-secondary">{{ $zona }}</span>
                                    @empty
                                        <span class="text-muted">—</span>
                                    @endforelse
                                </td>

                                <td>
                                    @forelse($agente->proveedores as $prov)
                                        <div class="small">
                                            {{ $prov->nombre_proveedor }}
                                            @if($agente->proveedores->count() > 1 && $prov->principal)
                                                <span class="badge bg-light text-dark">principal</span>
                                            @endif
                                        </div>
                                    @empty
                                        <span class="text-muted">—</span>
                                    @endforelse
                                </td>

                                <td class="text-end">
                                    {{ $agente->comunas_count }}
                                </td>

                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary"
                                       href="{{ route('courier.agentes.show', [
                                            'agente' => $agente->id,
                                            'periodo' => $periodo->codigo,
                                       ]) }}">
                                        Ver comunas
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
@endsection