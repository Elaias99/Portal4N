@extends('layouts.app')

@vite('resources/css/courier.css')

@section('content')
@php
    $n = fn ($v) => number_format((int) ($v ?? 0), 0, ',', '.');
    $f = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->format('d-m-Y') : null;

    $mesDelPeriodo = sprintf('%04d-%02d', $periodo->anio, $periodo->mes);

    /* El mes que se paga tiene que estar entre lo que trae el archivo. */
    $mesesCargados = collect($archivo['meses'] ?? []);
    $traeElMes = $mesesCargados->contains(fn ($m) => $m['mes'] === $mesDelPeriodo);
@endphp

<div class="co-page co-page-paso">

    @include('courier.partials.paso-cabeza')

        @if($archivo['bultos'] === 0)
            <p class="co-empty">
                Este período todavía no tiene bultos.
                <a href="{{ route('courier.index', ['periodo' => $periodo->codigo]) }}">Carga la descarga de Geolice</a>.
            </p>
        @else

            <p class="co-paso-dato">
                <span class="co-paso-numero">{{ $n($archivo['bultos']) }}</span>
                <span class="co-paso-unidad">bultos cargados</span>
            </p>

            <p class="co-paso-frase">
                Vienen del archivo <strong>{{ $archivo['archivo'] ?? 'cargado desde la terminal' }}</strong>@if($archivo['importado_at']),
                importado el {{ $archivo['importado_at']->format('d-m-Y') }} a las {{ $archivo['importado_at']->format('H:i') }}@endif.
                @if($f($archivo['desde']) && $f($archivo['hasta']))
                    Se recibieron entre el <strong>{{ $f($archivo['desde']) }}</strong> y el <strong>{{ $f($archivo['hasta']) }}</strong>.
                @endif
            </p>

            @if($mesesCargados->isNotEmpty())
                <div class="co-paso-bloque">
                    <h2 class="co-paso-subtitulo">En qué meses se recibieron</h2>

                    <table class="co-table">
                        <thead>
                            <tr>
                                <th>Mes</th>
                                <th class="is-num">Bultos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mesesCargados as $mes)
                                <tr class="{{ $mes['mes'] === $mesDelPeriodo ? 'is-selected' : '' }}">
                                    <td class="is-strong">{{ $mes['mes'] }}</td>
                                    <td class="is-num">{{ $n($mes['bultos']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if(! $traeElMes)
                <div class="co-alert co-alert-danger" role="alert">
                    <strong>Revisa antes de seguir.</strong>
                    Estás pagando {{ $periodo->nombre }}, y este archivo no trae bultos recibidos en ese mes.
                    Puede que sea la descarga equivocada.
                </div>
            @endif

        @endif

    @include('courier.partials.paso-pie')

</div>
@endsection
