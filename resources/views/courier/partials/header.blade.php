{{--
    Cabecera del módulo Courier.
    $titulo, $subtitulo (opcional), $volverRuta, $volverTexto (opcionales)
--}}
@php
    $volverRuta = $volverRuta ?? route('courier.index');
    $volverTexto = $volverTexto ?? 'Volver a catálogos Courier';
@endphp

<header class="co-page-header">
    <a href="{{ $volverRuta }}" class="co-back-link">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        <span>{{ $volverTexto }}</span>
    </a>

    <div>
        <h1 class="co-page-title">{{ $titulo }}</h1>
        @if(! empty($subtitulo))
            <p class="co-page-subtitle">{{ $subtitulo }}</p>
        @endif
    </div>

    <div class="co-header-meta">
        Catálogos cargados desde la planilla de Operaciones
    </div>
</header>
