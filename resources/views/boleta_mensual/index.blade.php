{{-- resources/views/boleta_mensual/index.blade.php --}}
@extends('layouts.app')

@vite('resources/css/boleta_mensual.css')

@section('content')

<style>


    /* Documento con pago programado */

    .hm-programado {
        background-color: #e9f2ff; /* azul muy suave */
        border-left: 3px solid #3b82f6;
    }


</style>


<div class="container-fluid py-3 hm">

    {{-- =========================
        1) ENCABEZADO (sin cards)
    ========================== --}}
    <div class="hm-header mb-3">
        <div>
            <h1 class="hm-title">Boletas Honorarios</h1>
            <div class="hm-subtle">Búsqueda, revisión y acciones sobre honorarios mensuales.</div>
        </div>

        {{-- Acciones de navegación (no mezcladas con filtros) --}}
        <div class="hm-actions">
            <a href="{{ route('cobranzas-compras.index', ['origen' => 'honorarios']) }}"
            class="btn btn-outline-secondary">
                Detalle Proveedor
            </a>


            <a href="{{ route('movimientos.honorarios.historial') }}" class="btn btn-outline-secondary">
                Movimientos
            </a>

            <a href="{{ route('honorarios.mensual.calendario') }}"
                class="btn btn-outline-secondary">
                    Calendario Corporativo
            </a>

        </div>
    </div>

    {{-- =========================
        2) FILTROS (sin card)
    ========================== --}}
    @php
        $filtrosActivos = collect([
            request('empresa_id'),
            request('anio'),
            request('mes'),
            request('razon_social_emisor'),
            request('rut_emisor'),
            request('folio'),
            request('fecha_emision_desde'),
            request('fecha_vencimiento_desde'),
            request('saldo_tipo'),
            request('saldo_monto'),
        ])->filter(fn($v) => $v !== null && $v !== '')->count();
    @endphp

    <details class="hm-filters mb-3" open>
        <summary>
            <div class="hm-summary-left">
                <span class="fw-semibold">Filtros de búsqueda</span>
                @if($filtrosActivos)
                    <span class="hm-summary-badge">{{ $filtrosActivos }} activo(s)</span>
                @endif
            </div>
            <span class="text-muted">Abrir / cerrar</span>
        </summary>

        <div class="hm-filters-body">


            @php
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


            {{-- =====================
                FORM 1: FILTROS (GET)
            ====================== --}}
            <form method="GET" action="{{ route('honorarios.mensual.index') }}">
                <div class="row g-3 align-items-end">

                    {{-- Empresa --}}
                    <div class="col-12 col-md-3 col-lg-1">
                        <label class="form-label">Empresa</label>
                        <select name="empresa_id" class="form-select">
                            <option value="">Todas</option>
                            @foreach($empresas as $empresa)
                                <option value="{{ $empresa->id }}"
                                    {{ request('empresa_id') == $empresa->id ? 'selected' : '' }}>
                                    {{ $empresa->Nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Año --}}
                    <div class="col-6 col-md-2 col-lg-1">
                        <label class="form-label">Año</label>
                        <select name="anio" class="form-select">
                            <option value="">Todos</option>
                            @foreach($anios as $anio)
                                <option value="{{ $anio }}"
                                    {{ request('anio') == $anio ? 'selected' : '' }}>
                                    {{ $anio }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Mes --}}
                    <div class="col-6 col-md-2 col-lg-1">
                        <label class="form-label">Mes</label>
                        <select name="mes" class="form-select">
                            <option value="">Todos</option>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}"
                                    {{ request('mes') == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    {{-- Razón Social --}}
                    <div class="col-12 col-md-5 col-lg-2">
                        <label class="form-label">Razón social emisor</label>
                        <input type="text"
                            name="razon_social_emisor"
                            class="form-control"
                            value="{{ request('razon_social_emisor') }}">
                    </div>

                    {{-- RUT --}}
                    <div class="col-12 col-md-3 col-lg-1">
                        <label class="form-label">RUT emisor</label>
                        <input type="text"
                            name="rut_emisor"
                            class="form-control"
                            value="{{ request('rut_emisor') }}">
                    </div>

                    {{-- Folio --}}
                    <div class="col-12 col-md-3 col-lg-1">
                        <label class="form-label">Folio</label>
                        <input type="text"
                            name="folio"
                            class="form-control"
                            value="{{ request('folio') }}">
                    </div>

                    {{-- Fecha Documento --}}
                    <div class="col-12 col-md-3 col-lg-2">
                        <label class="form-label">Fecha documento</label>
                        <input type="date"
                            name="fecha_emision_desde"
                            class="form-control"
                            value="{{ request('fecha_emision_desde') }}">
                    </div>

                    {{-- Servicio --}}
                    <div class="col-12 col-md-3 col-lg-2">
                        <label class="form-label">Servicio</label>

                        <div class="dropdown">
                            <button class="btn btn-outline-secondary w-100 dropdown-toggle"
                                    type="button"
                                    data-bs-toggle="dropdown">
                                Buscar servicio por
                            </button>

                            <div class="dropdown-menu p-3" style="min-width: 260px;">

                                {{-- Tipo --}}
                                <div class="mb-2">
                                    <label class="form-label">Tipo</label>
                                    <select name="servicio_tipo" class="form-select">
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

                                {{-- Valor --}}
                                <div>
                                    <label class="form-label">Servicio</label>

                                    {{-- Select proveedor --}}
                                    <select name="servicio_valor"
                                            id="servicioProveedorSelect"
                                            class="form-select d-none">
                                        <option value="">Seleccione servicio</option>
                                        @foreach($serviciosProveedor as $servicio)
                                            <option value="{{ $servicio }}"
                                                {{ request('servicio_valor') === $servicio ? 'selected' : '' }}>
                                                {{ $servicio }}
                                            </option>
                                        @endforeach
                                    </select>

                                    {{-- Input manual --}}
                                    <input type="text"
                                        name="servicio_valor"
                                        id="servicioManualInput"
                                        class="form-control d-none"
                                        placeholder="Ej: Agencias, Courier…"
                                        value="{{ request('servicio_valor') }}">
                                </div>


                            </div>
                        </div>
                    </div>



                    {{-- Fecha Vencimiento --}}
                    <div class="col-12 col-md-3 col-lg-2">
                        <label class="form-label">Fecha vencimiento</label>
                        <input type="date"
                            name="fecha_vencimiento_desde"
                            class="form-control"
                            value="{{ request('fecha_vencimiento_desde') }}">
                    </div>

                    {{-- Saldo --}}
                    <div class="col-12 col-md-3 col-lg-2">
                    <label class="form-label">Saldo</label>

                    <div class="dropdown w-100 keep-open-on-drag">
                        <button
                        class="form-control dropdown-toggle text-start"
                        type="button"
                        data-bs-toggle="dropdown"
                        data-bs-auto-close="outside"
                        aria-expanded="false"
                        >
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
                            min="0"
                        >
                        </div>
                    </div>
                    </div>

                    {{-- Acciones filtros --}}
                    <div class="col-12 col-lg-4 d-flex justify-content-lg-end align-items-end">
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary px-4">
                                Buscar
                            </button>

                            <a href="{{ route('honorarios.mensual.index') }}"
                            class="btn btn-outline-secondary px-4">
                                Limpiar
                            </a>

                            {{-- Nuevo botón Pagar --}}
                            <button
                                type="button"
                                id="btn-pagar-seleccionados"
                                class="btn btn-success">
                                Pagar
                            </button>

                            <button
                                type="button"
                                id="btn-proximo-pago-seleccionados"
                                class="btn btn-outline-primary">
                                Definir próximo pago
                            </button>



                        </div>
                    </div>

                </div>
            </form>

            {{-- =====================
                IMPORTADOR (POST)
            ====================== --}}
            <hr class="my-4">

            <div class="row g-3 align-items-end">

                {{-- IMPORTAR --}}
                <form action="{{ route('honorarios.mensual.import') }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="row g-3 align-items-end">
                    @csrf

                    {{-- Input archivo --}}
                    <div class="col-12 col-md-8">
                        <label class="form-label fw-semibold">
                            Importar archivo SII (file_informeMensualREC)
                        </label>

                        <input type="file"
                            name="archivo"
                            class="form-control"
                            required>
                    </div>

                    {{-- Botón --}}
                    <div class="col-6 col-md-1">
                        <button type="submit"
                                class="btn btn-success w-100">
                            Importar
                        </button>
                    </div>

                </form>


                {{-- Form EXPORTAR --}}
                <div class="col-6 col-md-1">
                    <form method="GET"
                        action="{{ route('honorarios.mensual.export') }}"
                        id="form-exportar">
                        
                        @foreach(request()->query() as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach

                        <button type="submit"
                                class="btn btn-outline-success w-100"
                                id="btn-exportar">
                            <span id="texto-exportar">Exportar</span>
                            <span id="spinner-exportar"
                                class="spinner-border spinner-border-sm ms-2 d-none"
                                role="status"
                                aria-hidden="true"></span>
                        </button>
                    </form>
                </div>

            </div>






            

        </div>
    </details>


    {{-- =========================
        3) MENSAJES + PREVIEW (sin cards)
    ========================== --}}
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

                {{-- BOTÓN CONFIRMAR HONORARIOS --}}
                <form action="{{ route('honorarios.mensual.store') }}" method="POST" class="m-0">
                    @csrf
                    <input type="hidden" name="data" value="{{ base64_encode(json_encode($preview)) }}">
                    <button class="btn btn-success"
                            {{ $hayFaltantes ? 'disabled' : '' }}>
                        Confirmar y guardar honorarios
                    </button>
                </form>
            </div>

            {{-- ============================= --}}
            {{-- BLOQUE PROVEEDORES FALTANTES --}}
            {{-- ============================= --}}
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
                                        {{-- RUT --}}
                                        <td>
                                            <input type="text"
                                                class="form-control"
                                                value="{{ $p['rut_emisor'] }}"
                                                readonly>
                                            <input type="hidden"
                                                name="proveedores[{{ $index }}][rut_cliente]"
                                                value="{{ $p['rut_emisor'] }}">
                                        </td>

                                        {{-- RAZÓN SOCIAL --}}
                                        <td>
                                            <input type="text"
                                                class="form-control"
                                                value="{{ $p['razon_social_emisor'] }}"
                                                readonly>
                                            <input type="hidden"
                                                name="proveedores[{{ $index }}][razon_social]"
                                                value="{{ $p['razon_social_emisor'] }}">
                                        </td>

                                        {{-- SERVICIO --}}
                                        <td>
                                            <input type="text"
                                                name="proveedores[{{ $index }}][servicio]"
                                                class="form-control"
                                                placeholder="Ej: Servicios profesionales"
                                                required>
                                        </td>

                                        {{-- CREDITOS --}}
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

            {{-- ============================= --}}
            {{-- TABLA INFORMATIVA DE ARCHIVO --}}
            {{-- ============================= --}}
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

            {{-- TABLA DOCUMENTOS (solo informativa ahora) --}}
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


    {{-- =========================
        4) TABLA PRINCIPAL “PLANA” (sin cards)
    ========================== --}}
    <div class="hm-table-wrap">

        <div class="hm-table-toolbar">
            <p class="hm-table-title">Reporte Honorarios Mensuales</p>
            <div class="hm-subtle">
                @if(method_exists($registros, 'total'))
                    Mostrando {{ $registros->count() }} de {{ $registros->total() }}
                @else
                    Registros: {{ $registros->count() }}
                @endif
            </div>
        </div>

        @if($registros->isEmpty())
            <div class="p-3">
                <p class="text-muted mb-0">No hay honorarios registrados.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover table-bordered align-middle">

                    <thead>
                        <tr>
                            {{-- Checkbox --}}
                            <th class="hm-nowrap text-center">
                                <input type="checkbox" id="check-all-honorarios">
                            </th>
                            <th class="hm-nowrap">Empresa</th>
                            <th class="hm-nowrap">Estado</th>
                            <th class="hm-nowrap">RUT</th>
                            <th class="hm-nowrap">Emisor</th>
                            <th class="hm-nowrap">Folio</th>
                            <th class="hm-nowrap">Servicio</th>
                            <th class="hm-nowrap">Servicio Final</th>
                            <th class="hm-nowrap">Fecha Emisión</th>
                            <th class="hm-nowrap">Fecha Vencimiento</th>
                            <th class="hm-nowrap">Estado SII</th>
                            <th class="hm-nowrap">Fecha Anulación</th>
                            <th class="hm-nowrap text-end">Monto Pagado</th>
                            <th class="hm-nowrap text-end">Saldo pendiente</th>
                            <th class="hm-nowrap">Fecha Último Movimiento</th>
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

                                {{-- Checkbox --}}
                                <td class="hm-nowrap text-center 
                                {{ ($r->pagoProgramado && $saldoPendiente > 0 && $r->pagos->isEmpty() && $r->prontoPagos->isEmpty()) ? 'hm-programado' : '' }}">

                                    @if($saldoPendiente > 0 && $r->estado !== 'NULA')


                                        <input type="checkbox"
                                            class="chk-honorario"
                                            value="{{ $r->id }}"
                                            data-id="{{ $r->id }}"
                                            data-empresa="{{ $r->empresa->Nombre }}"
                                            data-rut="{{ $r->rut_emisor }}"
                                            data-emisor="{{ $r->razon_social_emisor }}"
                                            data-folio="{{ $r->folio }}"
                                            data-fecha-emision="{{ $r->fecha_emision?->format('Y-m-d') }}"
                                            data-fecha-vencimiento="{{ $r->fecha_vencimiento?->format('Y-m-d') }}"
                                            data-monto="{{ $montoPagado }}"
                                            data-saldo="{{ $saldoPendiente }}">



                                    @else
                                        <input type="checkbox" disabled>
                                    @endif

                                </td>


                                {{-- Empresa --}}
                                <td class="hm-nowrap">
                                    <span class="hm-ellipsis-sm" title="{{ $r->empresa->Nombre }}">
                                        {{ $r->empresa->Nombre }}
                                    </span>
                                </td>

                                {{-- Estado --}}
                                <td class="hm-nowrap">
                                    <span class="hm-chip {{ $estaVencido ? 'hm-chip-bad' : 'hm-chip-ok' }}">
                                        {{ $estadoActual }}
                                        @if($estaVencido)
                                            <span class="hm-subtle" style="color:inherit;"></span>
                                        @endif
                                    </span>
                                </td>

                                {{-- RUT --}}
                                <td class="hm-nowrap">{{ $r->rut_emisor }}</td>

                                {{-- Emisor --}}
                                <td>
                                    @if($tieneCobranza)
                                        <span class="hm-ellipsis" title="{{ $r->cobranzaCompra->razon_social }}">
                                            {{ $r->cobranzaCompra->razon_social }}
                                        </span>
                                    @else
                                        <span class="text-muted">Sin proveedor</span>
                                    @endif
                                </td>

                                {{-- Folio --}}
                                <td class="hm-nowrap">
                                    <a href="{{ route('honorarios.mensual.show', $r->id) }}" class="fw-semibold text-decoration-none">
                                        {{ $r->folio }}
                                    </a>
                                </td>

                                {{-- Servicio --}}
                                <td class="hm-nowrap">
                                    @if($tieneCobranza)
                                        {{ $r->cobranzaCompra->servicio }}
                                    @else
                                        <span class="text-muted">Sin Servicio</span>
                                    @endif
                                </td>

                                {{-- Servicio Final --}}
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


                                {{-- Fechas --}}
                                <td class="hm-nowrap">{{ $r->fecha_emision?->format('Y-m-d') }}</td>
                                <td class="hm-nowrap">{{ $r->fecha_vencimiento?->format('Y-m-d') }}</td>

                                {{-- Estado SII --}}
                                <td class="hm-nowrap">
                                    <span class="{{ $r->estado === 'ANULADA' ? 'text-danger' : 'text-success' }}">
                                        {{ $r->estado }}
                                    </span>
                                </td>

                                {{-- Fecha Anulación --}}
                                <td class="hm-nowrap">{{ $r->fecha_anulacion?->format('Y-m-d') }}</td>

                                {{-- Monto Pagado --}}
                                <td class="hm-nowrap text-end fw-semibold">
                                    {{ number_format($montoPagado, 0, ',', '.') }}
                                </td>

                                {{-- Saldo Pendiente --}}
                                <td class="hm-nowrap text-end fw-semibold {{ $saldoPendiente === 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($saldoPendiente, 0, ',', '.') }}
                                </td>

                                {{-- Fecha Cambio Estado --}}
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

                </table>

                {{-- Paginación --}}
                <div class="py-3 d-flex justify-content-center">
                    {{ $registros->links('pagination::bootstrap-4') }}
                </div>
            </div>
        @endif
    </div>

    {{-- =========================
        5) MODAL ÚNICO: DEFINIR SERVICIO
        (reemplaza 1 modal por fila, sin perder funcionalidad)
    ========================== --}}
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

    {{-- =========================
        6) NAVEGACIÓN FINAL + MODAL PAGO MASIVO
    ========================== --}}
    <div class="d-flex justify-content-center mt-4">
        <a href="{{ route('boleta.mensual.panel') }}" class="btn btn-outline-primary px-4 py-2 rounded-pill">
            ← Ir a Panel Boletas honorarios
        </a>
    </div>

