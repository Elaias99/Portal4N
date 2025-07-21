{{-- Esta plantilla define el formulario de filtrado con su botón de aplicar y limpiar. --}}
<div class="card shadow-sm p-3">
    <form method="GET" action="{{ $action ?? url()->current() }}">
        {{-- Título del filtro --}}
        @isset($titulo)
            <h5 class="fw-bold mb-3">{{ $titulo }}</h5>
        @endisset

        {{-- Contenido dinámico que cada vista insertará --}}
        {!! $campos ?? '' !!}


        {{-- Botones de acción --}}
        <div class="d-grid gap-2 mt-3">
            <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
            <a href="{{ $action ?? url()->current() }}" class="btn btn-outline-secondary">Limpiar</a>
        </div>
    </form>
</div>
