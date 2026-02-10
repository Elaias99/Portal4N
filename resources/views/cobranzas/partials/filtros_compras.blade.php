{{-- ==============================================
    Filtros por columna (Compras)
    Ubicación: resources/views/cobranzas/partials/filtros_compras.blade.php
============================================== --}}
@props([
    'label',         // Texto visible en la cabecera (ej: "Razón Social")
    'columna',       // Nombre de la columna en la BD (ej: "razon_social")
    'sortBy' => null,
    'sortOrder' => 'asc',
    'placeholder' => null,
])

<th>
    <div class="dropdown d-inline">
        <button class="btn btn-light btn-sm dropdown-toggle px-2 py-1" type="button"
                data-bs-toggle="dropdown" aria-expanded="false"
                style="font-weight:600; color:#495057; background:#f9fafb; border:none;">
            {{ $label }}
            @if(isset($sortBy) && $sortBy === $columna)
                <i class="bi {{ $sortOrder === 'asc' ? 'bi-sort-alpha-down' : 'bi-sort-alpha-up' }} ms-1 text-primary"></i>
            @endif
        </button>

        <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
            {{-- Ordenamiento --}}
            <li>
                <a class="dropdown-item mb-1"
                   href="{{ route('finanzas_compras.column_filter', array_merge(request()->query(), ['sort_by' => $columna, 'sort_order' => 'asc'])) }}">
                    <i class="bi bi-sort-alpha-down"></i> Ordenar A → Z
                </a>
            </li>
            <li>
                <a class="dropdown-item mb-2"
                   href="{{ route('finanzas_compras.column_filter', array_merge(request()->query(), ['sort_by' => $columna, 'sort_order' => 'desc'])) }}">
                    <i class="bi bi-sort-alpha-up"></i> Ordenar Z → A
                </a>
            </li>

            <li><hr class="dropdown-divider"></li>

            {{-- Filtro por texto --}}
            <li class="px-2">
                <form method="GET" action="{{ route('finanzas_compras.column_filter') }}">
                    @foreach(request()->query() as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <input type="hidden" name="columna" value="{{ $columna }}">

                    <div class="mb-2">
                        <input type="text" name="valor" class="form-control form-control-sm"
                               placeholder="{{ $placeholder ?? 'Buscar ' . strtolower($label) . '...' }}"
                               value="{{ request('columna') === $columna ? request('valor') : '' }}">
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                        @if(request('columna') === $columna && request('valor'))
                            <a href="{{ route('finanzas_compras.index', array_diff_key(request()->query(), ['columna'=>1,'valor'=>1])) }}"
                               class="btn btn-outline-secondary btn-sm">
                               <i class="bi bi-x-circle"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </li>
        </ul>
    </div>
</th>
