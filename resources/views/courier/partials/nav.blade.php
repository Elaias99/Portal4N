{{--
    Navegación del módulo. $activo = index | agentes | comunas | tarifas | configuraciones | proveedores
    $resumen viene del service y aporta los conteos.
--}}
@php
    $items = [
        'index' => ['ruta' => route('courier.index'), 'icono' => 'fa-table-cells-large', 'texto' => 'Resumen', 'count' => null],
        'agentes' => ['ruta' => route('courier.agentes.index'), 'icono' => 'fa-truck', 'texto' => 'Agentes', 'count' => $resumen['agentes']['total'] ?? null],
        'comunas' => ['ruta' => route('courier.comunas'), 'icono' => 'fa-map-location-dot', 'texto' => 'Comunas', 'count' => $resumen['comunas']['total'] ?? null],
        'tarifas' => ['ruta' => route('courier.tarifas'), 'icono' => 'fa-weight-hanging', 'texto' => 'Tarifas', 'count' => $resumen['tarifas']['total'] ?? null],
        'configuraciones' => ['ruta' => route('courier.configuraciones'), 'icono' => 'fa-sliders', 'texto' => 'Configuración de pago', 'count' => $resumen['configuraciones']['total'] ?? null],
        'proveedores' => ['ruta' => route('courier.proveedores'), 'icono' => 'fa-building-columns', 'texto' => 'Proveedores', 'count' => $resumen['proveedores']['total'] ?? null],
    ];
@endphp

<nav class="co-nav" aria-label="Secciones del módulo Courier">
    @foreach($items as $clave => $item)
        <a href="{{ $item['ruta'] }}" class="{{ $activo === $clave ? 'is-active' : '' }}">
            <i class="fa-solid {{ $item['icono'] }}" aria-hidden="true"></i>
            <span>{{ $item['texto'] }}</span>
            @if($item['count'] !== null)
                <span class="co-nav-count">{{ number_format($item['count'], 0, ',', '.') }}</span>
            @endif
        </a>
    @endforeach
</nav>
