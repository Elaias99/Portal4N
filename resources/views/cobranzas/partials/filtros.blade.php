<thead>
    <tr>

        {{-- EMPRESA --}}
        <th>
            <div class="dropdown d-inline">
                <button class="btn btn-light btn-sm dropdown-toggle px-2 py-1" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false"
                        style="font-weight:600; color:#495057; background:#f9fafb; border:none;">
                    Empresa
                    @if(isset($sortBy) && $sortBy === 'empresa_id')
                        <i class="bi {{ $sortOrder === 'asc' ? 'bi-sort-alpha-down' : 'bi-sort-alpha-up' }} ms-1 text-primary"></i>
                    @endif
                </button>

                <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
                    {{-- Ordenamiento --}}
                    <li>
                        <a class="dropdown-item mb-1"
                            href="{{ route('cobranzas.column_filter', array_merge(request()->query(), ['sort_by' => 'empresa_id', 'sort_order' => 'asc'])) }}">
                            <i class="bi bi-sort-alpha-down"></i> Ordenar A → Z
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item mb-2"
                            href="{{ route('cobranzas.column_filter', array_merge(request()->query(), ['sort_by' => 'empresa_id', 'sort_order' => 'desc'])) }}">
                            <i class="bi bi-sort-alpha-up"></i> Ordenar Z → A
                        </a>
                    </li>

                    <li><hr class="dropdown-divider"></li>

                    {{-- Filtro por Empresa --}}
                    <li class="px-2">
                        <form method="GET" action="{{ route('cobranzas.column_filter') }}">
                            @foreach(request()->query() as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <input type="hidden" name="columna" value="empresa_id">

                            <div class="mb-2">
                                <select name="valor" class="form-select form-select-sm">
                                    <option value="">-- Seleccionar empresa --</option>
                                    @foreach($empresas as $empresa)
                                        <option value="{{ $empresa->id }}" {{ request('valor') == $empresa->id ? 'selected' : '' }}>
                                            {{ $empresa->Nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                                    <i class="bi bi-filter"></i> Filtrar
                                </button>

                                @if(request('columna') === 'empresa_id' && request('valor'))
                                    <a href="{{ route('cobranzas.documentos', array_diff_key(request()->query(), ['columna'=>1,'valor'=>1])) }}"
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

        {{-- Columnas fijas sin dropdown --}}
        <th>Estado</th>


        {{-- TIPO DOCUMENTO --}}
        <th>
            <div class="dropdown d-inline">
                <button class="btn btn-light btn-sm dropdown-toggle px-2 py-1" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false"
                        style="font-weight:600; color:#495057; background:#f9fafb; border:none;">
                    Tipo Doc
                    @if(isset($sortBy) && $sortBy === 'tipo_doc_id')
                        <i class="bi {{ $sortOrder === 'asc' ? 'bi-sort-alpha-down' : 'bi-sort-alpha-up' }} ms-1 text-primary"></i>
                    @endif
                </button>

                <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
                    {{-- Ordenamiento --}}
                    <li>
                        <a class="dropdown-item mb-1"
                            href="{{ route('cobranzas.column_filter', array_merge(request()->query(), ['sort_by' => 'tipo_doc_id', 'sort_order' => 'asc'])) }}">
                            <i class="bi bi-sort-alpha-down"></i> Ordenar A → Z
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item mb-2"
                            href="{{ route('cobranzas.column_filter', array_merge(request()->query(), ['sort_by' => 'tipo_doc_id', 'sort_order' => 'desc'])) }}">
                            <i class="bi bi-sort-alpha-up"></i> Ordenar Z → A
                        </a>
                    </li>

                    <li><hr class="dropdown-divider"></li>

                    {{-- Filtro por Tipo Doc --}}
                    <li class="px-2">
                        <form method="GET" action="{{ route('cobranzas.column_filter') }}">
                            @foreach(request()->query() as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <input type="hidden" name="columna" value="tipo_doc_id">

                            <div class="mb-2">
                                <select name="valor" class="form-select form-select-sm">
                                    <option value="">-- Seleccionar tipo --</option>
                                    @foreach($tiposDocumento as $tipo)
                                        <option value="{{ $tipo->id }}" {{ request('valor') == $tipo->id ? 'selected' : '' }}>
                                            {{ $tipo->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                                    <i class="bi bi-filter"></i> Filtrar
                                </button>

                                @if(request('columna') === 'tipo_doc_id' && request('valor'))
                                    <a href="{{ route('cobranzas.documentos', array_diff_key(request()->query(), ['columna'=>1,'valor'=>1])) }}"
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


        {{-- RUT CLIENTE --}}
        <th>
            <div class="dropdown d-inline">
                <button class="btn btn-light btn-sm dropdown-toggle px-2 py-1" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false"
                        style="font-weight:600; color:#495057; background:#f9fafb; border:none;">
                    RUT Cliente
                    @if(isset($sortBy) && $sortBy === 'rut_cliente')
                        <i class="bi {{ $sortOrder === 'asc' ? 'bi-sort-alpha-down' : 'bi-sort-alpha-up' }} ms-1 text-primary"></i>
                    @endif
                </button>

                <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
                    {{-- Ordenamiento --}}
                    <li>
                        <a class="dropdown-item mb-1"
                           href="{{ route('cobranzas.column_filter', array_merge(request()->query(), ['sort_by' => 'rut_cliente', 'sort_order' => 'asc'])) }}">
                           <i class="bi bi-sort-alpha-down"></i> Ordenar A → Z
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item mb-2"
                           href="{{ route('cobranzas.column_filter', array_merge(request()->query(), ['sort_by' => 'rut_cliente', 'sort_order' => 'desc'])) }}">
                           <i class="bi bi-sort-alpha-up"></i> Ordenar Z → A
                        </a>
                    </li>

                    <li><hr class="dropdown-divider"></li>

                    {{-- Filtro por texto --}}
                    <li class="px-2">
                        <form method="GET" action="{{ route('cobranzas.column_filter') }}">
                            @foreach(request()->query() as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <input type="hidden" name="columna" value="rut_cliente">

                            <div class="mb-2">
                                <input type="text" name="valor" class="form-control form-control-sm"
                                       placeholder="Buscar RUT..." value="{{ request('valor') }}">
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                                    <i class="bi bi-search"></i> Filtrar
                                </button>
                                @if(request('columna') === 'rut_cliente' && request('valor'))
                                    <a href="{{ route('cobranzas.documentos', array_diff_key(request()->query(), ['columna'=>1,'valor'=>1])) }}"
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

        {{-- RAZÓN SOCIAL --}}
        <th>
            <div class="dropdown d-inline">
                <button class="btn btn-light btn-sm dropdown-toggle px-2 py-1" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false"
                        style="font-weight:600; color:#495057; background:#f9fafb; border:none;">
                    Razón Social
                    @if(isset($sortBy) && $sortBy === 'razon_social')
                        <i class="bi {{ $sortOrder === 'asc' ? 'bi-sort-alpha-down' : 'bi-sort-alpha-up' }} ms-1 text-primary"></i>
                    @endif
                </button>

                <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
                    {{-- Ordenamiento --}}
                    <li>
                        <a class="dropdown-item mb-1"
                           href="{{ route('cobranzas.column_filter', array_merge(request()->query(), ['sort_by' => 'razon_social', 'sort_order' => 'asc'])) }}">
                           <i class="bi bi-sort-alpha-down"></i> Ordenar A → Z
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item mb-2"
                           href="{{ route('cobranzas.column_filter', array_merge(request()->query(), ['sort_by' => 'razon_social', 'sort_order' => 'desc'])) }}">
                           <i class="bi bi-sort-alpha-up"></i> Ordenar Z → A
                        </a>
                    </li>

                    <li><hr class="dropdown-divider"></li>

                    {{-- Filtro --}}
                    <li class="px-2">
                        <form method="GET" action="{{ route('cobranzas.column_filter') }}">
                            @foreach(request()->query() as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <input type="hidden" name="columna" value="razon_social">

                            <div class="mb-2">
                                <input type="text" name="valor" class="form-control form-control-sm"
                                       placeholder="Buscar razón social..." value="{{ request('valor') }}">
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                                    <i class="bi bi-search"></i> Filtrar
                                </button>
                                @if(request('columna') === 'razon_social' && request('valor'))
                                    <a href="{{ route('cobranzas.documentos', array_diff_key(request()->query(), ['columna'=>1,'valor'=>1])) }}"
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

        {{-- FOLIO --}}
        <th>
            <div class="dropdown d-inline">
                <button class="btn btn-light btn-sm dropdown-toggle px-2 py-1" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false"
                        style="font-weight:600; color:#495057; background:#f9fafb; border:none;">
                    Folio
                    @if(isset($sortBy) && $sortBy === 'folio')
                        <i class="bi {{ $sortOrder === 'asc' ? 'bi-sort-numeric-down' : 'bi-sort-numeric-up' }} ms-1 text-primary"></i>
                    @endif
                </button>

                <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
                    <li>
                        <a class="dropdown-item mb-1"
                           href="{{ route('cobranzas.column_filter', array_merge(request()->query(), ['sort_by' => 'folio', 'sort_order' => 'asc'])) }}">
                           <i class="bi bi-sort-numeric-down"></i> Ordenar 0 → 9
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item mb-2"
                           href="{{ route('cobranzas.column_filter', array_merge(request()->query(), ['sort_by' => 'folio', 'sort_order' => 'desc'])) }}">
                           <i class="bi bi-sort-numeric-up"></i> Ordenar 9 → 0
                        </a>
                    </li>

                    <li><hr class="dropdown-divider"></li>

                    {{-- Filtro --}}
                    <li class="px-2">
                        <form method="GET" action="{{ route('cobranzas.column_filter') }}">
                            @foreach(request()->query() as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <input type="hidden" name="columna" value="folio">

                            <div class="mb-2">
                                <input type="text" name="valor" class="form-control form-control-sm"
                                       placeholder="Buscar folio..." value="{{ request('valor') }}">
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                                    <i class="bi bi-search"></i> Filtrar
                                </button>
                                @if(request('columna') === 'folio' && request('valor'))
                                    <a href="{{ route('cobranzas.documentos', array_diff_key(request()->query(), ['columna'=>1,'valor'=>1])) }}"
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

        {{-- FECHA DOCTO --}}
        <th>
            <div class="dropdown d-inline">
                <button class="btn btn-light btn-sm dropdown-toggle px-2 py-1" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false"
                        style="font-weight:600; color:#495057; background:#f9fafb; border:none;">
                    Fecha Docto
                    @if(isset($sortBy) && $sortBy === 'fecha_docto')
                        <i class="bi {{ $sortOrder === 'asc' ? 'bi-sort-down-alt' : 'bi-sort-up-alt' }} ms-1 text-primary"></i>
                    @endif
                </button>

                <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
                    <li>
                        <a class="dropdown-item mb-1"
                           href="{{ route('cobranzas.column_filter', array_merge(request()->query(), ['sort_by' => 'fecha_docto', 'sort_order' => 'asc'])) }}">
                           <i class="bi bi-sort-down-alt"></i> Más antiguo primero
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item mb-2"
                           href="{{ route('cobranzas.column_filter', array_merge(request()->query(), ['sort_by' => 'fecha_docto', 'sort_order' => 'desc'])) }}">
                           <i class="bi bi-sort-up-alt"></i> Más reciente primero
                        </a>
                    </li>

                    <li><hr class="dropdown-divider"></li>

                    {{-- Filtro --}}
                    <li class="px-2">
                        <form method="GET" action="{{ route('cobranzas.column_filter') }}">
                            @foreach(request()->query() as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <input type="hidden" name="columna" value="fecha_docto">

                            <div class="mb-2">
                                <input type="date" name="valor" class="form-control form-control-sm"
                                       value="{{ request('valor') }}">
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                                    <i class="bi bi-search"></i> Filtrar
                                </button>
                                @if(request('columna') === 'fecha_docto' && request('valor'))
                                    <a href="{{ route('cobranzas.documentos', array_diff_key(request()->query(), ['columna'=>1,'valor'=>1])) }}"
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

        {{-- FECHA VENCIMIENTO --}}
        <th>
            <div class="dropdown d-inline">
                <button class="btn btn-light btn-sm dropdown-toggle px-2 py-1" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false"
                        style="font-weight:600; color:#495057; background:#f9fafb; border:none;">
                    Fecha Vencimiento
                    @if(isset($sortBy) && $sortBy === 'fecha_vencimiento')
                        <i class="bi {{ $sortOrder === 'asc' ? 'bi-sort-down-alt' : 'bi-sort-up-alt' }} ms-1 text-primary"></i>
                    @endif
                </button>

                <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
                    <li>
                        <a class="dropdown-item mb-1"
                           href="{{ route('cobranzas.column_filter', array_merge(request()->query(), ['sort_by' => 'fecha_vencimiento', 'sort_order' => 'asc'])) }}">
                           <i class="bi bi-sort-down-alt"></i> Más antiguo primero
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item mb-2"
                           href="{{ route('cobranzas.column_filter', array_merge(request()->query(), ['sort_by' => 'fecha_vencimiento', 'sort_order' => 'desc'])) }}">
                           <i class="bi bi-sort-up-alt"></i> Más reciente primero
                        </a>
                    </li>

                    <li><hr class="dropdown-divider"></li>

                    {{-- Filtro --}}
                    <li class="px-2">
                        <form method="GET" action="{{ route('cobranzas.column_filter') }}">
                            @foreach(request()->query() as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <input type="hidden" name="columna" value="fecha_vencimiento">

                            <div class="mb-2">
                                <input type="date" name="valor" class="form-control form-control-sm"
                                       value="{{ request('valor') }}">
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                                    <i class="bi bi-search"></i> Filtrar
                                </button>
                                @if(request('columna') === 'fecha_vencimiento' && request('valor'))
                                    <a href="{{ route('cobranzas.documentos', array_diff_key(request()->query(), ['columna'=>1,'valor'=>1])) }}"
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



        <th class="text-right">Monto Neto</th>
        <th class="text-right">Monto IVA</th>

        {{-- MONTO TOTAL --}}
        <th class="text-right">
            <div class="dropdown d-inline">
                <button class="btn btn-light btn-sm dropdown-toggle px-2 py-1" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false"
                        style="font-weight:600; color:#495057; background:#f9fafb; border:none;">
                    Monto Total
                    @if(isset($sortBy) && $sortBy === 'monto_total')
                        <i class="bi {{ $sortOrder === 'asc' ? 'bi-sort-numeric-down' : 'bi-sort-numeric-up' }} ms-1 text-primary"></i>
                    @endif
                </button>

                <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
                    <li>
                        <a class="dropdown-item mb-1"
                           href="{{ route('cobranzas.column_filter', array_merge(request()->query(), ['sort_by' => 'monto_total', 'sort_order' => 'asc'])) }}">
                           <i class="bi bi-sort-numeric-down"></i> Menor a mayor
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item mb-2"
                           href="{{ route('cobranzas.column_filter', array_merge(request()->query(), ['sort_by' => 'monto_total', 'sort_order' => 'desc'])) }}">
                           <i class="bi bi-sort-numeric-up"></i> Mayor a menor
                        </a>
                    </li>

                    <li><hr class="dropdown-divider"></li>

                    {{-- Filtro --}}
                    <li class="px-2">
                        <form method="GET" action="{{ route('cobranzas.column_filter') }}">
                            @foreach(request()->query() as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <input type="hidden" name="columna" value="monto_total">

                            <div class="mb-2">
                                <input type="number" name="valor" class="form-control form-control-sm"
                                       placeholder="Buscar monto..." value="{{ request('valor') }}">
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                                    <i class="bi bi-search"></i> Filtrar
                                </button>
                                @if(request('columna') === 'monto_total' && request('valor'))
                                    <a href="{{ route('cobranzas.documentos', array_diff_key(request()->query(), ['columna'=>1,'valor'=>1])) }}"
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

        <th class="text-right">Saldo Pendiente</th>
        {{-- Columnas fijas --}}
        <th>Fecha Último Movimiento</th>
    </tr>
</thead>
