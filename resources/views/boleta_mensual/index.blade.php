@extends('layouts.app')

@vite('resources/css/boleta_mensual.css')

@section('content')


<div class="container-fluid py-3 hm">

    <x-finanzas.flash-messages />

    <x-finanzas.header
        :back-route="route('boleta.mensual.panel')"
        title="Boletas Honorarios"
        back-label="Volver al Panel Boletas Honorarios"
    />

    @php
        $filtrosActivos = collect([
            request('empresa_id'),
            request('anio'),
            request('mes'),
            request('razon_social_emisor'),
            request('rut_emisor'),
            request('folio'),
            request('fecha_emision_desde'),
            request('fecha_emision_hasta'),
            request('fecha_vencimiento_desde'),
            request('fecha_vencimiento_hasta'),
            request('saldo_tipo'),
            request('saldo_monto'),
            request('servicio_tipo'),
            request('servicio_valor'),
        ])->filter(fn($v) => $v !== null && $v !== '')->count();

        $serviciosProveedor = [
            'SERVICIOS BOLETAS HONORARIOS',
            'COLABORADOR',
            'SUSCRIPCIONES',
            'AGENCIAS',
            'CONSERVADOR DE BIENES RAICES',
            'MANTENCION EDIFICIO',
            'MANTENCION VEHICULOS',
            'COURIER',
        ];
    @endphp

    <x-finanzas.top-section actions-width="320px">
        <x-slot:filters>
            <x-finanzas.filters-card>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="fw-semibold">Filtros de búsqueda</div>

                    @if($filtrosActivos)
                        <span class="hm-summary-badge">
                            {{ $filtrosActivos }} activo(s)
                        </span>
                    @endif
                </div>

                <form method="GET" action="{{ route('honorarios.mensual.index') }}">
                    <div class="row g-3 align-items-end">

                        <div class="col-12 col-md-3 col-lg-2">
                            <label class="form-label small text-muted">Empresa</label>
                            <select name="empresa_id" class="form-select form-select-sm">
                                <option value="">Todas</option>
                                @foreach($empresas as $empresa)
                                    <option value="{{ $empresa->id }}"
                                        {{ request('empresa_id') == $empresa->id ? 'selected' : '' }}>
                                        {{ $empresa->Nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-6 col-md-2 col-lg-1">
                            <label class="form-label small text-muted">Año</label>
                            <select name="anio" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                @foreach($anios as $anio)
                                    <option value="{{ $anio }}"
                                        {{ request('anio') == $anio ? 'selected' : '' }}>
                                        {{ $anio }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-6 col-md-2 col-lg-1">
                            <label class="form-label small text-muted">Mes</label>
                            <select name="mes" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}"
                                        {{ request('mes') == $m ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-12 col-md-5 col-lg-2">
                            <label class="form-label small text-muted">Razón social emisor</label>
                            <input type="text"
                                name="razon_social_emisor"
                                class="form-control form-control-sm"
                                value="{{ request('razon_social_emisor') }}">
                        </div>

                        <div class="col-12 col-md-3 col-lg-2">
                            <label class="form-label small text-muted">RUT emisor</label>
                            <input type="text"
                                name="rut_emisor"
                                class="form-control form-control-sm"
                                value="{{ request('rut_emisor') }}">
                        </div>

                        <div class="col-12 col-md-3 col-lg-1">
                            <label class="form-label small text-muted">Folio</label>
                            <input type="text"
                                name="folio"
                                class="form-control form-control-sm"
                                value="{{ request('folio') }}">
                        </div>




                        <div class="col-12 col-md-3 col-lg-2 dropdown-fechas">
                            <label class="form-label small text-muted">Fecha documento</label>
                            <div class="dropdown w-100">
                                <button class="form-control form-control-sm dropdown-toggle text-start"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                    <i class="bi bi-calendar3"></i> Fecha Dcto.
                                </button>

                                <div class="dropdown-menu p-3">
                                    <label class="form-label small text-muted">Desde</label>
                                    <input type="date"
                                        name="fecha_emision_desde"
                                        class="form-control form-control-sm mb-2"
                                        value="{{ request('fecha_emision_desde') }}">

                                    <label class="form-label small text-muted">Hasta</label>
                                    <input type="date"
                                        name="fecha_emision_hasta"
                                        class="form-control form-control-sm"
                                        value="{{ request('fecha_emision_hasta') }}">
                                </div>
                            </div>
                        </div>






                        <div class="col-12 col-md-4 col-lg-2">
                            <label class="form-label small text-muted">Servicio</label>

                            <div class="dropdown w-100 keep-open-on-drag">
                                <button class="form-control form-control-sm dropdown-toggle text-start"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        data-bs-auto-close="outside"
                                        aria-expanded="false">
                                    Buscar servicio por
                                </button>

                                <div class="dropdown-menu p-3" style="min-width: 260px;">
                                    <div class="mb-2">
                                        <label class="form-label small text-muted">Tipo</label>
                                        <select name="servicio_tipo" class="form-select form-select-sm">
                                            <option value="">Seleccione</option>
                                            <option value="proveedor"
                                                {{ request('servicio_tipo') === 'proveedor' ? 'selected' : '' }}>
                                                Servicio proveedor
                                            </option>
                                            <option value="manual"
                                                {{ request('servicio_tipo') === 'manual' ? 'selected' : '' }}>
                                                Servicio manual
                                            </option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="form-label small text-muted">Servicio</label>

                                        <select name="servicio_valor"
                                                id="servicioProveedorSelect"
                                                class="form-select form-select-sm d-none">
                                            <option value="">Seleccione servicio</option>
                                            @foreach($serviciosProveedor as $servicio)
                                                <option value="{{ $servicio }}"
                                                    {{ request('servicio_valor') === $servicio ? 'selected' : '' }}>
                                                    {{ $servicio }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <input type="text"
                                            name="servicio_valor"
                                            id="servicioManualInput"
                                            class="form-control form-control-sm d-none"
                                            placeholder="Ej: Agencias, Courier…"
                                            value="{{ request('servicio_valor') }}">
                                    </div>
                                </div>
                            </div>
                        </div>




                        <div class="col-12 col-md-3 col-lg-2 dropdown-fechas">
                            <label class="form-label small text-muted">Fecha vencimiento</label>
                            <div class="dropdown w-100">
                                <button class="form-control form-control-sm dropdown-toggle text-start"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                    <i class="bi bi-calendar-event"></i> Fecha Venc.
                                </button>

                                <div class="dropdown-menu p-3">
                                    <label class="form-label small text-muted">Desde</label>
                                    <input type="date"
                                        name="fecha_vencimiento_desde"
                                        class="form-control form-control-sm mb-2"
                                        value="{{ request('fecha_vencimiento_desde') }}">

                                    <label class="form-label small text-muted">Hasta</label>
                                    <input type="date"
                                        name="fecha_vencimiento_hasta"
                                        class="form-control form-control-sm"
                                        value="{{ request('fecha_vencimiento_hasta') }}">
                                </div>
                            </div>
                        </div>







                        <div class="col-12 col-md-3 col-lg-2">
                            <label class="form-label small text-muted">Saldo</label>

                            <div class="dropdown w-100 keep-open-on-drag">
                                <button
                                    class="form-control form-control-sm dropdown-toggle text-start"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    data-bs-auto-close="outside"
                                    aria-expanded="false">
                                    Buscar saldo por
                                </button>

                                <div class="dropdown-menu p-3" style="min-width: 240px;">
                                    <label class="form-label small text-muted mb-1">Tipo</label>
                                    <select name="saldo_tipo" class="form-select form-select-sm mb-2">
                                        <option value="">Selecciona</option>
                                        <option value="pendiente" {{ request('saldo_tipo') === 'pendiente' ? 'selected' : '' }}>
                                            Saldo pendiente
                                        </option>
                                        <option value="original" {{ request('saldo_tipo') === 'original' ? 'selected' : '' }}>
                                            Monto original
                                        </option>
                                    </select>

                                    <label class="form-label small text-muted mb-1">Monto</label>
                                    <input
                                        type="number"
                                        name="saldo_monto"
                                        class="form-control form-control-sm"
                                        value="{{ request('saldo_monto') }}"
                                        placeholder="Ej: 350000"
                                        min="0">
                                </div>
                            </div>
                        </div>





                        <div class="col-md-1">
                            <label class="form-label small text-muted">Estado Original</label>
                            <select name="estado_original" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="Al día" {{ request('estado_original') == 'Al día' ? 'selected' : '' }}>
                                    Al día ({{ $totalAlDia ?? 0 }})
                                </option>
                                <option value="Vencido" {{ request('estado_original') == 'Vencido' ? 'selected' : '' }}>
                                    Vencido ({{ $totalVencido ?? 0 }})
                                </option>
                            </select>
                        </div>

                        <div class="col-md-1">
                            <label class="form-label small text-muted">Estado de Pago</label>
                            <select name="estado_pago" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="Pagado" {{ request('estado_pago') == 'Pagado' ? 'selected' : '' }}>
                                    Pagado ({{ $totalPagados ?? 0 }})
                                </option>
                                <option value="Pendiente" {{ request('estado_pago') == 'Pendiente' ? 'selected' : '' }}>
                                    Pendiente ({{ $totalPendientes ?? 0 }})
                                </option>
                            </select>
                        </div>





                        

                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="{{ route('honorarios.mensual.index') }}"
                           class="btn btn-outline-secondary btn-sm">
                            Limpiar
                        </a>

                        <button type="submit" class="btn btn-success btn-sm">
                            Buscar
                        </button>
                    </div>



                    <div class="d-flex justify-content-end mt-3">
                        <a href="{{ route('cobranzas-compras.index', ['origen' => 'honorarios']) }}"
                        class="btn btn-outline-secondary btn-sm">
                            Detalle Proveedor
                        </a>
                    </div>

                </form>


                
            </x-finanzas.filters-card>
        </x-slot:filters>

        <x-slot:actions>
            <x-finanzas.mass-actions-card title="Gestión Masiva">



                <a href="{{ route('movimientos.honorarios.historial') }}"
                   class="btn btn-outline-secondary btn-sm w-100 mb-2">
                    Movimientos
                </a>

                <a href="{{ route('honorarios.mensual.calendario') }}"
                   class="btn btn-outline-secondary btn-sm w-100 mb-3">
                    Calendario Corporativo
                </a>

                <form action="{{ route('honorarios.mensual.import') }}"
                      method="POST"
                      enctype="multipart/form-data"
                      class="mb-3">
                    @csrf

                    <input type="file"
                        name="archivo"
                        class="form-control form-control-sm mb-2"
                        required>

                    <button type="submit" class="btn btn-success btn-sm w-100">
                        Importar
                    </button>
                </form>

                <form method="GET"
                      action="{{ route('honorarios.mensual.export') }}"
                      id="form-exportar"
                      class="mb-3">
                    @foreach(request()->query() as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach

                    <button type="submit"
                            class="btn btn-outline-success btn-sm w-100"
                            id="btn-exportar">
                        <span id="texto-exportar">Exportar</span>
                        <span id="spinner-exportar"
                            class="spinner-border spinner-border-sm ms-2 d-none"
                            role="status"
                            aria-hidden="true"></span>
                    </button>
                </form>

                <button
                    type="button"
                    id="btn-pagar-seleccionados"
                    class="btn btn-success btn-sm w-100 mb-2">
                    Pagar
                </button>

                <button
                    type="button"
                    id="btn-proximo-pago-seleccionados"
                    class="btn btn-outline-primary btn-sm w-100">
                    Definir próximo pago
                </button>

            </x-finanzas.mass-actions-card>
        </x-slot:actions>
    </x-finanzas.top-section>

    @if(session('info'))
        <div class="alert alert-info">
            {{ session('info') }}
        </div>
    @endif

    <div class="hm-table-wrap">


        @if($registros->isEmpty())
            <div class="p-3">
                <p class="text-muted mb-0">No hay honorarios registrados.</p>
            </div>
        @else


            <x-finanzas.plain-table>

                @php
                    $queryBaseColumnas = request()->except(['page']);

                    $ordenUrl = function (string $sortByNuevo, string $sortOrderNuevo) use ($queryBaseColumnas) {
                        return route('honorarios.mensual.index', array_merge(
                            $queryBaseColumnas,
                            [
                                'sort_by' => $sortByNuevo,
                                'sort_order' => $sortOrderNuevo,
                            ]
                        ));
                    };

                    $queryFiltroColumna = function (string $filtroActual) {
                        return request()->except(['page', $filtroActual]);
                    };

                    $limpiarFiltroUrl = function (string $filtroActual) {
                        return route(
                            'honorarios.mensual.index',
                            request()->except(['page', $filtroActual])
                        );
                    };

                    $filtroActivo = fn (string $filtro) => request()->filled($filtro);
                @endphp

                <thead>
                    <tr>
                        <th class="hm-nowrap text-center">
                            <input type="checkbox" id="check-all-honorarios">
                        </th>

                        {{-- EMPRESA --}}
                        <th>
                            <div class="dropdown d-inline">
                                <button
                                    class="btn btn-light btn-sm dropdown-toggle px-2 py-1 {{ $filtroActivo('cf_empresa_id') ? 'hm-column-filter-active' : '' }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    Empresa

                                    @if(($sortBy ?? null) === 'empresa_id')
                                        <i class="bi {{ ($sortOrder ?? 'asc') === 'asc' ? 'bi-sort-alpha-down' : 'bi-sort-alpha-up' }} ms-1 text-primary"></i>
                                    @endif
                                </button>

                                <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
                                    <li>
                                        <a class="dropdown-item mb-1" href="{{ $ordenUrl('empresa_id', 'asc') }}">
                                            <i class="bi bi-sort-alpha-down"></i> Ordenar A → Z
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item mb-2" href="{{ $ordenUrl('empresa_id', 'desc') }}">
                                            <i class="bi bi-sort-alpha-up"></i> Ordenar Z → A
                                        </a>
                                    </li>

                                    <li><hr class="dropdown-divider"></li>

                                    <li class="px-2">
                                        <form method="GET" action="{{ route('honorarios.mensual.index') }}">
                                            @foreach($queryFiltroColumna('cf_empresa_id') as $key => $value)
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
                                                        <option value="{{ $empresa->id }}"
                                                            {{ request('cf_empresa_id') == $empresa->id ? 'selected' : '' }}>
                                                            {{ $empresa->Nombre }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="d-flex justify-content-between">
                                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                                                    <i class="bi bi-filter"></i> Filtrar
                                                </button>

                                                @if($filtroActivo('cf_empresa_id'))
                                                    <a href="{{ $limpiarFiltroUrl('cf_empresa_id') }}" class="btn btn-outline-secondary btn-sm">
                                                        <i class="bi bi-x-circle"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </th>

                        {{-- TIPO BOLETA --}}
                        <th>
                            <div class="dropdown d-inline">
                                <button
                                    class="btn btn-light btn-sm dropdown-toggle px-2 py-1 {{ $filtroActivo('cf_tipo_boleta') ? 'hm-column-filter-active' : '' }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    Tipo boleta

                                    @if(($sortBy ?? null) === 'tipo_boleta')
                                        <i class="bi {{ ($sortOrder ?? 'asc') === 'asc' ? 'bi-sort-alpha-down' : 'bi-sort-alpha-up' }} ms-1 text-primary"></i>
                                    @endif
                                </button>

                                <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
                                    <li>
                                        <a class="dropdown-item mb-1" href="{{ $ordenUrl('tipo_boleta', 'asc') }}">
                                            <i class="bi bi-sort-alpha-down"></i> Ordenar A → Z
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item mb-2" href="{{ $ordenUrl('tipo_boleta', 'desc') }}">
                                            <i class="bi bi-sort-alpha-up"></i> Ordenar Z → A
                                        </a>
                                    </li>

                                    <li><hr class="dropdown-divider"></li>

                                    <li class="px-2">
                                        <form method="GET" action="{{ route('honorarios.mensual.index') }}">
                                            @foreach($queryFiltroColumna('cf_tipo_boleta') as $key => $value)
                                                @if(is_array($value))
                                                    @foreach($value as $item)
                                                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                                    @endforeach
                                                @else
                                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                                @endif
                                            @endforeach

                                            <div class="mb-2">
                                                <select name="cf_tipo_boleta" class="form-select form-select-sm">
                                                    <option value="">-- Seleccionar tipo --</option>

                                                    @foreach($tiposBoletaColumna as $tipoBoleta)
                                                        <option value="{{ $tipoBoleta }}"
                                                            {{ request('cf_tipo_boleta') === $tipoBoleta ? 'selected' : '' }}>
                                                            {{ $tipoBoleta }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="d-flex justify-content-between">
                                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                                                    <i class="bi bi-filter"></i> Filtrar
                                                </button>

                                                @if($filtroActivo('cf_tipo_boleta'))
                                                    <a href="{{ $limpiarFiltroUrl('cf_tipo_boleta') }}" class="btn btn-outline-secondary btn-sm">
                                                        <i class="bi bi-x-circle"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </th>

                        {{-- ESTADO FINANCIERO VISIBLE --}}
                        <th>
                            <div class="dropdown d-inline">
                                <button
                                    class="btn btn-light btn-sm dropdown-toggle px-2 py-1 {{ $filtroActivo('cf_estado_financiero') ? 'hm-column-filter-active' : '' }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    Estado

                                    @if(($sortBy ?? null) === 'estado_financiero_inicial')
                                        <i class="bi {{ ($sortOrder ?? 'asc') === 'asc' ? 'bi-sort-alpha-down' : 'bi-sort-alpha-up' }} ms-1 text-primary"></i>
                                    @endif
                                </button>

                                <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
                                    <li>
                                        <a class="dropdown-item mb-1" href="{{ $ordenUrl('estado_financiero_inicial', 'asc') }}">
                                            <i class="bi bi-sort-alpha-down"></i> Ordenar A → Z
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item mb-2" href="{{ $ordenUrl('estado_financiero_inicial', 'desc') }}">
                                            <i class="bi bi-sort-alpha-up"></i> Ordenar Z → A
                                        </a>
                                    </li>

                                    <li><hr class="dropdown-divider"></li>

                                    <li class="px-2">
                                        <form method="GET" action="{{ route('honorarios.mensual.index') }}">
                                            @foreach($queryFiltroColumna('cf_estado_financiero') as $key => $value)
                                                @if(is_array($value))
                                                    @foreach($value as $item)
                                                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                                    @endforeach
                                                @else
                                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                                @endif
                                            @endforeach

                                            <div class="mb-2">
                                                <select name="cf_estado_financiero" class="form-select form-select-sm">
                                                    <option value="">-- Seleccionar estado --</option>

                                                    @foreach($estadosFinancierosColumna as $estadoFinanciero)
                                                        <option value="{{ $estadoFinanciero }}"
                                                            {{ request('cf_estado_financiero') === $estadoFinanciero ? 'selected' : '' }}>
                                                            {{ $estadoFinanciero === 'Pago' ? 'Pagado' : $estadoFinanciero }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="d-flex justify-content-between">
                                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                                                    <i class="bi bi-filter"></i> Filtrar
                                                </button>

                                                @if($filtroActivo('cf_estado_financiero'))
                                                    <a href="{{ $limpiarFiltroUrl('cf_estado_financiero') }}" class="btn btn-outline-secondary btn-sm">
                                                        <i class="bi bi-x-circle"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </th>

                        {{-- RUT EMISOR --}}
                        <th>
                            <div class="dropdown d-inline">
                                <button
                                    class="btn btn-light btn-sm dropdown-toggle px-2 py-1 {{ $filtroActivo('cf_rut_emisor') ? 'hm-column-filter-active' : '' }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    RUT

                                    @if(($sortBy ?? null) === 'rut_emisor')
                                        <i class="bi {{ ($sortOrder ?? 'asc') === 'asc' ? 'bi-sort-alpha-down' : 'bi-sort-alpha-up' }} ms-1 text-primary"></i>
                                    @endif
                                </button>

                                <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
                                    <li>
                                        <a class="dropdown-item mb-1" href="{{ $ordenUrl('rut_emisor', 'asc') }}">
                                            <i class="bi bi-sort-alpha-down"></i> Ordenar A → Z
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item mb-2" href="{{ $ordenUrl('rut_emisor', 'desc') }}">
                                            <i class="bi bi-sort-alpha-up"></i> Ordenar Z → A
                                        </a>
                                    </li>

                                    <li><hr class="dropdown-divider"></li>

                                    <li class="px-2">
                                        <form method="GET" action="{{ route('honorarios.mensual.index') }}">
                                            @foreach($queryFiltroColumna('cf_rut_emisor') as $key => $value)
                                                @if(is_array($value))
                                                    @foreach($value as $item)
                                                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                                    @endforeach
                                                @else
                                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                                @endif
                                            @endforeach

                                            <div class="mb-2">
                                                <select name="cf_rut_emisor" class="form-select form-select-sm">
                                                    <option value="">-- Seleccionar RUT --</option>

                                                    @foreach($rutsEmisorColumna as $rutEmisor)
                                                        <option value="{{ $rutEmisor }}"
                                                            {{ request('cf_rut_emisor') === $rutEmisor ? 'selected' : '' }}>
                                                            {{ $rutEmisor }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="d-flex justify-content-between">
                                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                                                    <i class="bi bi-filter"></i> Filtrar
                                                </button>

                                                @if($filtroActivo('cf_rut_emisor'))
                                                    <a href="{{ $limpiarFiltroUrl('cf_rut_emisor') }}" class="btn btn-outline-secondary btn-sm">
                                                        <i class="bi bi-x-circle"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </th>

                        {{-- EMISOR: SIN FILTRO POR COLUMNA --}}
                        <th>Emisor</th>

                        {{-- FOLIO --}}
                        <th>
                            <div class="dropdown d-inline">
                                <button
                                    class="btn btn-light btn-sm dropdown-toggle px-2 py-1 {{ $filtroActivo('cf_folio') ? 'hm-column-filter-active' : '' }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    Folio

                                    @if(($sortBy ?? null) === 'folio')
                                        <i class="bi {{ ($sortOrder ?? 'asc') === 'asc' ? 'bi-sort-numeric-down' : 'bi-sort-numeric-up' }} ms-1 text-primary"></i>
                                    @endif
                                </button>

                                <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
                                    <li>
                                        <a class="dropdown-item mb-1" href="{{ $ordenUrl('folio', 'asc') }}">
                                            <i class="bi bi-sort-numeric-down"></i> Ordenar 0 → 9
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item mb-2" href="{{ $ordenUrl('folio', 'desc') }}">
                                            <i class="bi bi-sort-numeric-up"></i> Ordenar 9 → 0
                                        </a>
                                    </li>

                                    <li><hr class="dropdown-divider"></li>

                                    <li class="px-2">
                                        <form method="GET" action="{{ route('honorarios.mensual.index') }}">
                                            @foreach($queryFiltroColumna('cf_folio') as $key => $value)
                                                @if(is_array($value))
                                                    @foreach($value as $item)
                                                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                                    @endforeach
                                                @else
                                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                                @endif
                                            @endforeach

                                            <div class="mb-2">
                                                <select name="cf_folio" class="form-select form-select-sm">
                                                    <option value="">-- Seleccionar folio --</option>

                                                    @foreach($foliosColumna as $folioColumna)
                                                        <option value="{{ $folioColumna }}"
                                                            {{ (string) request('cf_folio') === (string) $folioColumna ? 'selected' : '' }}>
                                                            {{ $folioColumna }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="d-flex justify-content-between">
                                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                                                    <i class="bi bi-filter"></i> Filtrar
                                                </button>

                                                @if($filtroActivo('cf_folio'))
                                                    <a href="{{ $limpiarFiltroUrl('cf_folio') }}" class="btn btn-outline-secondary btn-sm">
                                                        <i class="bi bi-x-circle"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </th>

                        {{-- SERVICIO: SIN FILTRO POR COLUMNA --}}
                        <th>Servicio</th>

                        {{-- SERVICIO FINAL: SIN FILTRO POR COLUMNA --}}
                        <th>Servicio Final</th>

                        {{-- FECHA EMISIÓN --}}
                        <th>
                            <div class="dropdown d-inline">
                                <button
                                    class="btn btn-light btn-sm dropdown-toggle px-2 py-1 {{ $filtroActivo('cf_fecha_emision') ? 'hm-column-filter-active' : '' }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    Fecha Emisión

                                    @if(($sortBy ?? null) === 'fecha_emision')
                                        <i class="bi {{ ($sortOrder ?? 'asc') === 'asc' ? 'bi-sort-down-alt' : 'bi-sort-up-alt' }} ms-1 text-primary"></i>
                                    @endif
                                </button>

                                <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
                                    <li>
                                        <a class="dropdown-item mb-1" href="{{ $ordenUrl('fecha_emision', 'asc') }}">
                                            <i class="bi bi-sort-down-alt"></i> Más antigua primero
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item mb-2" href="{{ $ordenUrl('fecha_emision', 'desc') }}">
                                            <i class="bi bi-sort-up-alt"></i> Más reciente primero
                                        </a>
                                    </li>

                                    <li><hr class="dropdown-divider"></li>

                                    <li class="px-2">
                                        <form method="GET" action="{{ route('honorarios.mensual.index') }}">
                                            @foreach($queryFiltroColumna('cf_fecha_emision') as $key => $value)
                                                @if(is_array($value))
                                                    @foreach($value as $item)
                                                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                                    @endforeach
                                                @else
                                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                                @endif
                                            @endforeach

                                            <div class="mb-2">
                                                <select name="cf_fecha_emision" class="form-select form-select-sm">
                                                    <option value="">-- Seleccionar fecha --</option>

                                                    @foreach($fechasEmisionColumna as $fechaEmision)
                                                        <option value="{{ $fechaEmision }}"
                                                            {{ request('cf_fecha_emision') === $fechaEmision ? 'selected' : '' }}>
                                                            {{ \Carbon\Carbon::parse($fechaEmision)->format('d-m-Y') }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="d-flex justify-content-between">
                                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                                                    <i class="bi bi-filter"></i> Filtrar
                                                </button>

                                                @if($filtroActivo('cf_fecha_emision'))
                                                    <a href="{{ $limpiarFiltroUrl('cf_fecha_emision') }}" class="btn btn-outline-secondary btn-sm">
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
                                <button
                                    class="btn btn-light btn-sm dropdown-toggle px-2 py-1 {{ $filtroActivo('cf_fecha_vencimiento') ? 'hm-column-filter-active' : '' }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    Fecha Vencimiento

                                    @if(($sortBy ?? null) === 'fecha_vencimiento')
                                        <i class="bi {{ ($sortOrder ?? 'asc') === 'asc' ? 'bi-sort-down-alt' : 'bi-sort-up-alt' }} ms-1 text-primary"></i>
                                    @endif
                                </button>

                                <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
                                    <li>
                                        <a class="dropdown-item mb-1" href="{{ $ordenUrl('fecha_vencimiento', 'asc') }}">
                                            <i class="bi bi-sort-down-alt"></i> Más antigua primero
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item mb-2" href="{{ $ordenUrl('fecha_vencimiento', 'desc') }}">
                                            <i class="bi bi-sort-up-alt"></i> Más reciente primero
                                        </a>
                                    </li>

                                    <li><hr class="dropdown-divider"></li>

                                    <li class="px-2">
                                        <form method="GET" action="{{ route('honorarios.mensual.index') }}">
                                            @foreach($queryFiltroColumna('cf_fecha_vencimiento') as $key => $value)
                                                @if(is_array($value))
                                                    @foreach($value as $item)
                                                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                                    @endforeach
                                                @else
                                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                                @endif
                                            @endforeach

                                            <div class="mb-2">
                                                <select name="cf_fecha_vencimiento" class="form-select form-select-sm">
                                                    <option value="">-- Seleccionar fecha --</option>

                                                    @foreach($fechasVencimientoColumna as $fechaVencimiento)
                                                        <option value="{{ $fechaVencimiento }}"
                                                            {{ request('cf_fecha_vencimiento') === $fechaVencimiento ? 'selected' : '' }}>
                                                            {{ \Carbon\Carbon::parse($fechaVencimiento)->format('d-m-Y') }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="d-flex justify-content-between">
                                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                                                    <i class="bi bi-filter"></i> Filtrar
                                                </button>

                                                @if($filtroActivo('cf_fecha_vencimiento'))
                                                    <a href="{{ $limpiarFiltroUrl('cf_fecha_vencimiento') }}" class="btn btn-outline-secondary btn-sm">
                                                        <i class="bi bi-x-circle"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </th>

                        {{-- ESTADO SII --}}
                        <th>
                            <div class="dropdown d-inline">
                                <button
                                    class="btn btn-light btn-sm dropdown-toggle px-2 py-1 {{ $filtroActivo('cf_estado_sii') ? 'hm-column-filter-active' : '' }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    Estado SII

                                    @if(($sortBy ?? null) === 'estado')
                                        <i class="bi {{ ($sortOrder ?? 'asc') === 'asc' ? 'bi-sort-alpha-down' : 'bi-sort-alpha-up' }} ms-1 text-primary"></i>
                                    @endif
                                </button>

                                <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
                                    <li>
                                        <a class="dropdown-item mb-1" href="{{ $ordenUrl('estado', 'asc') }}">
                                            <i class="bi bi-sort-alpha-down"></i> Ordenar A → Z
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item mb-2" href="{{ $ordenUrl('estado', 'desc') }}">
                                            <i class="bi bi-sort-alpha-up"></i> Ordenar Z → A
                                        </a>
                                    </li>

                                    <li><hr class="dropdown-divider"></li>

                                    <li class="px-2">
                                        <form method="GET" action="{{ route('honorarios.mensual.index') }}">
                                            @foreach($queryFiltroColumna('cf_estado_sii') as $key => $value)
                                                @if(is_array($value))
                                                    @foreach($value as $item)
                                                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                                    @endforeach
                                                @else
                                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                                @endif
                                            @endforeach

                                            <div class="mb-2">
                                                <select name="cf_estado_sii" class="form-select form-select-sm">
                                                    <option value="">-- Seleccionar estado --</option>

                                                    @foreach($estadosSiiColumna as $estadoSii)
                                                        <option value="{{ $estadoSii }}"
                                                            {{ request('cf_estado_sii') === $estadoSii ? 'selected' : '' }}>
                                                            {{ $estadoSii }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="d-flex justify-content-between">
                                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                                                    <i class="bi bi-filter"></i> Filtrar
                                                </button>

                                                @if($filtroActivo('cf_estado_sii'))
                                                    <a href="{{ $limpiarFiltroUrl('cf_estado_sii') }}" class="btn btn-outline-secondary btn-sm">
                                                        <i class="bi bi-x-circle"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </th>

                        {{-- FECHA ANULACIÓN --}}
                        <th>
                            <div class="dropdown d-inline">
                                <button
                                    class="btn btn-light btn-sm dropdown-toggle px-2 py-1 {{ $filtroActivo('cf_fecha_anulacion') ? 'hm-column-filter-active' : '' }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    Fecha Anulación

                                    @if(($sortBy ?? null) === 'fecha_anulacion')
                                        <i class="bi {{ ($sortOrder ?? 'asc') === 'asc' ? 'bi-sort-down-alt' : 'bi-sort-up-alt' }} ms-1 text-primary"></i>
                                    @endif
                                </button>

                                <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
                                    <li>
                                        <a class="dropdown-item mb-1" href="{{ $ordenUrl('fecha_anulacion', 'asc') }}">
                                            <i class="bi bi-sort-down-alt"></i> Más antigua primero
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item mb-2" href="{{ $ordenUrl('fecha_anulacion', 'desc') }}">
                                            <i class="bi bi-sort-up-alt"></i> Más reciente primero
                                        </a>
                                    </li>

                                    <li><hr class="dropdown-divider"></li>

                                    <li class="px-2">
                                        <form method="GET" action="{{ route('honorarios.mensual.index') }}">
                                            @foreach($queryFiltroColumna('cf_fecha_anulacion') as $key => $value)
                                                @if(is_array($value))
                                                    @foreach($value as $item)
                                                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                                    @endforeach
                                                @else
                                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                                @endif
                                            @endforeach

                                            <div class="mb-2">
                                                <select name="cf_fecha_anulacion" class="form-select form-select-sm">
                                                    <option value="">-- Seleccionar fecha --</option>

                                                    @foreach($fechasAnulacionColumna as $fechaAnulacion)
                                                        <option value="{{ $fechaAnulacion }}"
                                                            {{ request('cf_fecha_anulacion') === $fechaAnulacion ? 'selected' : '' }}>
                                                            {{ \Carbon\Carbon::parse($fechaAnulacion)->format('d-m-Y') }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="d-flex justify-content-between">
                                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                                                    <i class="bi bi-filter"></i> Filtrar
                                                </button>

                                                @if($filtroActivo('cf_fecha_anulacion'))
                                                    <a href="{{ $limpiarFiltroUrl('cf_fecha_anulacion') }}" class="btn btn-outline-secondary btn-sm">
                                                        <i class="bi bi-x-circle"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </th>

                        {{-- MONTO PAGADO --}}
                        <th class="text-end">
                            <div class="dropdown d-inline">
                                <button
                                    class="btn btn-light btn-sm dropdown-toggle px-2 py-1 {{ $filtroActivo('cf_monto_pagado') ? 'hm-column-filter-active' : '' }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    Monto Pagado

                                    @if(($sortBy ?? null) === 'monto_pagado')
                                        <i class="bi {{ ($sortOrder ?? 'asc') === 'asc' ? 'bi-sort-numeric-down' : 'bi-sort-numeric-up' }} ms-1 text-primary"></i>
                                    @endif
                                </button>

                                <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
                                    <li>
                                        <a class="dropdown-item mb-1" href="{{ $ordenUrl('monto_pagado', 'asc') }}">
                                            <i class="bi bi-sort-numeric-down"></i> Menor a mayor
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item mb-2" href="{{ $ordenUrl('monto_pagado', 'desc') }}">
                                            <i class="bi bi-sort-numeric-up"></i> Mayor a menor
                                        </a>
                                    </li>

                                    <li><hr class="dropdown-divider"></li>

                                    <li class="px-2">
                                        <form method="GET" action="{{ route('honorarios.mensual.index') }}">
                                            @foreach($queryFiltroColumna('cf_monto_pagado') as $key => $value)
                                                @if(is_array($value))
                                                    @foreach($value as $item)
                                                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                                    @endforeach
                                                @else
                                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                                @endif
                                            @endforeach

                                            <div class="mb-2">
                                                <select name="cf_monto_pagado" class="form-select form-select-sm">
                                                    <option value="">-- Seleccionar monto --</option>

                                                    @foreach($montosPagadosColumna as $montoPagadoColumna)
                                                        <option value="{{ $montoPagadoColumna }}"
                                                            {{ (string) request('cf_monto_pagado') === (string) $montoPagadoColumna ? 'selected' : '' }}>
                                                            ${{ number_format($montoPagadoColumna, 0, ',', '.') }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="d-flex justify-content-between">
                                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                                                    <i class="bi bi-filter"></i> Filtrar
                                                </button>

                                                @if($filtroActivo('cf_monto_pagado'))
                                                    <a href="{{ $limpiarFiltroUrl('cf_monto_pagado') }}" class="btn btn-outline-secondary btn-sm">
                                                        <i class="bi bi-x-circle"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </th>

                        {{-- SALDO PENDIENTE --}}
                        <th class="text-end">
                            <div class="dropdown d-inline">
                                <button
                                    class="btn btn-light btn-sm dropdown-toggle px-2 py-1 {{ $filtroActivo('cf_saldo_pendiente') ? 'hm-column-filter-active' : '' }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    Saldo pendiente

                                    @if(($sortBy ?? null) === 'saldo_pendiente')
                                        <i class="bi {{ ($sortOrder ?? 'asc') === 'asc' ? 'bi-sort-numeric-down' : 'bi-sort-numeric-up' }} ms-1 text-primary"></i>
                                    @endif
                                </button>

                                <ul class="dropdown-menu shadow-sm small p-2" style="min-width: 230px;">
                                    <li>
                                        <a class="dropdown-item mb-1" href="{{ $ordenUrl('saldo_pendiente', 'asc') }}">
                                            <i class="bi bi-sort-numeric-down"></i> Menor a mayor
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item mb-2" href="{{ $ordenUrl('saldo_pendiente', 'desc') }}">
                                            <i class="bi bi-sort-numeric-up"></i> Mayor a menor
                                        </a>
                                    </li>

                                    <li><hr class="dropdown-divider"></li>

                                    <li class="px-2">
                                        <form method="GET" action="{{ route('honorarios.mensual.index') }}">
                                            @foreach($queryFiltroColumna('cf_saldo_pendiente') as $key => $value)
                                                @if(is_array($value))
                                                    @foreach($value as $item)
                                                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                                    @endforeach
                                                @else
                                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                                @endif
                                            @endforeach

                                            <div class="mb-2">
                                                <select name="cf_saldo_pendiente" class="form-select form-select-sm">
                                                    <option value="">-- Seleccionar saldo --</option>

                                                    @foreach($saldosPendientesColumna as $saldoPendienteColumna)
                                                        <option value="{{ $saldoPendienteColumna }}"
                                                            {{ (string) request('cf_saldo_pendiente') === (string) $saldoPendienteColumna ? 'selected' : '' }}>
                                                            ${{ number_format($saldoPendienteColumna, 0, ',', '.') }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="d-flex justify-content-between">
                                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 me-1">
                                                    <i class="bi bi-filter"></i> Filtrar
                                                </button>

                                                @if($filtroActivo('cf_saldo_pendiente'))
                                                    <a href="{{ $limpiarFiltroUrl('cf_saldo_pendiente') }}" class="btn btn-outline-secondary btn-sm">
                                                        <i class="bi bi-x-circle"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </th>

                        {{-- FECHA ÚLTIMO MOVIMIENTO: SIN FILTRO POR COLUMNA --}}
                        <th>Fecha Último Movimiento</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($registros as $r)
                        @php
                            $estadoActual = $r->estado_financiero ?? $r->estado_financiero_inicial;
                            $estaVencido = $r->fecha_vencimiento && $r->fecha_vencimiento->isPast();
                            $tieneCobranza = (bool) $r->cobranzaCompra;
                            $servicioCobranza = $tieneCobranza ? $r->cobranzaCompra->servicio : null;
                            $esServicioOtro = $tieneCobranza && $servicioCobranza === 'Otro';
                            $montoPagado = (int) ($r->monto_pagado ?? 0);
                            $saldoPendiente = (int) ($r->saldo_pendiente ?? 0);
                        @endphp

                        <tr>

                            <td class="hm-nowrap text-center {{ ($r->pagoProgramado && $saldoPendiente > 0 && $r->pagos->isEmpty() && $r->prontoPagos->isEmpty()) ? 'hm-programado' : '' }}">
                                @if($saldoPendiente > 0 && $r->estado !== 'NULA')
                                    <input type="checkbox"
                                        class="chk-honorario"
                                        value="{{ $r->id }}"
                                        data-id="{{ $r->id }}"
                                        data-empresa="{{ $r->empresa->Nombre }}"
                                        data-rut="{{ $r->rut_emisor }}"
                                        data-emisor="{{ $r->razon_social_emisor }}"
                                        data-folio="{{ $r->folio }}"
                                        data-fecha-emision="{{ $r->fecha_emision?->format('d-m-Y') }}"
                                        data-fecha-vencimiento="{{ $r->fecha_vencimiento?->format('d-m-Y') }}"
                                        data-monto="{{ $montoPagado }}"
                                        data-saldo="{{ $saldoPendiente }}">
                                @else
                                    <input type="checkbox" disabled>
                                @endif
                            </td>

                            <td class="hm-nowrap">
                                <span class="hm-ellipsis-sm" title="{{ $r->empresa->Nombre }}">
                                    {{ $r->empresa->Nombre }}
                                </span>
                            </td>

                            <td class="hm-nowrap">
                                @if($r->tipo_boleta === 'Boleta de Terceros')
                                    <span class="hm-chip hm-chip-info">
                                        {{ $r->tipo_boleta }}
                                    </span>
                                @else
                                    <span class="hm-chip hm-chip-ok">
                                        {{ $r->tipo_boleta ?? 'Boleta Honorario' }}
                                    </span>
                                @endif
                            </td>

                            <td class="hm-nowrap">
                                <span class="hm-chip {{ $estaVencido ? 'hm-chip-bad' : 'hm-chip-ok' }}">
                                    {{ $estadoActual }}
                                </span>
                            </td>

                            <td class="hm-nowrap">{{ $r->rut_emisor }}</td>

                            <td>
                                @if($tieneCobranza)
                                    <span class="hm-ellipsis" title="{{ $r->cobranzaCompra->razon_social }}">
                                        {{ $r->cobranzaCompra->razon_social }}
                                    </span>
                                @else
                                    <span class="text-muted">Sin proveedor</span>
                                @endif
                            </td>

                            <td class="hm-nowrap">
                                <a href="{{ route('honorarios.mensual.show', $r->id) }}" class="fw-semibold text-decoration-none">
                                    {{ $r->folio }}
                                </a>
                            </td>

                            <td class="hm-nowrap">
                                @if($tieneCobranza)
                                    {{ $r->cobranzaCompra->servicio }}
                                @else
                                    <span class="text-muted">Sin Servicio</span>
                                @endif
                            </td>

                            <td class="hm-nowrap">
                                @if($esServicioOtro)
                                    <a href="#"
                                    class="hm-link-editable"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalServicio"
                                    data-update-url="{{ route('honorarios.mensual.servicio.update', $r->id) }}"
                                    data-folio="{{ $r->folio }}"
                                    data-servicio="{{ $r->servicio_manual ?? '' }}"
                                    title="Definir servicio">

                                        @if($r->servicio_manual)
                                            <span class="hm-chip hm-chip-info">
                                                {{ $r->servicio_manual }}
                                            </span>
                                        @else
                                            <span class="text-muted">Otro</span>
                                        @endif
                                    </a>
                                @else
                                    {{ $r->servicio_final ?? '—' }}
                                @endif
                            </td>

                            <td class="hm-nowrap">{{ $r->fecha_emision?->format('d-m-Y') }}</td>
                            <td class="hm-nowrap">{{ $r->fecha_vencimiento?->format('d-m-Y') }}</td>

                            <td class="hm-nowrap">
                                <span class="{{ $r->estado === 'ANULADA' ? 'text-danger' : 'text-success' }}">
                                    {{ $r->estado }}
                                </span>
                            </td>

                            <td class="hm-nowrap">{{ $r->fecha_anulacion?->format('d-m-Y') }}</td>

                            <td class="hm-nowrap text-end fw-semibold">
                                {{ number_format($montoPagado, 0, ',', '.') }}
                            </td>

                            <td class="hm-nowrap text-end fw-semibold {{ $saldoPendiente === 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($saldoPendiente, 0, ',', '.') }}
                            </td>

                            <td class="hm-nowrap">
                                <div>
                                    {{ $r->fecha_ultima_gestion
                                        ? \Carbon\Carbon::parse($r->fecha_ultima_gestion)->format('d-m-Y')
                                        : '-' }}
                                </div>

                                @if(
                                    $r->pagoProgramado &&
                                    $r->pagos->isEmpty() &&
                                    $r->prontoPagos->isEmpty() &&
                                    (int) $r->saldo_pendiente > 0
                                )
                                    <div class="small text-primary fw-semibold mt-1">
                                        Próx. pago: {{ $r->pagoProgramado->fecha_programada?->format('d-m-Y') }}
                                    </div>
                                @endif
                            </td>

                        </tr>
                    @endforeach
                </tbody>

            </x-finanzas.plain-table>

            <div class="py-3 d-flex justify-content-center">
                {{ $registros->links('pagination::bootstrap-4') }}
            </div>






        @endif
    </div>

    <div class="modal fade" id="modalServicio" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formServicioUpdate">
                @csrf
                @method('PATCH')

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalServicioTitle">Definir servicio</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Servicio</label>
                            <input type="text"
                                   name="servicio_manual"
                                   id="inputServicioManual"
                                   class="form-control"
                                   required>
                        </div>
                        <div class="hm-subtle">
                            * Solo aplica cuando el servicio del proveedor es <strong>“Otro”</strong>.
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button class="btn btn-primary">
                            Guardar
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>

</div>

@include('boleta_mensual._modal_pago_masivo')
@include('boleta_mensual._modal_proximo_pago')
@include('boleta_mensual._modal_create_proveedor_honorarios')


@vite('resources/js/index.js')
@vite('resources/js/boleta_mensual_index.js')

@endsection