</div>

@include('boleta_mensual._modal_pago_masivo')
@include('boleta_mensual._modal_proximo_pago')


<script>
document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('form-exportar');
    if (!form) return;

    const btn = document.getElementById('btn-exportar');
    const texto = document.getElementById('texto-exportar');
    const spinner = document.getElementById('spinner-exportar');

    form.addEventListener('submit', function () {

        // Deshabilitar botón
        btn.disabled = true;
        texto.textContent = 'Generando archivo...';
        spinner.classList.remove('d-none');

        // Restaurar botón después de 4 segundos
        setTimeout(function () {
            btn.disabled = false;
            texto.textContent = 'Exportar';
            spinner.classList.add('d-none');
        }, 4000);

    });

});
</script>



{{-- Script mínimo para el modal reutilizable --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modalServicio');
    if (!modal) return;

    const form = document.getElementById('formServicioUpdate');
    const title = document.getElementById('modalServicioTitle');
    const input = document.getElementById('inputServicioManual');

    modal.addEventListener('show.bs.modal', (event) => {
        const btn = event.relatedTarget;
        if (!btn) return;

        const updateUrl = btn.getAttribute('data-update-url') || '';
        const folio = btn.getAttribute('data-folio') || '';
        const servicio = btn.getAttribute('data-servicio') || '';

        form.setAttribute('action', updateUrl);
        title.textContent = folio ? `Definir servicio – Folio ${folio}` : 'Definir servicio';
        input.value = servicio;
        setTimeout(() => input.focus(), 150);
    });
});
</script>


