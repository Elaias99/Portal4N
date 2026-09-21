{{--
    Resultado de una carga de pesajes de bodega (uno o varios CSV),
    tarjeta por tarjeta. $cargas (Collection de CourierImportacion tipo
    pesajes), $periodo.
--}}
@php
    $n = fn ($v) => number_format((int) $v, 0, ',', '.');
    $pct = fn ($parte, $total) => $total > 0 ? (int) round($parte * 100 / $total) : 0;
    $fechaCorta = fn ($ymd) => $ymd ? \Carbon\Carbon::parse($ymd)->format('d-m-Y') : '—';

    $suma = [
        'filas' => 0, 'nuevos' => 0, 'actualizados' => 0, 'sin_cambio' => 0,
        'kilos_cero' => 0, 'codigos_invalidos' => 0, 'kilos_invalidos' => 0,
        'repetidos_en_archivo' => 0, 'con_bulto' => 0, 'sin_bulto' => 0,
    ];
    $porDia = [];
    $invalidos = [];

    foreach ($cargas as $carga) {
        $c = $carga->conteos();
        foreach ($suma as $k => $v) {
            $suma[$k] += (int) ($c[$k] ?? 0);
        }
        $porDia[] = [
            'fecha' => $carga->resumen['fecha_pesaje'] ?? null,
            'archivo' => $carga->archivo,
            'filas' => (int) ($c['filas'] ?? 0),
            'nuevos' => (int) ($c['nuevos'] ?? 0),
            'actualizados' => (int) ($c['actualizados'] ?? 0),
            'sin_cambio' => (int) ($c['sin_cambio'] ?? 0),
        ];
        foreach ($carga->resumen['codigos_invalidos'] ?? [] as $texto => $veces) {
            $invalidos[$texto] = ($invalidos[$texto] ?? 0) + (int) $veces;
        }
    }

    usort($porDia, fn ($a, $b) => strcmp((string) $a['fecha'], (string) $b['fecha']));

    $pesados = $suma['con_bulto'] + $suma['sin_bulto'];
    $primera = $cargas->first();
    $hayAvisos = $suma['kilos_cero'] > 0 || $suma['codigos_invalidos'] > 0 || $suma['kilos_invalidos'] > 0 || $suma['repetidos_en_archivo'] > 0;
@endphp

