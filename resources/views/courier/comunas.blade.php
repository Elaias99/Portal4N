@extends('layouts.app')

@vite('resources/css/courier.css')

@section('content')
<div class="co-page">

    @include('courier.partials.header', [
        'titulo' => 'Comunas y cobertura',
        'subtitulo' => 'Cada escritura de comuna que Geolice puede enviar, con el agente y la zona que le corresponde.',
    ])

    @include('courier.partials.nav', ['activo' => 'comunas'])

    <section class="co-region">
        <form method="GET" action="{{ route('courier.comunas') }}" class="co-filters" role="search">
            <div class="co-field co-span-5">
                <label for="q">Comuna o agente</label>
                <input type="search" id="q" name="q" class="form-control"
                       value="{{ $buscar }}" placeholder="Curacaví, Las Condes, 4N RM…" autocomplete="off" autofocus>
            </div>
            <div class="co-field co-span-3">
                <label for="zona">Zona</label>
                <select id="zona" name="zona" class="form-select">
                    <option value="">Todas</option>
                    <option value="RM" @selected($zona === 'RM')>RM</option>
                    <option value="Regiones" @selected($zona === 'Regiones')>Regiones</option>
                    <option value="sin_zona" @selected($zona === 'sin_zona')>Sin zona</option>
                </select>
            </div>
            <div class="co-span-2">
                <button type="submit" class="co-btn co-btn-primary w-100">Buscar</button>
            </div>
            @if($buscar !== '' || $zona !== '')
                <div class="co-span-2">
                    <a href="{{ route('courier.comunas') }}" class="co-btn co-btn-muted w-100">Limpiar</a>
                </div>
            @endif
        </form>

        <div class="co-region-head">
            <h2 class="co-region-title">
                {{ number_format($comunas->total(), 0, ',', '.') }}
                {{ $comunas->total() === 1 ? 'comuna' : 'comunas' }}
                @if($buscar !== '')
                    para "{{ $buscar }}"
                @endif
            </h2>
            <span class="co-region-meta">
                Una misma comuna puede aparecer con varias escrituras: cada una es una fila, porque así la envía Geolice.
            </span>
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
                                <th class="is-mono">Clave de búsqueda</th>
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
                                    <td class="is-mono is-muted">{{ $fila->localidad_clave }}</td>
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