<script>
document.addEventListener('DOMContentLoaded', () => {

    const tipoSelect = document.querySelector('select[name="servicio_tipo"]');
    const selectProveedor = document.getElementById('servicioProveedorSelect');
    const inputManual = document.getElementById('servicioManualInput');

    function toggleServicioInput() {
        const tipo = tipoSelect.value;

        // Reset visual
        selectProveedor.classList.add('d-none');
        inputManual.classList.add('d-none');

        //Reset funcional
        selectProveedor.disabled = true;
        inputManual.disabled = true;

        if (tipo === 'proveedor') {
            selectProveedor.classList.remove('d-none');
            selectProveedor.disabled = false;
        }

        if (tipo === 'manual') {
            inputManual.classList.remove('d-none');
            inputManual.disabled = false;
        }
    }

    // Inicial (cuando vuelve con filtros activos)
    toggleServicioInput();

    tipoSelect.addEventListener('change', toggleServicioInput);
});
</script>


<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.dropdown.keep-open-on-drag').forEach(function (dd) {
    let startedInside = false;

    dd.addEventListener('mousedown', function (e) {
      if (e.target.closest('.dropdown-menu')) startedInside = true;
    });

    const menu = dd.querySelector('.dropdown-menu');
    if (menu) {
      menu.addEventListener('click', function (e) {
        e.stopPropagation();
      });
    }

    document.addEventListener('click', function (e) {
      if (!startedInside) return;
      startedInside = false;

      if (!e.target.closest('.dropdown.keep-open-on-drag')) {
        e.preventDefault();
        e.stopImmediatePropagation();
      }
    }, true);
  });
});
</script>

@vite('resources/js/index.js')



@endsection