<section class="co-slider" id="resultado" aria-label="Resultado de la carga de pesajes" data-slider>

    <div class="co-slider-head">
        <div>
            <h2 class="co-region-title">Resultado de la carga de pesajes</h2>
            <p class="co-slider-sub">
                {{ $cargas->count() === 1 ? '1 archivo' : $cargas->count() . ' archivos' }}
                · {{ $periodo->nombre }}
                · {{ $primera->created_at->format('d-m-Y H:i') }}
                · {{ $primera->usuario?->name ?? 'Terminal' }}
            </p>
        </div>
        <div class="co-slider-nav">
            <button type="button" class="co-btn co-btn-muted co-btn-sm" data-prev>Anterior</button>
            <span class="co-slider-count"><span data-current>1</span> / <span data-total>1</span></span>
            <button type="button" class="co-btn co-btn-muted co-btn-sm" data-next>Siguiente</button>
        </div>
    </div>

    <div class="co-slider-track" tabindex="0" data-track>

        {{-- 1 · Portada --}}
        <article class="co-slide co-slide-hero">
            <span class="co-slide-eyebrow">Pesajes cargados</span>
            <span class="co-slide-value">{{ $n($suma['filas']) }}</span>
            <h3 class="co-slide-title">
                pesajes leídos de {{ count($porDia) === 1 ? 'un día' : count($porDia) . ' días' }} de bodega
            </h3>
            <p class="co-slide-text">
                Cada bulto queda con su peso de balanza y la fecha en que se pesó.
                Desliza hacia la derecha para ver el detalle por día y el cruce con los bultos de Geolice.
            </p>
            <span class="co-slide-foot">
                @foreach($porDia as $dia){{ $fechaCorta($dia['fecha']) }}@if(! $loop->last) · @endif @endforeach
            </span>
        </article>

        {{-- 2 · Por día --}}
        <article class="co-slide is-accent">
            <span class="co-slide-eyebrow">Por día</span>
            <div class="co-slide-tiles {{ count($porDia) > 4 ? 'is-dense' : '' }}">
                @foreach($porDia as $dia)
                    <div class="co-tile">
                        <span class="co-tile-value">{{ $n($dia['filas']) }}</span>
                        <span class="co-tile-label">{{ $fechaCorta($dia['fecha']) }}</span>
                        <span class="co-tile-text">
                            {{ $n($dia['nuevos']) }} nuevos
                            @if($dia['actualizados'] > 0) · {{ $n($dia['actualizados']) }} actualizados @endif
                            @if($dia['sin_cambio'] > 0) · {{ $n($dia['sin_cambio']) }} sin cambio @endif
                        </span>
                    </div>
                @endforeach
            </div>
            <p class="co-slide-text">
                "Sin cambio" y "actualizados" aparecen cuando se vuelve a cargar un día que ya estaba: nada se duplica.
            </p>
        </article>

        {{-- 3 · Cruce con los bultos --}}
        <article class="co-slide {{ $suma['sin_bulto'] === 0 ? 'is-ok' : 'is-neutral' }}">
            <span class="co-slide-eyebrow">Cruce con Geolice</span>
            <span class="co-slide-value">{{ $pct($suma['con_bulto'], $pesados) }}<small>%</small></span>
            <h3 class="co-slide-title">de los bultos pesados están en la descarga de Geolice</h3>
            <div class="co-bar" role="img" aria-label="{{ $n($suma['con_bulto']) }} con bulto, {{ $n($suma['sin_bulto']) }} sin bulto">
                <div class="co-bar-fill" style="width: {{ $pct($suma['con_bulto'], $pesados) }}%"></div>
            </div>
            <div class="co-bar-legend">
                <span><strong>{{ $n($suma['con_bulto']) }}</strong> con bulto</span>
                <span><strong>{{ $n($suma['sin_bulto']) }}</strong> sin bulto todavía</span>
            </div>
            <p class="co-slide-text">
                Los que no tienen bulto no se pierden: quedan guardados y se cruzan solos cuando ese bulto llegue en una descarga posterior.
            </p>
        </article>

        {{-- 4 · Avisos --}}
        <article class="co-slide {{ $hayAvisos ? 'is-warn' : 'is-ok' }}">
            <span class="co-slide-eyebrow">Avisos</span>
            <div class="co-slide-tiles">
                <div class="co-tile {{ $suma['kilos_cero'] > 0 ? 'is-warn' : '' }}">
                    <span class="co-tile-value">{{ $n($suma['kilos_cero']) }}</span>
                    <span class="co-tile-label">Pesajes en 0 kg</span>
                    <span class="co-tile-text">Pasaron por la balanza sin peso. Se guardan, pero no sirven como peso de pago.</span>
                </div>
                <div class="co-tile {{ $suma['codigos_invalidos'] + $suma['kilos_invalidos'] > 0 ? 'is-warn' : '' }}">
                    <span class="co-tile-value">{{ $n($suma['codigos_invalidos'] + $suma['kilos_invalidos']) }}</span>
                    <span class="co-tile-label">Filas descartadas</span>
                    <span class="co-tile-text">
                        Código o kilos ilegibles.
                        @foreach(array_slice($invalidos, 0, 3, true) as $texto => $veces)
                            "{{ $texto }}" ×{{ $veces }}@if(! $loop->last), @endif
                        @endforeach
                    </span>
                </div>
                <div class="co-tile {{ $suma['repetidos_en_archivo'] > 0 ? 'is-warn' : '' }}">
                    <span class="co-tile-value">{{ $n($suma['repetidos_en_archivo']) }}</span>
                    <span class="co-tile-label">Repetidos en el archivo</span>
                    <span class="co-tile-text">El mismo código dos veces en el mismo día; se tomó la última fila.</span>
                </div>
            </div>
            <p class="co-slide-text">
                Siguiente paso: calcular el pago. Ahí se decide, para cada bulto, qué peso manda cuando hay varios pesajes.
            </p>
        </article>

    </div>

    <div class="co-slider-dots" data-dots></div>
</section>
