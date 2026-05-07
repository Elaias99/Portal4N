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

    @if(session('preview'))
        @php
            $preview = session('preview');
            $proveedoresFaltantes = $preview['proveedores_faltantes'] ?? [];
            $hayFaltantes = count($proveedoresFaltantes) > 0;
        @endphp

        <div class="hm-block hm-block-warning mb-3">

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="hm-block-title mb-0">
                    Regularización previa a la importación
                </div>

                <form action="{{ route('honorarios.mensual.store') }}" method="POST" class="m-0">
                    @csrf
                    <input type="hidden" name="data" value="{{ base64_encode(json_encode($preview)) }}">
                    <button class="btn btn-success"
                            {{ $hayFaltantes ? 'disabled' : '' }}>
                        Confirmar y guardar honorarios
                    </button>
                </form>
            </div>

            @if($hayFaltantes)

                <div class="alert alert-danger mt-3">
                    <strong>Proveedores no registrados detectados.</strong>
                    Debe crearlos antes de continuar.
                </div>

                <form action="{{ route('honorarios.mensual.proveedores.store') }}" method="POST">
                    @csrf

                    <div class="table-responsive mt-3">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>RUT</th>
                                    <th>Razón Social</th>
                                    <th>Servicio</th>
                                    <th>Créditos (días)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($proveedoresFaltantes as $index => $p)
                                    <tr>
                                        <td>
                                            <input type="text"
                                                class="form-control"
                                                value="{{ $p['rut_emisor'] }}"
                                                readonly>
                                            <input type="hidden"
                                                name="proveedores[{{ $index }}][rut_cliente]"
                                                value="{{ $p['rut_emisor'] }}">
                                        </td>

                                        <td>
                                            <input type="text"
                                                class="form-control"
                                                value="{{ $p['razon_social_emisor'] }}"
                                                readonly>
                                            <input type="hidden"
                                                name="proveedores[{{ $index }}][razon_social]"
                                                value="{{ $p['razon_social_emisor'] }}">
                                        </td>

                                        <td>
                                            <input type="text"
                                                name="proveedores[{{ $index }}][servicio]"
                                                class="form-control"
                                                placeholder="Ej: Servicios profesionales"
                                                required>
                                        </td>

                                        <td>
                                            <input type="number"
                                                name="proveedores[{{ $index }}][creditos]"
                                                class="form-control"
                                                min="0"
                                                placeholder="Ej: 30"
                                                required>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <button class="btn btn-primary">
                            Crear proveedores
                        </button>
                    </div>

                </form>

            @endif

            <div class="mt-4">
                <div class="row g-2">
                    <div class="col-12 col-md-6">
                        <div class="hm-subtle">Contribuyente</div>
                        <div class="fw-semibold">{{ $preview['meta']['razon_social'] }}</div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="hm-subtle">RUT</div>
                        <div class="fw-semibold hm-nowrap">
                            {{ $preview['meta']['rut_contribuyente'] }}
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="hm-subtle">Periodo</div>
                        <div class="fw-semibold">
                            {{ \Carbon\Carbon::create()->month($preview['meta']['mes'])->translatedFormat('F') }}
                            {{ $preview['meta']['anio'] }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive mt-3">
                <table class="table table-sm table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>RUT Emisor</th>
                            <th>Razón Social</th>
                            <th class="text-end">Pagado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($preview['registros'] as $r)
                            <tr>
                                <td>{{ $r['folio'] }}</td>
                                <td>{{ $r['fecha_emision'] }}</td>
                                <td>{{ $r['estado'] }}</td>
                                <td>{{ $r['rut_emisor'] }}</td>
                                <td>{{ $r['razon_social_emisor'] }}</td>
                                <td class="text-end">
                                    {{ number_format($r['monto_pagado'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    @endif

    <div class="hm-table-wrap">


        @if($registros->isEmpty())
            <div class="p-3">
                <p class="text-muted mb-0">No hay honorarios registrados.</p>
            </div>
        @else


            <x-finanzas.plain-table>

                <thead>
                    <tr>
                        <th class="hm-nowrap text-center">
                            <input type="checkbox" id="check-all-honorarios">
                        </th>
                        <th>Empresa</th>
                        <th>Tipo boleta</th>
                        <th>Estado</th>
                        <th>RUT</th>
                        <th>Emisor</th>
                        <th>Folio</th>
                        <th>Servicio</th>
                        <th>Servicio Final</th>
                        <th>Fecha Emisión</th>
                        <th>Fecha Vencimiento</th>
                        <th>Estado SII</th>
                        <th>Fecha Anulación</th>
                        <th class="text-end">Monto Pagado</th>
                        <th class="text-end">Saldo pendiente</th>
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


@vite('resources/js/index.js')
@vite('resources/js/boleta_mensual_index.js')


@endsection