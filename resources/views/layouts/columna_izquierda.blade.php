<div class="card shadow-sm p-3 mb-4">
    {{-- Título de la tarjeta superior (ej: Gestión Masiva de...) --}}
    @isset($tituloTarjeta)
        <h5 class="fw-bold mb-3">{{ $tituloTarjeta }}</h5>
    @endisset

    {{-- Slot para las acciones o accesos rápidos (ej: botones de importar/exportar) --}}
    {{ $acciones ?? '' }}
</div>

{{-- Filtros --}}
@component('layouts.filtros', ['titulo' => $tituloFiltros ?? 'Filtrar Por', 'action' => $action ?? url()->current()])
    @slot('campos')
        {{ $filtros ?? '' }}
    @endslot
@endcomponent
