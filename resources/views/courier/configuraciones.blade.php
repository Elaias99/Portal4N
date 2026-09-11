@extends('layouts.app')

@vite('resources/css/courier.css')

@section('content')
<div class="co-page">

    @include('courier.partials.header', [
        'titulo' => 'Configuración de pago',
        'subtitulo' => 'Para cada agente + cliente + servicio: si se paga y con qué tabla. Es la hoja PagosCentroCostos.',
    ])

    @include('courier.partials.nav', ['activo' => 'configuraciones'])

    <section class="co-region">
        <form method="GET" action="{{ route('courier.configuraciones') }}" class="co-filters" role="search">
            <div class="co-field co-span-4">
                <label for="q">Cliente o servicio</label>
                <input type="search" id="q" name="q" class="form-control"
                       value="{{ $buscar }}" placeholder="Cruz Verde, Ecommerce, Valijas…" autocomplete="off">
            </div>
            <div class="co-field co-span-3">
                <label for="agente">Agente</label>
                <select id="agente" name="agente" class="form-select">
                    <option value="">Todos</option>
                    @foreach($agentes as $a)
                        <option value="{{ $a->id }}" @selected($agenteSeleccionado === $a->id)>{{ $a->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="co-field co-span-2">
                <label for="estado">¿Se paga?</label>
                <select id="estado" name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option value="SI" @selected($estadoSeleccionado === 'SI')>Sí</option>
                    <option value="NO" @selected($estadoSeleccionado === 'NO')>No</option>
                    <option value="REVISAR" @selected($estadoSeleccionado === 'REVISAR')>Por revisar</option>
                </select>
            </div>
            <div class="co-span-2">
                <button type="submit" class="co-btn co-btn-primary w-100">Filtrar</button>
            </div>
            @if($buscar !== '' || $agenteSeleccionado || $estadoSeleccionado !== '')
                <div class="co-span-1">
                    <a href="{{ route('courier.configuraciones') }}" class="co-btn co-btn-muted w-100" title="Limpiar">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                </div>
            @endif
        </form>

        <div class="co-region-head">
            <h2 class="co-region-title">
                {{ number_format($configuraciones->total(), 0, ',', '.') }} configuraciones
            </h2>
            <span class="co-region-meta">
                <span class="co-badge co-badge-si">SI {{ number_format($resumen['configuraciones']['si'], 0, ',', '.') }}</span>
                <span class="co-badge co-badge-no">NO {{ number_format($resumen['configuraciones']['no'], 0, ',', '.') }}</span>
                <span class="co-badge co-badge-revisar">REVISAR {{ $resumen['configuraciones']['revisar'] }}</span>
                <span style="margin-left:0.5rem">en todo el catálogo</span>
            </span>
        </div>

        <div class="co-region-body is-flush">
            @if($configuraciones->isEmpty())
                <div class="co-empty">Ninguna configuración coincide con el filtro.</div>
            @else
                <div class="table-responsive">
                    <table class="co-table">
                        <thead>
                            <tr>
                                <th>Agente</th>
                                <th>Cliente</th>
                                <th>Servicio</th>
                                <th>¿Se paga?</th>
                                <th>Tabla</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($configuraciones as $cfg)
                                <tr>
                                    <td>
                                        @if($cfg->agente)
                                            <a href="{{ route('courier.agentes.show', ['agente' => $cfg->agente->id]) }}">{{ $cfg->agente->nombre }}</a>
                                        @else
                                            <span class="is-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="is-strong">{{ $cfg->comerciante }}</td>
                                    <td class="is-muted">{{ $cfg->servicio }}</td>
                                    <td>
                                        @if($cfg->pagar === 'SI')
                                            <span class="co-badge co-badge-si">SI</span>
                                        @elseif($cfg->pagar === 'NO')
                                            <span class="co-badge co-badge-no">NO</span>
                                        @else
                                            <span class="co-badge co-badge-revisar">REVISAR</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($cfg->tabla === null)
                                            <span class="is-muted">—</span>
                                        @else
                                            <span class="co-badge {{ $cfg->tabla === 0 ? 'co-badge-neutral' : 'co-badge-accent' }}">Tabla {{ $cfg->tabla }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="co-pagination">
                    <span>
                        Mostrando {{ $configuraciones->firstItem() }}–{{ $configuraciones->lastItem() }} de {{ number_format($configuraciones->total(), 0, ',', '.') }}
                    </span>
                    {{ $configuraciones->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </section>

</div>
@endsection
