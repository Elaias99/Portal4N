@extends('layouts.app')

@vite('resources/css/courier.css')

@section('content')
@php
    use App\Services\Courier\CourierCalculoService;

    $n = fn ($v) => number_format((int) ($v ?? 0), 0, ',', '.');

    $motivos = $resumenPago['motivos'] ?? [];
    $fuera = array_sum($motivos);
    $dentro = (int) ($resumenPago['bultos_pagados'] ?? 0);
    $calculados = (int) ($resumenPago['bultos_calculados'] ?? 0);

    /*
     * Los motivos que son una decisión ya tomada se separan de los que
     * siguen esperando que Operaciones defina algo.
     */
    $esperanDefinicion = ['sin_comuna', 'comuna_desconocida', 'sin_configuracion', 'sin_tabla', 'sin_tarifa', 'configuracion_revisar'];
@endphp

<div class="co-page co-page-paso">

    @include('courier.partials.paso-cabeza')

        @if(! $resumenPago)
            <p class="co-empty">Este período todavía no se ha calculado.</p>
        @else

            <p class="co-paso-dato">
                <span class="co-paso-numero">{{ $n($dentro) }}</span>
                <span class="co-paso-unidad">bultos entran al pago, de {{ $n($calculados) }}</span>
            </p>

            <p class="co-paso-frase">
                Quedan fuera <strong>{{ $n($fuera) }}</strong> bultos. Cada uno tiene un motivo, y no todos
                significan lo mismo: unos responden a una regla que ya está decidida, y otros a algo que
                todavía nadie definió.
            </p>

            <div class="co-paso-bloque">
                <h2 class="co-paso-subtitulo">Por qué quedan fuera</h2>

                <div class="co-table-wrap">
                    <table class="co-table">
                        <thead>
                            <tr>
                                <th>Motivo</th>
                                <th class="is-num">Bultos</th>
                                <th>Qué significa</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($motivos as $motivo => $cantidad)
                                <tr>
                                    <td class="is-strong">{{ CourierCalculoService::MOTIVOS[$motivo] ?? $motivo }}</td>
                                    <td class="is-num">{{ $n($cantidad) }}</td>
                                    <td>
                                        @if(in_array($motivo, $esperanDefinicion, true))
                                            <span class="co-badge co-badge-revisar">Falta que Operaciones lo defina</span>
                                        @else
                                            <span class="is-muted">Regla ya definida</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        @endif

    @include('courier.partials.paso-pie')

</div>
@endsection
