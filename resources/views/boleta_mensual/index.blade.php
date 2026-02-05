{{-- resources/views/boleta_mensual/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid py-3 hm">

    {{-- =========================
        0) ESTILOS LOCALES (solo esta vista)
    ========================== --}}
    <style>
            /* =========================
            BASE DEL MÓDULO
            ========================= */
            .hm {
                background: #f4f6f8;
            }

            /* Layout */
            .hm .hm-header {
                display: flex;
                gap: 12px;
                align-items: flex-end;
                justify-content: space-between;
                flex-wrap: wrap;
            }

            .hm .hm-title {
                margin: 0;
                line-height: 1.1;
            }

            .hm .hm-subtle {
                color: #6c757d;
                font-size: .9rem;
            }

            /* =========================
            FILTROS
            ========================= */
            .hm details.hm-filters {
                border: 1px solid rgba(0,0,0,.08);
                border-radius: 12px;
                padding: 12px 12px 6px;
                background: #f7f8fa; /* ❌ no blanco puro */
            }

            .hm details.hm-filters > summary {
                cursor: pointer;
                list-style: none;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
            }

            .hm details.hm-filters > summary::-webkit-details-marker {
                display: none;
            }

            .hm .hm-summary-left {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .hm .hm-summary-badge {
                font-size: .75rem;
                padding: .25rem .55rem;
                border-radius: 999px;
                background: rgba(13,110,253,.08);
                color: #0d6efd;
            }

            .hm .hm-filters-body {
                padding-top: 12px;
            }

            .hm .hm-actions {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
                justify-content: flex-end;
            }

            .hm .hm-actions .btn {
                white-space: nowrap;
            }

            /* =========================
            TABLA PLANA
            ========================= */
            .hm .hm-table-wrap {
                border: 1px solid rgba(0,0,0,.08);
                border-radius: 12px;
                overflow: hidden;
                background: #f7f8fa;
            }

            .hm .hm-table-toolbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 10px 12px;
                border-bottom: 1px solid rgba(0,0,0,.08);
                background: #f7f8fa;
            }

            .hm .hm-table-title {
                font-weight: 600;
                margin: 0;
            }

            .hm .table {
                margin: 0;
                background: transparent;
            }

            .hm .table thead th {
                position: sticky;
                top: 0;
                z-index: 2;
                background: #eef0f3; /* más suave */
                border-bottom: 1px solid rgba(0,0,0,.12);
                white-space: nowrap;
                font-size: .82rem;
            }

            .hm .table td {
                vertical-align: middle;
                font-size: .88rem;
            }

            /* Helpers */
            .hm .hm-nowrap { white-space: nowrap; }

            .hm .hm-ellipsis {
                max-width: 260px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                display: block;
            }

            .hm .hm-ellipsis-sm {
                max-width: 160px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                display: block;
            }

            /* =========================
            ESTADOS (DESATURADOS)
            ========================= */
            .hm .hm-chip {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: .25rem .55rem;
                border-radius: 999px;
                font-size: .75rem;
            }

            .hm .hm-chip-ok {
                background: rgba(25,135,84,.08);
                color: #146c43;
            }

            .hm .hm-chip-bad {
                background: rgba(220,53,69,.08);
                color: #b02a37;
            }

            .hm .hm-chip-info {
                background: rgba(13,202,240,.10);
                color: #087990;
            }

            /* =========================
            BLOQUES PREVIEW
            ========================= */
            .hm .hm-block {
                border: 1px solid rgba(0,0,0,.08);
                border-radius: 12px;
                background: #f7f8fa;
                padding: 12px;
            }

            .hm .hm-block-warning {
                border-color: rgba(255,193,7,.35);
                background: rgba(255,193,7,.06);
            }

            .hm .hm-block-title {
                font-weight: 600;
                margin: 0 0 10px;
            }

            .hm-link-editable {
                text-decoration: none;
                color: inherit;
            }

            .hm-link-editable:hover {
                text-decoration: none;
            }


    </style>

    {{-- =========================
        1) ENCABEZADO (sin cards)
    ========================== --}}
    <div class="hm-header mb-3">
        <div>
            <h1 class="hm-title">Honorario Mensual</h1>
            <div class="hm-subtle">Búsqueda, revisión y acciones sobre honorarios mensuales.</div>
        </div>

        {{-- Acciones de navegación (no mezcladas con filtros) --}}
        <div class="hm-actions">
            <a href="{{ route('cobranzas-compras.index') }}" class="btn btn-outline-secondary">
                Detalle Proveedor
            </a>

            <a href="{{ route('honorarios.mensual.historial') }}" class="btn btn-outline-secondary">
                Movimientos
            </a>

            <button type="button"
                    class="btn btn-outline-secondary"
                    data-bs-toggle="modal"
                    data-bs-target="#modalPagoMasivo">
                Pagos Masivos
            </button>
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
                        <select name="saldo_tipo" class="form-select">
                            <option value="">Buscar saldo por</option>
                            <option value="pendiente" {{ request('saldo_tipo') === 'pendiente' ? 'selected' : '' }}>
                                Saldo pendiente
                            </option>
                            <option value="original" {{ request('saldo_tipo') === 'original' ? 'selected' : '' }}>
                                Monto original
                            </option>
                        </select>
                    </div>

                    {{-- Monto --}}
                    <div class="col-12 col-md-3 col-lg-2">
                        <label class="form-label">Monto</label>
                        <input type="number"
                            name="saldo_monto"
                            class="form-control"
                            value="{{ request('saldo_monto') }}">
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



                        </div>
                    </div>

                </div>
            </form>

            {{-- =====================
                IMPORTADOR (POST)
            ====================== --}}
            <hr class="my-4">

            <form action="{{ route('honorarios.mensual.import') }}"
                method="POST"
                enctype="multipart/form-data">

                @csrf

                <div class="row g-3 align-items-end">

                    <div class="col-12 col-md-9">
                        <label class="form-label fw-semibold">
                            Importar archivo SII (file_informeMensualREC)
                        </label>

                        <input type="file"
                            name="archivo"
                            class="form-control"
                            required>
                    </div>

                    <div class="col-12 col-md-3">
                        <button class="btn btn-success w-100">
                            Importar
                        </button>
                    </div>

                </div>
            </form>

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
        @php $preview = session('preview'); @endphp

        <div class="hm-block hm-block-warning mb-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="hm-block-title mb-0">Previsualización del archivo (sin guardar)</div>

                <form action="{{ route('honorarios.mensual.store') }}" method="POST" class="m-0">
                    @csrf
                    <input type="hidden" name="data" value="{{ base64_encode(json_encode($preview)) }}">
                    <button class="btn btn-success">
                        Confirmar y guardar
                    </button>
                </form>
            </div>

            <div class="mt-2">
                <div class="row g-2">
                    <div class="col-12 col-md-6">
                        <div class="hm-subtle">Contribuyente</div>
                        <div class="fw-semibold">{{ $preview['meta']['razon_social'] }}</div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="hm-subtle">RUT</div>
                        <div class="fw-semibold hm-nowrap">{{ $preview['meta']['rut_contribuyente'] }}</div>
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
                            <th class="hm-nowrap">Folio</th>
                            <th class="hm-nowrap">Fecha</th>
                            <th class="hm-nowrap">Estado</th>
                            <th class="hm-nowrap">Fecha Anulación</th>
                            <th class="hm-nowrap">Rut Emisor</th>
                            <th>Razón Social</th>
                            <th class="hm-nowrap">Soc. Prof.</th>
                            <th class="hm-nowrap text-end">Bruto</th>
                            <th class="hm-nowrap text-end">Retenido</th>
                            <th class="hm-nowrap text-end">Pagado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($preview['registros'] as $r)
                            <tr>
                                <td class="hm-nowrap">{{ $r['folio'] }}</td>
                                <td class="hm-nowrap">{{ $r['fecha_emision'] }}</td>
                                <td class="hm-nowrap">{{ $r['estado'] }}</td>
                                <td class="hm-nowrap">{{ $r['fecha_anulacion'] }}</td>
                                <td class="hm-nowrap">{{ $r['rut_emisor'] }}</td>
                                <td><span class="hm-ellipsis" title="{{ $r['razon_social_emisor'] }}">{{ $r['razon_social_emisor'] }}</span></td>
                                <td class="hm-nowrap">{{ $r['sociedad_profesional'] ? 'SI' : 'NO' }}</td>
                                <td class="hm-nowrap text-end">{{ number_format($r['monto_bruto'], 0, ',', '.') }}</td>
                                <td class="hm-nowrap text-end">{{ number_format($r['monto_retenido'], 0, ',', '.') }}</td>
                                <td class="hm-nowrap text-end">{{ number_format($r['monto_pagado'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach

                        <tr class="table-light fw-bold">
                            <td colspan="7">Totales</td>
                            <td class="hm-nowrap text-end">{{ number_format($preview['totales']['bruto'], 0, ',', '.') }}</td>
                            <td class="hm-nowrap text-end">{{ number_format($preview['totales']['retenido'], 0, ',', '.') }}</td>
                            <td class="hm-nowrap text-end">{{ number_format($preview['totales']['pagado'], 0, ',', '.') }}</td>
                        </tr>
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
                            <th class="hm-nowrap">Fecha Cambio Estado</th>
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
                                <td class="hm-nowrap text-center">
                                    @if($saldoPendiente > 0)
                                        <input type="checkbox"
                                            class="chk-honorario"
                                            value="{{ $r->id }}"
                                            data-id="{{ $r->id }}"
                                            data-folio="{{ $r->folio }}"
                                            data-rut="{{ $r->rut_emisor }}"
                                            data-emisor="{{ $r->razon_social_emisor }}"
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
                                <td class="hm-nowrap">{{ $r->fecha_estado_financiero }}</td>
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

        // 🔴 Reset funcional
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


@vite('resources/js/index.js')



@endsection
