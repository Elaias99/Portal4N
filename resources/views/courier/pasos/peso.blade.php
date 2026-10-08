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

        @if(session('pesosCargados'))
            @php $c = session('pesosCargados'); @endphp
            <div class="co-alert co-alert-ok" role="status">
                <strong>Pesos cargados de {{ $c['archivo'] }}.</strong>
                {{ $n($c['filas'] ?? 0) }} pesos leídos
                ({{ $n($c['nuevos'] ?? 0) }} nuevos · {{ $n($c['actualizados'] ?? 0) }} actualizados · {{ $n($c['sin_cambio'] ?? 0) }} sin cambio)
                · {{ $n($c['en_cero'] ?? 0) }} en 0 kg
                · {{ $n($c['descartados'] ?? 0) }} descartados.
                {{ $n($c['bultos_con_peso']) }} bultos de este período ya tienen peso de balanza.
                Ahora aprieta <strong>Calcular de nuevo</strong> para que el total use estos pesos.
            </div>
        @elseif(session('calculoListo'))
            @php $c = session('calculoListo'); @endphp
            <div class="co-alert co-alert-ok" role="status">
                <strong>Cálculo terminado.</strong>
                {{ $n($c['pagar']) }} bultos se pagan y {{ $n($c['descontar']) }} quedan fuera.
            </div>
        @endif

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

        <div class="co-paso-bloque">
            <h2 class="co-paso-subtitulo">Subir pesos</h2>

            <p class="co-note">
                Excel con las columnas
                <strong>Codigo_S+Bulto · Notas · Cod_seguimiento · Fecha de maestro · Comerciante · Servicio</strong>,
                en la primera hoja. <strong>Notas</strong> son los kilos.
            </p>

            @if($errors->has('archivo'))
                <div class="co-alert co-alert-danger" role="alert">{{ $errors->first('archivo') }}</div>
            @endif

            <form method="POST" action="{{ route('courier.importar-pesos') }}" enctype="multipart/form-data" data-subir>
                @csrf
                <input type="hidden" name="periodo" value="{{ $periodo->codigo }}">
                <div class="co-field">
                    <label for="archivo-pesos">Archivo de pesos</label>
                    <input type="file" id="archivo-pesos" name="archivo" class="form-control" accept=".xlsx" required>
                </div>
                <button type="submit" class="co-btn co-btn-primary" data-loading-text="Subiendo…" style="margin-top:.75rem">Subir pesos</button>
            </form>

            @if(session('pesosCargados'))
                <form method="POST" action="{{ route('courier.calcular') }}" data-subir style="margin-top:.75rem">
                    @csrf
                    <input type="hidden" name="periodo" value="{{ $periodo->codigo }}">
                    <input type="hidden" name="volver" value="peso">
                    <button type="submit" class="co-btn co-btn-primary" data-loading-text="Calculando…">Calcular de nuevo</button>
                </form>
            @endif
        </div>

    @include('courier.partials.paso-pie')

</div>
@endsection

@push('scripts')
<script>
(function () {
    document.querySelectorAll('[data-subir]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var boton = form.querySelector('button[type="submit"]');

            if (boton) {
                boton.disabled = true;
                boton.textContent = boton.getAttribute('data-loading-text') || 'Procesando…';
            }
        });
    });
})();
</script>
@endpush
