@extends('layouts.app')

@vite('resources/css/courier.css')

@section('content')
@php
    $n = fn ($v) => number_format((int) ($v ?? 0), 0, ',', '.');
    $pct = fn ($parte, $total) => $total > 0 ? round($parte * 100 / $total, 1) : 0;
@endphp

<div class="co-page">

    @include('courier.partials.header', [
        'titulo' => 'Courier · Distribución por agente',
        'subtitulo' => 'A qué agente le corresponde cada bulto del período, según la comuna de destino.',
        'volverRuta' => route('courier.index'),
        'volverTexto' => 'Volver a Pago del mes',
        'meta' => 'Reparto de bultos · aún sin montos',
    ])

    @include('courier.partials.nav', ['activo' => 'index'])

    @include('courier.partials.periodo-nav', ['activo' => 'distribucion'])

    @if(! $periodo || ! $distribucion || $distribucion['total'] === 0)
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
            <span class="co-chip"><strong>{{ $n($distribucion['total']) }}</strong> bultos del período</span>
            <span class="co-chip is-ok"><strong>{{ $n($distribucion['con_agente']) }}</strong> con agente identificado</span>
            @if($distribucion['comuna_no_reconocida'] > 0)
                <span class="co-chip is-descontar"><strong>{{ $n($distribucion['comuna_no_reconocida']) }}</strong> con comuna no reconocida</span>
            @endif
            @if($distribucion['sin_comuna'] > 0)
                <span class="co-chip is-descontar"><strong>{{ $n($distribucion['sin_comuna']) }}</strong> sin comuna de destino</span>
            @endif
        </div>

        <p class="co-note" style="margin-bottom: 1rem;">
            Esta pantalla cuenta bultos, no pesos. Los montos aparecen cuando se ejecute el cálculo del período.
            La columna «con estado pagable» solo aplica la regla de estado de entrega; no reemplaza al cálculo completo.
        </p>

        @foreach($distribucion['zonas'] as $zona)
            <section class="co-region">
                <div class="co-region-head">
                    <div>
                        <h2 class="co-region-title">{{ $zona['zona'] }}</h2>
                        <p class="co-note">
                            {{ count($zona['agentes']) }} {{ count($zona['agentes']) === 1 ? 'agente' : 'agentes' }}
                            · {{ $n($zona['bultos']) }} bultos
                        </p>
                    </div>
                    <span class="co-region-meta">
                        {{ $pct($zona['bultos'], $distribucion['total']) }}% del período
                    </span>
                </div>

                <div class="co-table-wrap">
                    <table class="co-table">
                        <thead>
                            <tr>
                                <th>Agente</th>
                                <th class="is-num">Bultos</th>
                                <th class="is-num">Con estado pagable</th>
                                <th class="is-num">Con estado que descuenta</th>
                                <th class="is-num">Peso en la zona</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($zona['agentes'] as $agente)
                                <tr>
                                    <td class="is-strong">
                                        <a href="{{ route('courier.agentes.show', ['agente' => $agente['agente_id']]) }}">
                                            {{ $agente['agente'] }}
                                        </a>
                                    </td>
                                    <td class="is-num">{{ $n($agente['bultos']) }}</td>
                                    <td class="is-num">{{ $n($agente['con_estado_pagable']) }}</td>
                                    <td class="is-num is-muted">{{ $n($agente['con_estado_descontado']) }}</td>
                                    <td class="is-num">
                                        <span class="co-barra" role="img"
                                              aria-label="{{ $pct($agente['bultos'], $zona['bultos']) }} por ciento de la zona">
                                            <span style="width: {{ $pct($agente['bultos'], $zona['bultos']) }}%"></span>
                                        </span>
                                        {{ $pct($agente['bultos'], $zona['bultos']) }}%
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach

        @if($distribucion['comunas_no_reconocidas'] !== [])
            <section class="co-region">
                <div class="co-region-head">
                    <div>
                        <h2 class="co-region-title">Bultos que no llegaron a ningún agente</h2>
                        <p class="co-note">Su comuna de destino no está en el catálogo, así que no se sabe quién los reparte.</p>
                    </div>
                    <a href="{{ route('courier.pendientes', ['periodo' => $periodo->codigo]) }}" class="co-btn co-btn-muted co-btn-sm">
                        Ver todos los pendientes
                    </a>
                </div>

                <div class="co-table-wrap">
                    <table class="co-table">
                        <thead>
                            <tr>
                                <th>Comuna tal como la envía Geolice</th>
                                <th class="is-num">Bultos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($distribucion['comunas_no_reconocidas'] as $comuna => $cantidad)
                                <tr>
                                    <td class="is-mono">{{ $comuna }}</td>
                                    <td class="is-num">{{ $n($cantidad) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

    @endif

</div>
@endsection
