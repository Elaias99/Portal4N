@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">

    {{-- ====== CABECERA ====== --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">Courier · Tablas tarifarias</h3>
            <p class="text-muted mb-0">
                Valor por peso de cada tarifa. Las tarifas se buscan por
                coincidencia exacta del peso en kilos enteros.
            </p>
        </div>

        @if($periodos->isNotEmpty())
            <form method="GET" action="{{ route('courier.tarifas') }}">
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
            <a class="nav-link active"
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
    @elseif($tarifas->isEmpty())
        <div class="alert alert-info">
            No hay tarifas cargadas para {{ $periodo->nombre }}.
        </div>
    @else

        <p class="text-muted small mb-3">
            {{ $tarifas->count() }} tarifas · {{ $periodo->nombre }}
        </p>

        {{-- ====== TABLA ====== --}}
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:70px">Tabla</th>
                            <th>Nombre</th>
                            <th class="text-end">1 kg</th>
                            <th class="text-end">5 kg</th>
                            <th class="text-end">10 kg</th>
                            <th class="text-end">20 kg</th>
                            <th class="text-end">Kilo adicional</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tarifas as $tarifa)
                            <tr>
                                <td class="fw-semibold">
                                    {{ $tarifa->numero }}
                                </td>

                                <td class="{{ $tarifa->numero === 0 ? 'text-muted' : '' }}">
                                    {{ $tarifa->nombre }}
                                </td>

                                @foreach(['precio_1','precio_5','precio_10','precio_20'] as $campo)
                                    <td class="text-end">
                                        @if($tarifa->$campo !== null)
                                            ${{ number_format($tarifa->$campo, 0, ',', '.') }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                @endforeach

                                <td class="text-end">
                                    @if($tarifa->esPlana())
                                        <span class="text-muted">plana</span>
                                    @else
                                        ${{ number_format($tarifa->kilo_adicional, 0, ',', '.') }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <p class="text-muted small mt-3 mb-0">
            Sobre los 20 kilos el valor se proyecta sumando el kilo adicional
            por cada kilo extra. Las tarifas marcadas como planas cobran lo
            mismo a cualquier peso.
        </p>
    @endif

</div>
@endsection