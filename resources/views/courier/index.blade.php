@extends('layouts.app')

@vite('resources/css/courier.css')

@section('content')
@php
    $n = fn ($v) => number_format((int) $v, 0, ',', '.');
@endphp

<div class="co-page">

    @include('courier.partials.header', [
        'titulo' => 'Courier · Catálogos',
        'subtitulo' => 'Lo que la planilla de Operaciones sabe sobre agentes, comunas, tarifas y pagos, ahora dentro de Portal4N.',
        'volverRuta' => route('cobranzas.general'),
        'volverTexto' => 'Volver al panel de Finanzas',
    ])

    @include('courier.partials.nav', ['activo' => 'index'])

    {{-- ====== BUSCADOR PRINCIPAL ====== --}}
    <form method="GET" action="{{ route('courier.comunas') }}" class="co-search" role="search">
        <p class="co-search-label">¿Qué agente atiende esta comuna?</p>
        <div class="co-search-input">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" name="q" class="form-control"
                   placeholder="Escribe una comuna: Curacaví, Las Condes, Puerto Aysén…"
                   autocomplete="off" autofocus>
        </div>
        <button type="submit" class="co-btn co-btn-primary">Buscar</button>
        <p class="co-search-hint">
            Busca sin preocuparte de tildes ni mayúsculas. También encuentra por nombre de agente.
        </p>
    </form>

    {{-- ====== TARJETAS ====== --}}
    <div class="co-stats">

        <a href="{{ route('courier.agentes.index') }}" class="co-stat">
            <span class="co-stat-label"><i class="fa-solid fa-truck"></i> Agentes</span>
            <span class="co-stat-value">{{ $n($resumen['agentes']['total']) }}</span>
            <span class="co-stat-detail">
                @if($resumen['agentes']['varios_titulares'] > 0)
                    <strong>{{ $resumen['agentes']['varios_titulares'] }}</strong> con más de un titular ·
                @endif
                @if($resumen['agentes']['sin_comunas'] > 0)
                    <strong>{{ $resumen['agentes']['sin_comunas'] }}</strong> sin comunas
                @else
                    todos con cobertura
                @endif
            </span>
            <span class="co-stat-origin">Hoja <code>Operador</code></span>
        </a>

        <a href="{{ route('courier.comunas') }}" class="co-stat">
            <span class="co-stat-label"><i class="fa-solid fa-map-location-dot"></i> Comunas</span>
            <span class="co-stat-value">{{ $n($resumen['comunas']['total']) }}</span>
            <span class="co-stat-detail">
                <strong>{{ $n($resumen['comunas']['rm']) }}</strong> RM ·
                <strong>{{ $n($resumen['comunas']['regiones']) }}</strong> Regiones
                @if($resumen['comunas']['sin_zona'] > 0)
                    · <strong>{{ $resumen['comunas']['sin_zona'] }}</strong> sin zona
                @endif
                <br>{{ $n($resumen['comunas']['con_retorno']) }} pagan retorno
            </span>
            <span class="co-stat-origin">Hoja <code>Operador</code></span>
        </a>

        <a href="{{ route('courier.tarifas') }}" class="co-stat">
            <span class="co-stat-label"><i class="fa-solid fa-weight-hanging"></i> Tarifas</span>
            <span class="co-stat-value">{{ $n($resumen['tarifas']['total']) }}</span>
            <span class="co-stat-detail">
                tablas 0 a 16 · <strong>{{ $resumen['tarifas']['planas'] }}</strong> planas (mismo valor a cualquier peso)
            </span>
            <span class="co-stat-origin">Hoja <code>Pesos</code></span>
        </a>

        <a href="{{ route('courier.configuraciones') }}" class="co-stat">
            <span class="co-stat-label"><i class="fa-solid fa-sliders"></i> Configuración de pago</span>
            <span class="co-stat-value">{{ $n($resumen['configuraciones']['total']) }}</span>
            <span class="co-stat-detail">
                <strong>{{ $n($resumen['configuraciones']['si']) }}</strong> se pagan ·
                <strong>{{ $n($resumen['configuraciones']['no']) }}</strong> no
                @if($resumen['configuraciones']['revisar'] > 0)
                    · <strong>{{ $resumen['configuraciones']['revisar'] }}</strong> por revisar
                @endif
                @if($resumen['configuraciones']['si_tabla_0'] > 0)
                    <br>{{ $resumen['configuraciones']['si_tabla_0'] }} se pagan con tabla 0
                @endif
            </span>
            <span class="co-stat-origin">Hoja <code>PagosCentroCostos</code></span>
        </a>

        <a href="{{ route('courier.proveedores') }}" class="co-stat">
            <span class="co-stat-label"><i class="fa-solid fa-building-columns"></i> Proveedores</span>
            <span class="co-stat-value">{{ $n($resumen['proveedores']['total']) }}</span>
            <span class="co-stat-detail">
                <strong>{{ $n($resumen['proveedores']['factura']) }}</strong> factura ·
                <strong>{{ $n($resumen['proveedores']['boleta']) }}</strong> boleta ·
                <strong>{{ $n($resumen['proveedores']['sin_documento']) }}</strong> sin documento
            </span>
            <span class="co-stat-origin">Hoja <code>DatosProveedores</code></span>
        </a>

        <div class="co-stat">
            <span class="co-stat-label"><i class="fa-solid fa-list-check"></i> Reglas auxiliares</span>
            <span class="co-stat-value">{{ $n($resumen['estados']['total'] + $resumen['pesos']['total']) }}</span>
            <span class="co-stat-detail">
                <strong>{{ $resumen['estados']['total'] }}</strong> estados de entrega
                ({{ $resumen['estados']['descontar'] }} descuentan) ·
                <strong>{{ $n($resumen['pesos']['total']) }}</strong> pesos transformados
            </span>
            <span class="co-stat-origin">Hojas <code>Estados</code> y <code>PesoTransformado</code></span>
        </div>

    </div>

    {{-- ====== CÓMO SE USAN ====== --}}
    <section class="co-region">
        <div class="co-region-head">
            <h2 class="co-region-title">Cómo se encadenan para pagar un bulto</h2>
            <span class="co-region-meta">Mismo orden que la planilla</span>
        </div>
        <div class="co-region-body">
            <ul class="co-origen-list">
                <li><span>1 · Comuna de destino</span><code>Comunas → agente y zona</code></li>
                <li><span>2 · Agente + cliente + servicio</span><code>Configuración de pago → ¿se paga? ¿tabla?</code></li>
                <li><span>3 · Peso de bodega o peso declarado</span><code>Pesos transformados → kilos enteros</code></li>
                <li><span>4 · Tabla + kilos</span><code>Tarifas → valor</code></li>
                <li><span>5 · Estado de entrega</span><code>Estados → pagar o descontar</code></li>
                <li><span>6 · Agente + repartidor</span><code>Proveedores → a quién, con qué documento, a qué cuenta</code></li>
            </ul>
            <p class="co-note" style="margin-top:0.85rem">
                Esta pantalla muestra catálogos. Los pasos 1 a 6 se aplican a cada bulto en el cálculo mensual, que es la siguiente etapa.
            </p>
        </div>
    </section>

</div>
@endsection
