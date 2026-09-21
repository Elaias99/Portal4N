@extends('layouts.app')

@vite('resources/css/courier.css')

@section('content')
<div class="co-page">

    @include('courier.partials.header', [
        'titulo' => 'Courier · Comunas',
        'subtitulo' => 'Escribe una comuna y sabrás qué agente la reparte, en qué zona y si se paga retorno. Desde el agente llegas a sus clientes, tarifas y comunas.',
    ])

    @include('courier.partials.nav', ['activo' => 'comunas'])

    {{-- ====== BUSCADOR ====== --}}
    <form method="GET" action="{{ route('courier.comunas') }}" class="co-search" role="search">
        <p class="co-search-label">¿Qué agente atiende esta comuna?</p>
        <div class="co-search-row">
            <div class="co-search-input">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input type="search" id="q" name="q" class="form-control"
                       value="{{ $buscar }}" placeholder="Curacaví, Las Condes, Puerto Aysén… o el nombre de un agente"
                       autocomplete="off" autofocus>
            </div>
            <select id="zona" name="zona" class="form-select co-search-zona" aria-label="Zona">
                <option value="">Todas las zonas</option>
                <option value="RM" @selected($zona === 'RM')>RM</option>
                <option value="Regiones" @selected($zona === 'Regiones')>Regiones</option>
                <option value="sin_zona" @selected($zona === 'sin_zona')>Sin zona</option>
            </select>
            <button type="submit" class="co-btn co-btn-primary">Buscar</button>
            @if($buscar !== '' || $zona !== '')
                <a href="{{ route('courier.comunas') }}" class="co-btn co-btn-muted">Limpiar</a>
            @endif
        </div>
        <p class="co-search-hint">
            Sin preocuparte de tildes ni mayúsculas. Una misma comuna puede aparecer con varias escrituras: cada una es una fila, porque así la envía Geolice.
        </p>
    </form>

    {{-- ====== RESULTADOS ====== --}}
    <section class="co-region">
        <div class="co-region-head">
            <h2 class="co-region-title">
                {{ number_format($comunas->total(), 0, ',', '.') }}
                {{ $comunas->total() === 1 ? 'comuna' : 'comunas' }}
                @if($buscar !== '')
                    para "{{ $buscar }}"
                @endif
            </h2>
            <span class="co-region-meta">Hoja <code>Operador</code> de la planilla</span>
        </div>

        <div class="co-region-body is-flush">
            @if($comunas->isEmpty())
                <div class="co-empty">
                    Ninguna comuna coincide.
                    @if($buscar !== '')
                        Si Geolice la envía así, hay que agregarla al catálogo — por eso conviene que Operaciones la confirme.
                    @endif
                </div>
            @else
                <div class="table-responsive">
                    <table class="co-table">
                        <thead>
                            <tr>
                                <th>Comuna (tal como llega)</th>
                                <th>Agente</th>
                                <th>Zona</th>
                                <th class="is-num">Paga retorno</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($comunas as $fila)
                                <tr>
                                    <td class="is-strong">{{ $fila->localidad }}</td>
                                    <td>
                                        @if($fila->agente)
                                            <a href="{{ route('courier.agentes.show', ['agente' => $fila->agente->id]) }}">
                                                {{ $fila->agente->nombre }}
                                            </a>
                                        @else
                                            <span class="is-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($fila->zona)
                                            <span class="co-badge {{ $fila->zona === 'RM' ? 'co-badge-zona-rm' : 'co-badge-zona-regiones' }}">{{ $fila->zona }}</span>
                                        @else
                                            <span class="co-badge co-badge-zona-none">sin zona</span>
                                        @endif
                                    </td>
                                    <td class="is-num">
                                        @if($fila->pagar_retorno)
                                            ${{ number_format($fila->valor_retorno ?? 0, 0, ',', '.') }}
                                        @else
                                            <span class="is-muted">no</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="co-pagination">
                    <span>
                        Mostrando {{ $comunas->firstItem() }}–{{ $comunas->lastItem() }} de {{ number_format($comunas->total(), 0, ',', '.') }}
                    </span>
                    {{ $comunas->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </section>

</div>
@endsection
