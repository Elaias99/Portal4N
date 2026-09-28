@extends('layouts.app')

@vite('resources/css/courier.css')

@section('content')
@php
    $n = fn ($v) => number_format((int) ($v ?? 0), 0, ',', '.');

    $bloques = $sinResolver['bloques'];
    $ejemplos = $sinResolver['ejemplos'];

    $vivos = (int) $sinResolver['vivos'];
    $conProblema = (int) $sinResolver['bultos'];
    $sinEfecto = $conProblema - $vivos;

    /*
     * Cada bloque lleva su texto propio. El orden en pantalla no es fijo:
     * manda cuántos bultos iban a pagarse, porque eso es lo que cuesta.
     */
    $textos = [
        'sin_comuna' => [
            'titulo' => 'Sin comuna de destino',
            'que_pasa' => 'Geolice no informó la comuna. Sin comuna no hay agente, y sin agente no hay a quién pagarle.',
            'quien' => 'Hay que avisarle al comerciante: el dato sale mal desde el origen.',
        ],
        'comuna_desconocida' => [
            'titulo' => 'La comuna no está en el catálogo',
            'que_pasa' => 'Llegó un texto en el campo comuna que el catálogo no reconoce.',
            'quien' => 'Dos escrituras parecidas pueden ser comunas distintas y pagarle a agentes distintos, así que ninguna se relaciona automáticamente: cada una se confirma a mano.',
        ],
        'sin_configuracion' => [
            'titulo' => 'Nadie ha definido si se paga',
            'que_pasa' => 'La combinación agente + cliente + servicio no existe en la configuración de pago. No es que se decidiera no pagar: es que no está decidido.',
            'quien' => 'Lo define Operaciones, y mientras no lo haga estos bultos no se pueden calcular.',
        ],
    ];

    $orden = collect($bloques)
        ->map(fn ($b, $clave) => $b + ['clave' => $clave])
        ->filter(fn ($b) => $b['bultos'] > 0)
        ->sortByDesc(fn ($b) => [$b['vivos'], $b['bultos']])
        ->values();
@endphp

<div class="co-page co-page-paso">

    @include('courier.partials.paso-cabeza')

        <p class="co-paso-dato">
            <span class="co-paso-numero {{ $vivos > 0 ? 'is-atencion' : '' }}">{{ $n($vivos) }}</span>
            <span class="co-paso-unidad">bultos sin resolver que sí se iban a pagar</span>
        </p>

        @if($conProblema === 0)
            <p class="co-paso-frase">
                Todo lo que trajo el archivo calza con los catálogos. Puedes seguir.
            </p>
        @else
            <p class="co-paso-frase">
                De los {{ $n($sinResolver['total']) }} bultos cargados, <strong>{{ $n($conProblema) }}</strong>
                tienen algo sin resolver.
                @if($sinEfecto > 0)
                    Pero <strong>{{ $n($sinEfecto) }}</strong> de ellos no se iban a pagar igual, porque su estado de
                    entrega ya los descarta ({{ implode(', ', $sinResolver['estados_que_descartan']) }}).
                    Los que importan son los otros <strong>{{ $n($vivos) }}</strong>.
                @endif
            </p>

            @foreach($orden as $bloque)
                @php
                    $t = $textos[$bloque['clave']];
                    $sinCosto = $bloque['vivos'] === 0;
                @endphp

                <div class="co-paso-bloque {{ $sinCosto ? 'is-sin-costo' : '' }}">

                    <h2 class="co-paso-subtitulo">
                        {{ $t['titulo'] }}
                        <span class="co-paso-marca {{ $sinCosto ? 'is-quieto' : 'is-atencion' }}">
                            @if($sinCosto)
                                {{ $n($bloque['bultos']) }} bultos · ninguno se iba a pagar
                            @else
                                {{ $n($bloque['vivos']) }} de {{ $n($bloque['bultos']) }} bultos se iban a pagar
                            @endif
                        </span>
                    </h2>

                    <p class="co-note">{{ $t['que_pasa'] }}</p>

                    @if($sinCosto)
                        <p class="co-note">
                            Hoy no cuesta plata: todos estos bultos vienen con un estado que ya los deja fuera del pago.
                            Queda anotado por si más adelante llega uno en un estado que sí se paga.
                        </p>
                    @else
                        <p class="co-note">{{ $t['quien'] }}</p>
                    @endif

                    @if($bloque['detalle'] !== [])
                        <div class="co-table-wrap">
                            <table class="co-table">
                                <thead>
                                    <tr>
                                        @if($bloque['clave'] === 'comuna_desconocida')
                                            <th>Tal como lo manda Geolice</th>
                                            <th class="is-num">Bultos</th>
                                            <th class="is-num">Se iban a pagar</th>
                                            <th>De qué envíos se trata</th>
                                        @else
                                            <th>Agente</th>
                                            <th>Cliente</th>
                                            <th>Servicio</th>
                                            <th class="is-num">Bultos</th>
                                            <th class="is-num">Se iban a pagar</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($bloque['detalle'] as $fila)
                                        <tr class="{{ $fila['vivos'] === 0 ? 'is-quieto' : '' }}">
                                            @if($bloque['clave'] === 'comuna_desconocida')
                                                <td class="is-mono is-strong">{{ $fila['etiqueta'] }}</td>
                                                <td class="is-num">{{ $n($fila['bultos']) }}</td>
                                                <td class="is-num {{ $fila['vivos'] > 0 ? 'is-strong' : 'is-muted' }}">
                                                    {{ $fila['vivos'] > 0 ? $n($fila['vivos']) : '—' }}
                                                </td>
                                                <td>
                                                    @forelse($ejemplos[$fila['etiqueta']] ?? [] as $ejemplo)
                                                        <span class="co-comuna-ejemplo">
                                                            <strong>{{ $ejemplo['comerciante'] }}</strong>
                                                            · {{ $ejemplo['servicio'] }}
                                                            · {{ $ejemplo['direccion'] ?? 'sin dirección' }}
                                                        </span>
                                                    @empty
                                                        <span class="is-muted">Sin ejemplos.</span>
                                                    @endforelse
                                                </td>
                                            @else
                                                @php $partes = array_map('trim', explode('|', $fila['etiqueta'])); @endphp
                                                <td class="is-strong">{{ $partes[0] ?? '—' }}</td>
                                                <td>{{ $partes[1] ?? '—' }}</td>
                                                <td>{{ $partes[2] ?? '—' }}</td>
                                                <td class="is-num">{{ $n($fila['bultos']) }}</td>
                                                <td class="is-num {{ $fila['vivos'] > 0 ? 'is-strong' : 'is-muted' }}">
                                                    {{ $fila['vivos'] > 0 ? $n($fila['vivos']) : '—' }}
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                </div>
            @endforeach
        @endif

    @include('courier.partials.paso-pie')

</div>
@endsection
