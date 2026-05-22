{{-- ==============================================
    Filtros por columna (Compras)
    Ubicación: resources/views/cobranzas/partials/filtros_compras.blade.php
============================================== --}}

@php
    /*
    |--------------------------------------------------------------------------
    | Relación columna visible -> parámetro independiente de filtro
    |--------------------------------------------------------------------------
    | Cada columna conserva su propio filtro, permitiendo acumular varios
    | filtros simultáneamente en el listado principal.
    |--------------------------------------------------------------------------
    */
    $mapaFiltros = [
        'empresa_id' => 'cf_empresa_id',
        'status_original' => 'cf_status_original',
        'tipo_documento_id' => 'cf_tipo_documento_id',
        'rut_proveedor' => 'cf_rut_proveedor',
        'razon_social' => 'cf_razon_social',
        'folio' => 'cf_folio',
        'fecha_docto' => 'cf_fecha_docto',
        'fecha_vencimiento' => 'cf_fecha_vencimiento',
        'monto_total' => 'cf_monto_total',
    ];

    $filterKey = $mapaFiltros[$columna] ?? null;

    $baseExcept = ['page', 'columna', 'valor'];

    $querySin = function (array $extra = []) use ($baseExcept) {
        return request()->except(array_merge($baseExcept, $extra));
    };

    $ordenUrl = function (string $sortOrderNuevo) use ($columna, $querySin) {
        return route('finanzas_compras.index', array_merge(
            $querySin(),
            [
                'sort_by' => $columna,
                'sort_order' => $sortOrderNuevo,
            ]
        ));
    };

    $limpiarUrl = function () use ($filterKey, $querySin) {
        return route('finanzas_compras.index', $querySin([$filterKey]));
    };

    $inputOcultos = function () use ($filterKey, $querySin) {
        return $querySin([$filterKey]);
    };

    $activo = $filterKey && request()->filled($filterKey);

    $esFecha = in_array($columna, [
        'fecha_docto',
        'fecha_vencimiento',
    ], true);

    $esMonto = $columna === 'monto_total';

    $esEmpresa = $columna === 'empresa_id';

    $esTipoDocumento = $columna === 'tipo_documento_id';

    $esStatusOriginal = $columna === 'status_original';

    $iconoAsc = in_array($columna, ['folio', 'monto_total'], true)
        ? 'bi-sort-numeric-down'
        : (in_array($columna, ['fecha_docto', 'fecha_vencimiento'], true)
            ? 'bi-sort-down-alt'
            : 'bi-sort-alpha-down');

    $iconoDesc = in_array($columna, ['folio', 'monto_total'], true)
        ? 'bi-sort-numeric-up'
        : (in_array($columna, ['fecha_docto', 'fecha_vencimiento'], true)
            ? 'bi-sort-up-alt'
            : 'bi-sort-alpha-up');

    $textoOrdenAsc = match ($columna) {
        'folio' => 'Ordenar 0 → 9',
        'monto_total' => 'Menor a mayor',
        'fecha_docto', 'fecha_vencimiento' => 'Más antiguo primero',
        default => 'Ordenar A → Z',
    };

    $textoOrdenDesc = match ($columna) {
        'folio' => 'Ordenar 9 → 0',
        'monto_total' => 'Mayor a menor',
        'fecha_docto', 'fecha_vencimiento' => 'Más reciente primero',
        default => 'Ordenar Z → A',
    };
@endphp

<th>
    <div class="dropdown d-inline">
        <button
            class="btn btn-light btn-sm dropdown-toggle px-2 py-1 {{ $activo ? 'fc-column-filter-active' : '' }}"
            type="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            style="font-weight:600; color:#495057; background:#f9fafb; border:none;"
        >
            {{ $label }}

            @if(isset($sortBy) && $sortBy === $columna)
                <i class="bi {{ $sortOrder === 'asc' ? $iconoAsc : $iconoDesc }} ms-1 text-primary"></i>
            @endif
        </button>

        <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
            {{-- Ordenamiento --}}
            <li>
                <a class="dropdown-item mb-1" href="{{ $ordenUrl('asc') }}">
                    <i class="bi {{ $iconoAsc }}"></i> {{ $textoOrdenAsc }}
                </a>
            </li>

            <li>
                <a class="dropdown-item mb-2" href="{{ $ordenUrl('desc') }}">
                    <i class="bi {{ $iconoDesc }}"></i> {{ $textoOrdenDesc }}
                </a>
            </li>

            <li>
                <hr class="dropdown-divider">
            </li>

            {{-- Filtro independiente por columna --}}
            @if($filterKey)
                <li class="px-2">
                    <form method="GET" action="{{ route('finanzas_compras.index') }}">
                        @foreach($inputOcultos() as $key => $value)
                            @if(is_array($value))
                                @foreach($value as $item)
                                    <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                @endforeach
                            @else
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach

                        <div class="mb-2">
                            @if($esEmpresa)
                                <select name="{{ $filterKey }}" class="form-select form-select-sm">
                                    <option value="">-- Seleccionar empresa --</option>

                                    @foreach($empresas as $empresa)
                                        <option
                                            value="{{ $empresa->id }}"
                                            {{ request($filterKey) == $empresa->id ? 'selected' : '' }}
                                        >
                                            {{ $empresa->Nombre }}
                                        </option>
                                    @endforeach
                                </select>

                            @elseif($esTipoDocumento)
                                <select name="{{ $filterKey }}" class="form-select form-select-sm">
                                    <option value="">-- Seleccionar tipo --</option>

                                    @foreach($tiposDocumento as $tipo)
                                        <option
                                            value="{{ $tipo->id }}"
                                            {{ request($filterKey) == $tipo->id ? 'selected' : '' }}
                                        >
                                            {{ $tipo->nombre }}
                                        </option>
                                    @endforeach
                                </select>

                            @elseif($esStatusOriginal)
                                <select name="{{ $filterKey }}" class="form-select form-select-sm">
                                    <option value="">-- Seleccionar estado --</option>

                                    <option value="Al día" {{ request($filterKey) === 'Al día' ? 'selected' : '' }}>
                                        Al día
                                    </option>

                                    <option value="Vencido" {{ request($filterKey) === 'Vencido' ? 'selected' : '' }}>
                                        Vencido
                                    </option>

                                    <option value="Sin cálculo" {{ request($filterKey) === 'Sin cálculo' ? 'selected' : '' }}>
                                        Sin cálculo
                                    </option>
                                </select>

                            @elseif($esFecha)
                                <input
                                    type="date"
                                    name="{{ $filterKey }}"
                                    class="form-control form-control-sm"
                                    value="{{ request($filterKey) }}"
                                >

                            @elseif($esMonto)
                                <input
                                    type="number"
                                    name="{{ $filterKey }}"
                                    class="form-control form-control-sm"
                                    placeholder="{{ $placeholder ?? 'Buscar monto...' }}"
                                    value="{{ request($filterKey) }}"
                                    min="0"
                                >

                            @else
                                <input
                                    type="text"
                                    name="{{ $filterKey }}"
                                    class="form-control form-control-sm"
                                    placeholder="{{ $placeholder ?? 'Buscar ' . strtolower($label) . '...' }}"
                                    value="{{ request($filterKey) }}"
                                >
                            @endif
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                                <i class="bi bi-filter"></i> Filtrar
                            </button>

                            @if($activo)
                                <a href="{{ $limpiarUrl() }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-x-circle"></i>
                                </a>
                            @endif
                        </div>
                    </form>
                </li>
            @endif
        </ul>
    </div>
</th>