@extends('layouts.app')

@vite('resources/css/courier.css')

@section('content')
@php
    $n = fn ($v) => number_format((int) ($v ?? 0), 0, ',', '.');

    $porDefecto = collect($pesos['grupos'] ?? [])->firstWhere('origen', 'x');
    $bultos = (int) ($pesos['bultos'] ?? 0);
@endphp

<div class="co-page co-page-paso">

    @include('courier.partials.paso-cabeza')

        @if(! $pesos || $bultos === 0)
            <p class="co-empty">Este período todavía no tiene bultos con pago calculado.</p>
        @else

            <p class="co-paso-dato">
                <span class="co-paso-numero {{ ($porDefecto['bultos'] ?? 0) > 0 ? 'is-atencion' : '' }}">
                    {{ $n($porDefecto['bultos'] ?? 0) }}
                </span>
                <span class="co-paso-unidad">bultos se pagaron con 1 kilo porque no había peso</span>
            </p>

            <p class="co-paso-frase">
                La tarifa se cobra por kilo, así que el peso es plata. Cuando el bulto no tiene peso de balanza
                se usa el que declaró el cliente, y si tampoco hay, se paga un kilo.
                @if(($porDefecto['bultos'] ?? 0) > 0)
                    Esos {{ $n($porDefecto['bultos']) }} bultos son los que más conviene revisar con bodega.
                @endif
            </p>

            <div class="co-paso-bloque">
                <h2 class="co-paso-subtitulo">De dónde salió el peso de los {{ $n($bultos) }} bultos que se pagan</h2>

                <div class="co-table-wrap">
                    <table class="co-table">
                        <thead>
                            <tr>
                                <th>Origen del peso</th>
                                <th class="is-num">Bultos</th>
                                <th class="is-num">Kilos</th>
                                <th class="is-num">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pesos['grupos'] as $grupo)
                                <tr class="{{ $grupo['origen'] === 'x' && $grupo['bultos'] > 0 ? 'is-selected' : '' }}">
                                    <td class="is-strong">{{ $grupo['nombre'] }}</td>
                                    <td class="is-num">{{ $n($grupo['bultos']) }}</td>
                                    <td class="is-num">{{ $n($grupo['kilos']) }}</td>
                                    <td class="is-num">${{ $n($grupo['monto']) }}</td>
                                </tr>
                            @endforeach
                            <tr class="co-fila-total">
                                <td class="is-strong">Total</td>
                                <td class="is-num">{{ $n($bultos) }}</td>
                                <td class="is-num">{{ $n(collect($pesos['grupos'])->sum('kilos')) }}</td>
                                <td class="is-num is-strong">${{ $n($pesos['monto']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        @endif

    @include('courier.partials.paso-pie')

</div>
@endsection
