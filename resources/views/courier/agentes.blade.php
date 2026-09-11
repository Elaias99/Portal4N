@extends('layouts.app')

@vite('resources/css/courier.css')

@section('content')
<div class="co-page">

    @include('courier.partials.header', [
        'titulo' => 'Agentes Courier',
        'subtitulo' => 'Quiénes reparten, en qué zona, cuántas comunas cubren y cuántas configuraciones de pago tienen.',
    ])

    @include('courier.partials.nav', ['activo' => 'agentes'])

    <section class="co-region">
        <form method="GET" action="{{ route('courier.agentes.index') }}" class="co-filters" role="search">
            <div class="co-field co-span-6">
                <label for="q">Buscar agente</label>
                <input type="search" id="q" name="q" class="form-control"
                       value="{{ $buscar }}" placeholder="Nombre del agente…" autocomplete="off">
            </div>
            <div class="co-span-2">
                <button type="submit" class="co-btn co-btn-primary w-100">Buscar</button>
            </div>
            @if($buscar !== '')
                <div class="co-span-2">
                    <a href="{{ route('courier.agentes.index') }}" class="co-btn co-btn-muted w-100">Limpiar</a>
                </div>
            @endif
        </form>

        <div class="co-region-head">
            <h2 class="co-region-title">{{ $agentes->count() }} agentes</h2>
            <span class="co-region-meta">
                {{ number_format($agentes->sum('comunas_count'), 0, ',', '.') }} comunas ·
                {{ number_format($agentes->sum('configuraciones_count'), 0, ',', '.') }} configuraciones de pago
            </span>
        </div>

        <div class="co-region-body is-flush">
            @if($agentes->isEmpty())
                <div class="co-empty">No hay agentes que coincidan con "{{ $buscar }}".</div>
            @else
                <div class="table-responsive">
                    <table class="co-table">
                        <thead>
                            <tr>
                                <th>Agente</th>
                                <th>Zona</th>
                                <th>Titular</th>
                                <th class="is-num">Comunas</th>
                                <th class="is-num">Config. de pago</th>
                                <th class="is-num">Se pagan</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($agentes as $agente)
                                <tr>
                                    <td class="is-strong">{{ $agente->nombre }}</td>

                                    <td>
                                        @forelse($agente->zonas as $zona)
                                            <span class="co-badge {{ $zona === 'RM' ? 'co-badge-zona-rm' : 'co-badge-zona-regiones' }}">{{ $zona }}</span>
                                        @empty
                                            <span class="co-badge co-badge-zona-none">sin zona</span>
                                        @endforelse
                                    </td>

                                    <td>
                                        @forelse($agente->proveedores as $prov)
                                            <div>
                                                {{ $prov->nombre_proveedor }}
                                                @if($agente->proveedores->count() > 1 && $prov->principal)
                                                    <span class="co-badge co-badge-neutral">principal</span>
                                                @endif
                                            </div>
                                        @empty
                                            <span class="is-muted">—</span>
                                        @endforelse
                                    </td>

                                    <td class="is-num">{{ number_format($agente->comunas_count, 0, ',', '.') }}</td>
                                    <td class="is-num">{{ number_format($agente->configuraciones_count, 0, ',', '.') }}</td>
                                    <td class="is-num">{{ number_format($agente->pagables_count, 0, ',', '.') }}</td>

                                    <td class="is-num">
                                        <a href="{{ route('courier.agentes.show', ['agente' => $agente->id]) }}"
                                           class="co-btn co-btn-muted co-btn-sm">
                                            Ver ficha
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>

</div>
@endsection
