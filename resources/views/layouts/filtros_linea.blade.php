<div class="card shadow-sm border rounded mb-4">
    <div class="card-body py-3 px-4">
        <form method="GET" action="{{ $action ?? url()->current() }}">
            <div class="row align-items-end g-3">
                {{-- Título opcional --}}
                @isset($titulo)
                    <div class="col-12">
                        <h5 class="fw-bold text-primary mb-3">
                            <i class="fas fa-filter me-2 text-secondary"></i>{{ $titulo }}
                        </h5>
                    </div>
                @endisset

                {{-- Contenido del filtro (slot) --}}
                {{ $slot }}  {{-- El {{ $slot }} representa lo que está dentro del @component, en este caso el <select>. --}}

                {{-- Botones de acción --}}
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary shadow-sm">
                        <i class="fas fa-search me-1"></i> Aplicar Filtro
                    </button>
                </div>

                @if(request()->query())
                    <div class="col-auto">
                        <a href="{{ $action ?? url()->current() }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times-circle me-1"></i> Limpiar
                        </a>
                    </div>
                @endif
            </div>
        </form>
    </div>
</div>
