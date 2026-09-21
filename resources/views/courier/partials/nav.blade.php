{{--
    Navegación del módulo. $activo = index | agentes | comunas | tarifas | configuraciones | proveedores
    $resumen viene del service y aporta los conteos.
    La raíz (index) es el proceso de pago; el resto son catálogos.
--}}
@php
    $items = [
        'index' => ['ruta' => route('courier.index'), 'icono' => null, 'texto' => 'Pago del mes', 'count' => null],
        'agentes' => ['ruta' => route('courier.agentes.index'), 'icono' => 'fa-truck', 'texto' => 'Agentes', 'count' => $resumen['agentes']['total'] ?? null],
        'comunas' => ['ruta' => route('courier.comunas'), 'icono' => 'fa-magnifying-glass', 'texto' => 'Comunas', 'count' => $resumen['comunas']['total'] ?? null],

        // 'tarifas' => ['ruta' => route('courier.tarifas'), 'icono' => 'fa-weight-hanging', 'texto' => 'Tarifas', 'count' => $resumen['tarifas']['total'] ?? null],
        // 'configuraciones' => ['ruta' => route('courier.configuraciones'), 'icono' => 'fa-sliders', 'texto' => 'Configuración de pago', 'count' => $resumen['configuraciones']['total'] ?? null],
        'proveedores' => ['ruta' => route('courier.proveedores'), 'icono' => 'fa-building-columns', 'texto' => 'Proveedores', 'count' => $resumen['proveedores']['total'] ?? null],
    ];
@endphp

<nav class="co-nav" aria-label="Secciones del módulo Courier">
    @foreach($items as $clave => $item)
        <a href="{{ $item['ruta'] }}" class="{{ $activo === $clave ? 'is-active' : '' }} {{ $clave === 'index' ? 'co-nav-main' : '' }}">
            @if($item['icono'])
                <i class="fa-solid {{ $item['icono'] }}" aria-hidden="true"></i>
            @endif
            <span>{{ $item['texto'] }}</span>
            @if($item['count'] !== null)
                <span class="co-nav-count">{{ number_format($item['count'], 0, ',', '.') }}</span>
            @endif
        </a>
        @if($clave === 'index')
            <span class="co-nav-sep" aria-hidden="true"></span>
        @endif
    @endforeach
</nav>
