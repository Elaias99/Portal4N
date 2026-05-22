@php
    $baseExcept = ['page', 'columna', 'valor'];

    $querySin = function (array $extra = []) use ($baseExcept) {
        return request()->except(array_merge($baseExcept, $extra));
    };

    $ordenUrl = function (string $sortBy, string $sortOrder) use ($querySin) {
        return route('cobranzas.documentos', array_merge(
            $querySin(),
            [
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ]
        ));
    };

    $limpiarUrl = function (string $filterKey) use ($querySin) {
        return route('cobranzas.documentos', $querySin([$filterKey]));
    };

    $inputOcultos = function (string $filterKey) use ($querySin) {
        return $querySin([$filterKey]);
    };

    $activo = fn (string $filterKey) => request()->filled($filterKey);
@endphp

<thead>
    <tr>
        <th class="text-center" style="width:40px;">
            <input type="checkbox" id="check-all-documentos-factory">
        </th>

        {{-- EMPRESA --}}
        <th>
            <div class="dropdown d-inline">
                <button class="btn btn-light btn-sm dropdown-toggle px-2 py-1 {{ $activo('cf_empresa_id') ? 'cc-column-filter-active' : '' }}"
                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Empresa
                    @if(isset($sortBy) && $sortBy === 'empresa_id')
                        <i class="bi {{ $sortOrder === 'asc' ? 'bi-sort-alpha-down' : 'bi-sort-alpha-up' }} ms-1 text-primary"></i>
                    @endif
                </button>

                <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
                    <li>
                        <a class="dropdown-item mb-1" href="{{ $ordenUrl('empresa_id', 'asc') }}">
                            <i class="bi bi-sort-alpha-down"></i> Ordenar A -> Z
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item mb-2" href="{{ $ordenUrl('empresa_id', 'desc') }}">
                            <i class="bi bi-sort-alpha-up"></i> Ordenar Z -> A
                        </a>
                    </li>

                    <li><hr class="dropdown-divider"></li>

                    <li class="px-2">
                        <form method="GET" action="{{ route('cobranzas.documentos') }}">
                            @foreach($inputOcultos('cf_empresa_id') as $key => $value)
                                @if(is_array($value))
                                    @foreach($value as $item)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach

                            <div class="mb-2">
                                <select name="cf_empresa_id" class="form-select form-select-sm">
                                    <option value="">-- Seleccionar empresa --</option>
                                    @foreach($empresas as $empresa)
                                        <option value="{{ $empresa->id }}" {{ request('cf_empresa_id') == $empresa->id ? 'selected' : '' }}>
                                            {{ $empresa->Nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                                    <i class="bi bi-filter"></i> Filtrar
                                </button>

                                @if($activo('cf_empresa_id'))
                                    <a href="{{ $limpiarUrl('cf_empresa_id') }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-x-circle"></i>
                                    </a>
                                @endif
                            </div>
                        </form>
                    </li>
                </ul>
            </div>
        </th>

        <th>Estado</th>

        {{-- TIPO DOCUMENTO --}}
        <th>
            <div class="dropdown d-inline">
                <button class="btn btn-light btn-sm dropdown-toggle px-2 py-1 {{ $activo('cf_tipo_documento_id') ? 'cc-column-filter-active' : '' }}"
                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Tipo Doc
                    @if(isset($sortBy) && $sortBy === 'tipo_documento_id')
                        <i class="bi {{ $sortOrder === 'asc' ? 'bi-sort-alpha-down' : 'bi-sort-alpha-up' }} ms-1 text-primary"></i>
                    @endif
                </button>

                <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
                    <li>
                        <a class="dropdown-item mb-1" href="{{ $ordenUrl('tipo_documento_id', 'asc') }}">
                            <i class="bi bi-sort-alpha-down"></i> Ordenar A -> Z
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item mb-2" href="{{ $ordenUrl('tipo_documento_id', 'desc') }}">
                            <i class="bi bi-sort-alpha-up"></i> Ordenar Z -> A
                        </a>
                    </li>

                    <li><hr class="dropdown-divider"></li>

                    <li class="px-2">
                        <form method="GET" action="{{ route('cobranzas.documentos') }}">
                            @foreach($inputOcultos('cf_tipo_documento_id') as $key => $value)
                                @if(is_array($value))
                                    @foreach($value as $item)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach

                            <div class="mb-2">
                                <select name="cf_tipo_documento_id" class="form-select form-select-sm">
                                    <option value="">-- Seleccionar tipo --</option>
                                    @foreach($tiposDocumento as $tipo)
                                        <option value="{{ $tipo->id }}" {{ request('cf_tipo_documento_id') == $tipo->id ? 'selected' : '' }}>
                                            {{ $tipo->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                                    <i class="bi bi-filter"></i> Filtrar
                                </button>

                                @if($activo('cf_tipo_documento_id'))
                                    <a href="{{ $limpiarUrl('cf_tipo_documento_id') }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-x-circle"></i>
                                    </a>
                                @endif
                            </div>
                        </form>
                    </li>
                </ul>
            </div>
        </th>

        {{-- RUT CLIENTE --}}
        <th>
            <div class="dropdown d-inline">
                <button class="btn btn-light btn-sm dropdown-toggle px-2 py-1 {{ $activo('cf_rut_cliente') ? 'cc-column-filter-active' : '' }}"
                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    RUT Cliente
                    @if(isset($sortBy) && $sortBy === 'rut_cliente')
                        <i class="bi {{ $sortOrder === 'asc' ? 'bi-sort-alpha-down' : 'bi-sort-alpha-up' }} ms-1 text-primary"></i>
                    @endif
                </button>

                <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
                    <li>
                        <a class="dropdown-item mb-1" href="{{ $ordenUrl('rut_cliente', 'asc') }}">
                            <i class="bi bi-sort-alpha-down"></i> Ordenar A -> Z
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item mb-2" href="{{ $ordenUrl('rut_cliente', 'desc') }}">
                            <i class="bi bi-sort-alpha-up"></i> Ordenar Z -> A
                        </a>
                    </li>

                    <li><hr class="dropdown-divider"></li>

                    <li class="px-2">
                        <form method="GET" action="{{ route('cobranzas.documentos') }}">
                            @foreach($inputOcultos('cf_rut_cliente') as $key => $value)
                                @if(is_array($value))
                                    @foreach($value as $item)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach

                            <div class="mb-2">
                                <input type="text" name="cf_rut_cliente" class="form-control form-control-sm"
                                       placeholder="Buscar RUT..." value="{{ request('cf_rut_cliente') }}">
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                                    <i class="bi bi-search"></i> Filtrar
                                </button>

                                @if($activo('cf_rut_cliente'))
                                    <a href="{{ $limpiarUrl('cf_rut_cliente') }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-x-circle"></i>
                                    </a>
                                @endif
                            </div>
                        </form>
                    </li>
                </ul>
            </div>
        </th>

        {{-- RAZON SOCIAL --}}
        <th>
            <div class="dropdown d-inline">
                <button class="btn btn-light btn-sm dropdown-toggle px-2 py-1 {{ $activo('cf_razon_social') ? 'cc-column-filter-active' : '' }}"
                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Razón Social
                    @if(isset($sortBy) && $sortBy === 'razon_social')
                        <i class="bi {{ $sortOrder === 'asc' ? 'bi-sort-alpha-down' : 'bi-sort-alpha-up' }} ms-1 text-primary"></i>
                    @endif
                </button>

                <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
                    <li>
                        <a class="dropdown-item mb-1" href="{{ $ordenUrl('razon_social', 'asc') }}">
                            <i class="bi bi-sort-alpha-down"></i> Ordenar A -> Z
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item mb-2" href="{{ $ordenUrl('razon_social', 'desc') }}">
                            <i class="bi bi-sort-alpha-up"></i> Ordenar Z -> A
                        </a>
                    </li>

                    <li><hr class="dropdown-divider"></li>

                    <li class="px-2">
                        <form method="GET" action="{{ route('cobranzas.documentos') }}">
                            @foreach($inputOcultos('cf_razon_social') as $key => $value)
                                @if(is_array($value))
                                    @foreach($value as $item)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach

                            <div class="mb-2">
                                <input type="text" name="cf_razon_social" class="form-control form-control-sm"
                                       placeholder="Buscar razón social..." value="{{ request('cf_razon_social') }}">
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                                    <i class="bi bi-search"></i> Filtrar
                                </button>

                                @if($activo('cf_razon_social'))
                                    <a href="{{ $limpiarUrl('cf_razon_social') }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-x-circle"></i>
                                    </a>
                                @endif
                            </div>
                        </form>
                    </li>
                </ul>
            </div>
        </th>

        {{-- FOLIO --}}
        <th>
            <div class="dropdown d-inline">
                <button class="btn btn-light btn-sm dropdown-toggle px-2 py-1 {{ $activo('cf_folio') ? 'cc-column-filter-active' : '' }}"
                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Folio
                    @if(isset($sortBy) && $sortBy === 'folio')
                        <i class="bi {{ $sortOrder === 'asc' ? 'bi-sort-numeric-down' : 'bi-sort-numeric-up' }} ms-1 text-primary"></i>
                    @endif
                </button>

                <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
                    <li>
                        <a class="dropdown-item mb-1" href="{{ $ordenUrl('folio', 'asc') }}">
                            <i class="bi bi-sort-numeric-down"></i> Ordenar 0 -> 9
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item mb-2" href="{{ $ordenUrl('folio', 'desc') }}">
                            <i class="bi bi-sort-numeric-up"></i> Ordenar 9 -> 0
                        </a>
                    </li>

                    <li><hr class="dropdown-divider"></li>

                    <li class="px-2">
                        <form method="GET" action="{{ route('cobranzas.documentos') }}">
                            @foreach($inputOcultos('cf_folio') as $key => $value)
                                @if(is_array($value))
                                    @foreach($value as $item)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach

                            <div class="mb-2">
                                <input type="text" name="cf_folio" class="form-control form-control-sm"
                                       placeholder="Buscar folio..." value="{{ request('cf_folio') }}">
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                                    <i class="bi bi-search"></i> Filtrar
                                </button>

                                @if($activo('cf_folio'))
                                    <a href="{{ $limpiarUrl('cf_folio') }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-x-circle"></i>
                                    </a>
                                @endif
                            </div>
                        </form>
                    </li>
                </ul>
            </div>
        </th>

        {{-- FECHA DOCTO --}}
        <th>
            <div class="dropdown d-inline">
                <button class="btn btn-light btn-sm dropdown-toggle px-2 py-1 {{ $activo('cf_fecha_docto') ? 'cc-column-filter-active' : '' }}"
                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Fecha Docto
                    @if(isset($sortBy) && $sortBy === 'fecha_docto')
                        <i class="bi {{ $sortOrder === 'asc' ? 'bi-sort-down-alt' : 'bi-sort-up-alt' }} ms-1 text-primary"></i>
                    @endif
                </button>

                <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
                    <li>
                        <a class="dropdown-item mb-1" href="{{ $ordenUrl('fecha_docto', 'asc') }}">
                            <i class="bi bi-sort-down-alt"></i> Más antiguo primero
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item mb-2" href="{{ $ordenUrl('fecha_docto', 'desc') }}">
                            <i class="bi bi-sort-up-alt"></i> Más reciente primero
                        </a>
                    </li>

                    <li><hr class="dropdown-divider"></li>

                    <li class="px-2">
                        <form method="GET" action="{{ route('cobranzas.documentos') }}">
                            @foreach($inputOcultos('cf_fecha_docto') as $key => $value)
                                @if(is_array($value))
                                    @foreach($value as $item)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach

                            <div class="mb-2">
                                <input type="date" name="cf_fecha_docto" class="form-control form-control-sm"
                                       value="{{ request('cf_fecha_docto') }}">
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                                    <i class="bi bi-search"></i> Filtrar
                                </button>

                                @if($activo('cf_fecha_docto'))
                                    <a href="{{ $limpiarUrl('cf_fecha_docto') }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-x-circle"></i>
                                    </a>
                                @endif
                            </div>
                        </form>
                    </li>
                </ul>
            </div>
        </th>

        {{-- FECHA VENCIMIENTO --}}
        <th>
            <div class="dropdown d-inline">
                <button class="btn btn-light btn-sm dropdown-toggle px-2 py-1 {{ $activo('cf_fecha_vencimiento') ? 'cc-column-filter-active' : '' }}"
                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Fecha Vencimiento
                    @if(isset($sortBy) && $sortBy === 'fecha_vencimiento')
                        <i class="bi {{ $sortOrder === 'asc' ? 'bi-sort-down-alt' : 'bi-sort-up-alt' }} ms-1 text-primary"></i>
                    @endif
                </button>

                <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
                    <li>
                        <a class="dropdown-item mb-1" href="{{ $ordenUrl('fecha_vencimiento', 'asc') }}">
                            <i class="bi bi-sort-down-alt"></i> Más antiguo primero
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item mb-2" href="{{ $ordenUrl('fecha_vencimiento', 'desc') }}">
                            <i class="bi bi-sort-up-alt"></i> Más reciente primero
                        </a>
                    </li>

                    <li><hr class="dropdown-divider"></li>

                    <li class="px-2">
                        <form method="GET" action="{{ route('cobranzas.documentos') }}">
                            @foreach($inputOcultos('cf_fecha_vencimiento') as $key => $value)
                                @if(is_array($value))
                                    @foreach($value as $item)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach

                            <div class="mb-2">
                                <input type="date" name="cf_fecha_vencimiento" class="form-control form-control-sm"
                                       value="{{ request('cf_fecha_vencimiento') }}">
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                                    <i class="bi bi-search"></i> Filtrar
                                </button>

                                @if($activo('cf_fecha_vencimiento'))
                                    <a href="{{ $limpiarUrl('cf_fecha_vencimiento') }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-x-circle"></i>
                                    </a>
                                @endif
                            </div>
                        </form>
                    </li>
                </ul>
            </div>
        </th>

        <th class="text-right">Monto Neto</th>
        <th class="text-right">Monto IVA</th>

        {{-- MONTO TOTAL --}}
        <th class="text-right">
            <div class="dropdown d-inline">
                <button class="btn btn-light btn-sm dropdown-toggle px-2 py-1 {{ $activo('cf_monto_total') ? 'cc-column-filter-active' : '' }}"
                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Monto Total
                    @if(isset($sortBy) && $sortBy === 'monto_total')
                        <i class="bi {{ $sortOrder === 'asc' ? 'bi-sort-numeric-down' : 'bi-sort-numeric-up' }} ms-1 text-primary"></i>
                    @endif
                </button>

                <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
                    <li>
                        <a class="dropdown-item mb-1" href="{{ $ordenUrl('monto_total', 'asc') }}">
                            <i class="bi bi-sort-numeric-down"></i> Menor a mayor
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item mb-2" href="{{ $ordenUrl('monto_total', 'desc') }}">
                            <i class="bi bi-sort-numeric-up"></i> Mayor a menor
                        </a>
                    </li>

                    <li><hr class="dropdown-divider"></li>

                    <li class="px-2">
                        <form method="GET" action="{{ route('cobranzas.documentos') }}">
                            @foreach($inputOcultos('cf_monto_total') as $key => $value)
                                @if(is_array($value))
                                    @foreach($value as $item)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach

                            <div class="mb-2">
                                <input type="number" name="cf_monto_total" class="form-control form-control-sm"
                                       placeholder="Buscar monto..." value="{{ request('cf_monto_total') }}">
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                                    <i class="bi bi-search"></i> Filtrar
                                </button>

                                @if($activo('cf_monto_total'))
                                    <a href="{{ $limpiarUrl('cf_monto_total') }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-x-circle"></i>
                                    </a>
                                @endif
                            </div>
                        </form>
                    </li>
                </ul>
            </div>
        </th>

        <th class="text-right">Saldo Pendiente</th>
        <th>Fecha Último Movimiento</th>
    </tr>
</thead>