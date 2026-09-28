{{--
    Navegación entre las vistas de un mismo período.
    $activo = resumen | distribucion | pendientes
    $periodo (CourierPeriodo|null), $periodos (Collection)
--}}
@php
    $codigo = $periodo?->codigo;

    $secciones = [
        'resumen' => ['ruta' => route('courier.index', $codigo ? ['periodo' => $codigo] : []), 'texto' => 'Resumen del período'],
        'pago' => ['ruta' => route('courier.pago', $codigo ? ['periodo' => $codigo] : []), 'texto' => 'Pago a proveedores'],
        'distribucion' => ['ruta' => route('courier.distribucion', $codigo ? ['periodo' => $codigo] : []), 'texto' => 'Distribución por agente'],
        'pendientes' => ['ruta' => route('courier.pendientes', $codigo ? ['periodo' => $codigo] : []), 'texto' => 'Pendientes'],
    ];
@endphp

<section class="co-periodo" aria-label="Período de pago">
    <form method="GET" action="{{ $secciones[$activo]['ruta'] }}" class="co-periodo-form">
        <label for="periodo">Período</label>
        <select id="periodo" name="periodo" class="form-select" onchange="this.form.submit()" @disabled($periodos->isEmpty())>
            @forelse($periodos as $opcion)
                <option value="{{ $opcion->codigo }}" @selected($periodo && $opcion->id === $periodo->id)>
                    {{ $opcion->nombre }}
                </option>
            @empty
                <option value="">Sin períodos</option>
            @endforelse
        </select>
        <noscript><button type="submit" class="co-btn co-btn-muted co-btn-sm">Ver</button></noscript>
    </form>

    <nav class="co-subnav" aria-label="Secciones del período">
        @foreach($secciones as $clave => $seccion)
            <a href="{{ $seccion['ruta'] }}" class="{{ $activo === $clave ? 'is-active' : '' }}">
                {{ $seccion['texto'] }}
            </a>
        @endforeach
    </nav>
</section>
