@extends('layouts.app')

@vite('resources/css/courier.css')

@section('content')
@php
    $n = fn ($v) => number_format((int) ($v ?? 0), 0, ',', '.');
    $totalExtras = collect($extras)->sum('total');
@endphp

<div class="co-page co-page-paso">

    @include('courier.partials.paso-cabeza')

        @if(session('extraCargado'))
            @php $c = session('extraCargado'); @endphp
            <div class="co-alert co-alert-ok" role="status">
                <strong>{{ $c['nombre'] }} cargado de {{ $c['archivo'] }}:</strong>
                {{ $n($c['filas']) }} filas por ${{ $n($c['total']) }}.
                @if($c['sin_proveedor'] > 0)
                    {{ $n($c['sin_proveedor']) }} filas no encontraron su proveedor por RUT.
                @endif
            </div>
        @endif

        <p class="co-paso-dato">
            <span class="co-paso-numero">${{ $n($totalExtras) }}</span>
            <span class="co-paso-unidad">en pagos extra cargados en {{ $periodo->nombre }}</span>
        </p>

        <p class="co-paso-frase">
            Sube el archivo de cada pago. Cada archivo reemplaza lo que ese pago tenía en el mes,
            así que se puede volver a subir si viene corregido.
        </p>

        <div class="co-paso-bloque">
            <div class="co-table-wrap">
                <table class="co-table">
                    <thead>
                        <tr>
                            <th>Pago</th>
                            <th class="is-num">Filas</th>
                            <th class="is-num">Monto</th>
                            <th>Archivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($extras as $clave => $extra)
                            <tr>
                                <td class="is-strong">{{ $extra['nombre'] }}</td>
                                <td class="is-num">{{ $extra['filas'] > 0 ? $n($extra['filas']) : '—' }}</td>
                                <td class="is-num">{{ $extra['filas'] > 0 ? '$' . $n($extra['total']) : 'Sin cargar' }}</td>
                                <td>
                                    @if($clave === 'apoyo-alza')
                                        <span class="co-note">Se calcula solo con las <a href="{{ route('courier.apoyo-alza') }}">reglas de Apoyo Alza</a>.</span>
                                    @else
                                        @if($extra['archivo'])
                                            <div class="co-note">{{ $extra['archivo'] }}</div>
                                        @endif

                                        @if($errors->has("archivo_{$clave}"))
                                            <div class="co-alert co-alert-danger" role="alert">{{ $errors->first("archivo_{$clave}") }}</div>
                                        @endif

                                        <form method="POST" action="{{ route('courier.importar-extra') }}" enctype="multipart/form-data"
                                              data-subir style="display:flex; gap:.5rem; align-items:center; margin-top:.25rem">
                                            @csrf
                                            <input type="hidden" name="periodo" value="{{ $periodo->codigo }}">
                                            <input type="hidden" name="tipo" value="{{ $clave }}">
                                            <input type="file" name="archivo" class="form-control form-control-sm" accept=".xlsx" required
                                                   aria-label="Archivo de {{ $extra['nombre'] }}">
                                            <button type="submit" class="co-btn co-btn-primary co-btn-sm" data-loading-text="Subiendo…">Subir</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        <tr class="co-fila-total">
                            <td class="is-strong">Total</td>
                            <td></td>
                            <td class="is-num is-strong">${{ $n($totalExtras) }}</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
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
