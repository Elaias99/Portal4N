@extends('layouts.app')

@vite('resources/css/courier.css')

@section('content')
@php
    $n = fn ($v) => number_format((int) ($v ?? 0), 0, ',', '.');

    $comunas = $alertas['comunas_fuera_de_catalogo'] ?? [];
    $ejemplos = $alertas['comunas_fuera_ejemplos'] ?? [];
    $sinConfiguracion = $alertas['sin_configuracion'] ?? [];
    $estados = $alertas['estados'] ?? [];
    $estadosSinRegla = $alertas['estados_desconocidos'] ?? [];
@endphp

<div class="co-page">

    @include('courier.partials.header', [
        'titulo' => 'Courier · Pendientes del período',
        'subtitulo' => 'Lo que el sistema no puede resolver solo. Cada punto necesita una decisión de Operaciones antes de calcular el pago.',
        'volverRuta' => route('courier.index'),
        'volverTexto' => 'Volver a Pago del mes',
        'meta' => 'Se recalcula cada vez que abres esta página',
    ])

    @include('courier.partials.nav', ['activo' => 'index'])

    @include('courier.partials.periodo-nav', ['activo' => 'pendientes'])

    @if(! $periodo || ! $alertas || $alertas['total'] === 0)
        <section class="co-region">
            <div class="co-region-body">
                <p class="co-empty">
                    Este período todavía no tiene bultos cargados.
                    <a href="{{ route('courier.index') }}">Importa la descarga de Geolice</a> para verlos aquí.
                </p>
            </div>
        </section>
    @else

        <div class="co-chips">
            <span class="co-chip"><strong>{{ $n($alertas['total']) }}</strong> bultos del período</span>
            <span class="co-chip {{ ($alertas['sin_comuna'] ?? 0) > 0 ? 'is-descontar' : 'is-ok' }}">
                <strong>{{ $n($alertas['sin_comuna'] ?? 0) }}</strong> sin comuna de destino
            </span>
            <span class="co-chip {{ $comunas !== [] ? 'is-descontar' : 'is-ok' }}">
                <strong>{{ $n($alertas['comunas_fuera_bultos'] ?? 0) }}</strong> con comuna no reconocida
            </span>
            <span class="co-chip {{ $sinConfiguracion !== [] ? 'is-descontar' : 'is-ok' }}">
                <strong>{{ $n($alertas['sin_configuracion_bultos'] ?? 0) }}</strong> sin configuración de pago
            </span>
        </div>

        {{-- ====== COMUNAS NO RECONOCIDAS ====== --}}
        <section class="co-region">
            <div class="co-region-head">
                <div>
                    <h2 class="co-region-title">
                        Comunas no reconocidas
                        <span class="co-badge {{ $comunas !== [] ? 'co-badge-revisar' : 'co-badge-si' }}">
                            {{ $n(count($comunas)) }}
                        </span>
                    </h2>
                    <p class="co-note">
                        Geolice envía un nombre de comuna que no está en el catálogo. Sin comuna reconocida no hay agente,
                        y sin agente el bulto no se puede pagar. Casi siempre son tildes, mayúsculas o abreviaturas.
                    </p>
                </div>
                <a href="{{ route('courier.comunas') }}" class="co-btn co-btn-muted co-btn-sm">Ver catálogo de comunas</a>
            </div>

            @if($comunas === [])
                <div class="co-region-body"><p class="co-note">Todas las comunas del período existen en el catálogo.</p></div>
            @else
                <div class="co-table-wrap">
                    <table class="co-table">
                        <thead>
                            <tr>
                                <th>Comuna tal como la envía Geolice</th>
                                <th class="is-num">Bultos</th>
                                <th>De qué envíos se trata</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($comunas as $comuna => $cantidad)
                                <tr>
                                    <td class="is-mono is-strong">{{ $comuna }}</td>
                                    <td class="is-num">{{ $n($cantidad) }}</td>
                                    <td>
                                        @forelse($ejemplos[$comuna] ?? [] as $ejemplo)
                                            <span class="co-comuna-ejemplo">
                                                <strong>{{ $ejemplo['comerciante'] }}</strong>
                                                · {{ $ejemplo['servicio'] }}
                                                · {{ $ejemplo['direccion'] ?? 'sin dirección' }}
                                            </span>
                                        @empty
                                            <span class="is-muted">Sin ejemplos disponibles.</span>
                                        @endforelse
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="co-region-body">
                    <p class="co-note">
                        El valor de la izquierda es literalmente lo que envía Geolice. Cuando es un número o un texto
                        que no parece una comuna, el problema está en el origen: hay que avisarle a Operaciones para que
                        lo corrijan allá, o confirmar a qué comuna corresponde y agregar esa variante al catálogo.
                    </p>
                </div>
            @endif
        </section>

        {{-- ====== SIN CONFIGURACIÓN DE PAGO ====== --}}
        <section class="co-region">
            <div class="co-region-head">
                <div>
                    <h2 class="co-region-title">
                        Combinaciones sin configuración de pago
                        <span class="co-badge {{ $sinConfiguracion !== [] ? 'co-badge-revisar' : 'co-badge-si' }}">
                            {{ $n(count($sinConfiguracion)) }}
                        </span>
                    </h2>
                    <p class="co-note">
                        La combinación agente + cliente + servicio no existe en la configuración de pago.
                        <strong>No es tabla 0</strong>: tabla 0 significa que Operaciones decidió no pagar; esto significa
                        que nadie lo ha decidido todavía.
                    </p>
                </div>
            </div>

            @if($sinConfiguracion === [])
                <div class="co-region-body"><p class="co-note">Todas las combinaciones del período tienen configuración.</p></div>
            @else
                <div class="co-table-wrap">
                    <table class="co-table">
                        <thead>
                            <tr>
                                <th>Agente</th>
                                <th>Cliente</th>
                                <th>Servicio</th>
                                <th class="is-num">Bultos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sinConfiguracion as $combinacion => $cantidad)
                                @php $partes = array_map('trim', explode('|', $combinacion)); @endphp
                                <tr>
                                    <td class="is-strong">{{ $partes[0] ?? '—' }}</td>
                                    <td>{{ $partes[1] ?? '—' }}</td>
                                    <td>{{ $partes[2] ?? '—' }}</td>
                                    <td class="is-num">{{ $n($cantidad) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- ====== ESTADOS DE ENTREGA ====== --}}
        <section class="co-region">
            <div class="co-region-head">
                <div>
                    <h2 class="co-region-title">
                        Estados de entrega
                        <span class="co-badge {{ $estadosSinRegla !== [] ? 'co-badge-no' : 'co-badge-si' }}">
                            {{ $estadosSinRegla !== [] ? $n(count($estadosSinRegla)) . ' sin regla' : 'todos con regla' }}
                        </span>
                    </h2>
                    <p class="co-note">
                        Cada estado que informa Geolice debe existir en el catálogo con su regla: si el bulto se considera
                        para el pago o se descuenta.
                    </p>
                </div>
            </div>

            <div class="co-table-wrap">
                <table class="co-table">
                    <thead>
                        <tr>
                            <th>Estado</th>
                            <th class="is-num">Bultos</th>
                            <th>Regla en el catálogo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($estados as $estado)
                            <tr>
                                <td class="is-strong">{{ $estado['estado'] }}</td>
                                <td class="is-num">{{ $n($estado['bultos']) }}</td>
                                <td>
                                    @if($estado['en_catalogo'])
                                        <span class="co-badge {{ $estado['considerar'] === 'DESCONTAR' ? 'co-badge-no' : 'co-badge-si' }}">
                                            {{ $estado['considerar'] }}
                                        </span>
                                    @else
                                        <span class="co-badge co-badge-no">Sin regla</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="co-empty">Sin estados informados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    @endif

</div>
@endsection
