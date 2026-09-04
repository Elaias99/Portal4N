@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">

    {{-- ====== CABECERA ====== --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">Courier · Configuración de pago</h3>
            <p class="text-muted mb-0">
                Qué tarifa y qué estado le corresponde a cada combinación de
                agente, cliente y servicio.
            </p>
        </div>

        @if($periodos->isNotEmpty())
            <form method="GET" action="{{ route('courier.configuraciones') }}">
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
            <a class="nav-link"
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
            <a class="nav-link active"
               href="{{ route('courier.configuraciones', ['periodo' => $periodo?->codigo]) }}">
                Configuración de pago
            </a>
        </li>
    </ul>

    @if($periodo === null)
        <div class="alert alert-warning">
            Todavía no hay períodos cargados en el módulo Courier.
        </div>
    @else

        {{-- ====== FILTROS ====== --}}
        <form method="GET" action="{{ route('courier.configuraciones') }}"
              class="row g-2 align-items-end mb-3">

            <input type="hidden" name="periodo" value="{{ $periodo->codigo }}">

            <div class="col-md-4">
                <label for="agente" class="form-label text-muted small mb-1">
                    Agente
                </label>
                <select name="agente" id="agente" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    @foreach($agentes as $a)
                        <option value="{{ $a->id }}"
                            @selected((string) $agenteSeleccionado === (string) $a->id)>
                            {{ $a->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label for="estado" class="form-label text-muted small mb-1">
                    Estado
                </label>
                <select name="estado" id="estado" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="SI" @selected($estadoSeleccionado === 'SI')>Se paga</option>
                    <option value="NO" @selected($estadoSeleccionado === 'NO')>No se paga</option>
                    <option value="REVISAR" @selected($estadoSeleccionado === 'REVISAR')>Por revisar</option>
                </select>
            </div>

            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">
                    Filtrar
                </button>
            </div>

            <div class="col-md-2">
                <a href="{{ route('courier.configuraciones', ['periodo' => $periodo->codigo]) }}"
                   class="btn btn-sm btn-outline-secondary w-100">
                    Limpiar
                </a>
            </div>
        </form>

        {{-- ====== RESUMEN ====== --}}
        <p class="text-muted small mb-3">
            {{ $configuraciones->count() }} configuraciones ·
            {{ $configuraciones->where('pagar', 'SI')->count() }} se pagan ·
            {{ $configuraciones->where('pagar', 'NO')->count() }} no ·
            {{ $configuraciones->where('pagar', 'REVISAR')->count() }} por revisar
        </p>

        {{-- ====== TABLA ====== --}}
        @if($configuraciones->isEmpty())
            <div class="alert alert-info">
                No hay configuraciones que coincidan con el filtro.
            </div>
        @else
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Agente</th>
                                <th>Cliente</th>
                                <th>Servicio</th>
                                <th style="width:110px">¿Se paga?</th>
                                <th class="text-end" style="width:80px">Tabla</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($configuraciones as $cfg)
                                <tr>
                                    <td>{{ $cfg->agente?->nombre ?? '—' }}</td>
                                    <td>{{ $cfg->comerciante }}</td>
                                    <td>{{ $cfg->servicio }}</td>

                                    <td>
                                        @if($cfg->pagar === 'SI')
                                            <span class="text-success fw-semibold">SI</span>
                                        @elseif($cfg->pagar === 'NO')
                                            <span class="text-danger fw-semibold">NO</span>
                                        @else
                                            <span class="text-warning fw-semibold">REVISAR</span>
                                        @endif
                                    </td>

                                    <td class="text-end">
                                        @if($cfg->tabla === null)
                                            <span class="text-muted">—</span>
                                        @elseif($cfg->tabla === 0)
                                            <span class="text-muted">0</span>
                                        @else
                                            {{ $cfg->tabla }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif

</div>
@endsection