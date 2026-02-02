@extends('layouts.app')

@section('content')
<div class="container">

    {{-- =========================
        1) TÍTULO
    ========================== --}}
    <h1 class="mb-4">Honorarios Mensuales</h1>


    {{-- =========================
        2) FILTROS
    ========================== --}}
    {{-- =========================
        FILTROS – BUSCAR HONORARIOS
    ========================== --}}
    <div class="card mb-4">
        <div class="card-header">
            <strong>Buscar honorarios</strong>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('honorarios.mensual.index') }}">

                <div class="row g-3 align-items-end">

                    {{-- Empresa --}}
                    <div class="col-md-2">
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
                    <div class="col-md-1">
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
                    <div class="col-md-1">
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
                    <div class="col-md-2">
                        <label class="form-label">Razón social emisor</label>
                        <input type="text"
                            name="razon_social_emisor"
                            class="form-control"
                            value="{{ request('razon_social_emisor') }}">
                    </div>

                    {{-- RUT --}}
                    <div class="col-md-2">
                        <label class="form-label">RUT emisor</label>
                        <input type="text"
                            name="rut_emisor"
                            class="form-control"
                            value="{{ request('rut_emisor') }}">
                    </div>

                    {{-- Folio --}}
                    <div class="col-md-2">
                        <label class="form-label">Folio</label>
                        <input type="text"
                            name="folio"
                            class="form-control"
                            value="{{ request('folio') }}">
                    </div>

                    {{-- Fecha Documento --}}
                    <div class="col-md-2">
                        <label class="form-label">Fecha documento</label>
                        <input type="date"
                            name="fecha_emision_desde"
                            class="form-control"
                            value="{{ request('fecha_emision_desde') }}">
                    </div>

                    {{-- Fecha Vencimiento --}}
                    <div class="col-md-2">
                        <label class="form-label">Fecha vencimiento</label>
                        <input type="date"
                            name="fecha_vencimiento_desde"
                            class="form-control"
                            value="{{ request('fecha_vencimiento_desde') }}">
                    </div>

                    {{-- Saldo --}}
                    <div class="col-md-2">
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
                    <div class="col-md-2">
                        <label class="form-label">Monto</label>
                        <input type="number"
                            name="saldo_monto"
                            class="form-control"
                            value="{{ request('saldo_monto') }}">
                    </div>

                    {{-- Botones --}}
                    <div class="col-md-2 d-flex gap-2">
                        <button class="btn btn-primary w-100">
                            Buscar
                        </button>

                        <a href="{{ route('honorarios.mensual.index') }}"
                        class="btn btn-outline-secondary w-100">
                            Limpiar
                        </a>

                        
                    </div>


                    <div class="col-md-2 d-flex gap-2">

                        <a href="{{ route('cobranzas-compras.index') }}"
                           class="btn btn-outline-secondary w-100">
                            Detalle Proveedor
                        </a>

                        <a href="{{ route('honorarios.mensual.historial') }}"
                           class="btn btn-outline-secondary w-100">
                            Movimientos
                        </a>

                        <button type="button"
                                class="btn btn-outline-secondary w-100"
                                data-bs-toggle="modal"
                                data-bs-target="#modalPagoMasivo">
                            Pagos Masivos
                        </button>


                        
                    </div>

                </div>
            </form>
        </div>
    </div>




    {{-- =========================
        3) MENSAJES + PREVIEW
    ========================== --}}

    {{-- Mensaje info --}}
    @if(session('info'))
        <div class="alert alert-info">
            {{ session('info') }}
        </div>
    @endif


    {{-- Preview (sin guardar) --}}
    @if(session('preview'))
        @php $preview = session('preview'); @endphp

        <div class="card mb-5 border-warning">
            <div class="card-header bg-warning">
                <strong>Previsualización del archivo (sin guardar)</strong>
            </div>

            <div class="card-body">

                <p><strong>Contribuyente:</strong> {{ $preview['meta']['razon_social'] }}</p>
                <p><strong>RUT:</strong> {{ $preview['meta']['rut_contribuyente'] }}</p>

                <p>
                    <strong>Periodo:</strong>
                    {{ \Carbon\Carbon::create()->month($preview['meta']['mes'])->translatedFormat('F') }}
                    {{ $preview['meta']['anio'] }}
                </p>

                <table class="table table-sm table-bordered mt-3">
                    <thead class="table-secondary">
                        <tr>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Fecha Anulación</th>
                            <th>Rut Emisor</th>
                            <th>Razón Social</th>
                            <th>Soc. Prof.</th>
                            <th>Bruto</th>
                            <th>Retenido</th>
                            <th>Pagado</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($preview['registros'] as $r)
                            <tr>
                                <td>{{ $r['folio'] }}</td>
                                <td>{{ $r['fecha_emision'] }}</td>
                                <td>{{ $r['estado'] }}</td>
                                <td>{{ $r['fecha_anulacion'] }}</td>
                                <td>{{ $r['rut_emisor'] }}</td>
                                <td>{{ $r['razon_social_emisor'] }}</td>
                                <td>{{ $r['sociedad_profesional'] ? 'SI' : 'NO' }}</td>
                                <td class="text-end">{{ number_format($r['monto_bruto'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($r['monto_retenido'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($r['monto_pagado'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach

                        <tr class="table-light fw-bold">
                            <td colspan="7">Totales</td>
                            <td class="text-end">{{ number_format($preview['totales']['bruto'], 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($preview['totales']['retenido'], 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($preview['totales']['pagado'], 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>

                <form action="{{ route('honorarios.mensual.store') }}" method="POST" class="mt-4">
                    @csrf
                    <input type="hidden" name="data" value="{{ base64_encode(json_encode($preview)) }}">
                    <button class="btn btn-success">Confirmar y guardar</button>
                </form>

            </div>
        </div>
    @endif



    {{-- =========================
        4) REPORTE (TABLA PRINCIPAL)
    ========================== --}}
    <div class="card mt-4">

        <div class="card-header">
            <strong>Reporte Honorarios Mensuales</strong>
        </div>

        <div class="card-body p-0">

            @if($registros->isEmpty())
                <div class="p-3">
                    <p class="text-muted mb-0">No hay honorarios registrados.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 align-middle">

                        <thead class="table-light">
                            <tr>
                                <th>Empresa</th>
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
                                <th>Monto Pagado</th>
                                <th>Saldo pendiente</th>
                                <th>Fecha Cambio Estado</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($registros as $r)

                                @php
                                    // Estado financiero mostrado
                                    $estadoActual = $r->estado_financiero ?? $r->estado_financiero_inicial;

                                    // Vencido por fecha
                                    $estaVencido = $r->fecha_vencimiento && $r->fecha_vencimiento->isPast();
                                    $claseEstado = $estaVencido ? 'badge bg-danger' : 'badge bg-success';

                                    // Cobranza / servicio
                                    $tieneCobranza = (bool) $r->cobranzaCompra;
                                    $servicioCobranza = $tieneCobranza ? $r->cobranzaCompra->servicio : null;
                                    $esServicioOtro = $tieneCobranza && $servicioCobranza === 'Otro';

                                    // Montos
                                    $montoPagado = (int) ($r->monto_pagado ?? 0);
                                    $saldoPendiente = (int) ($r->saldo_pendiente ?? 0);
                                @endphp

                                <tr>
                                    {{-- Empresa --}}
                                    <td>{{ $r->empresa->Nombre }}</td>

                                    {{-- Estado financiero (badge) --}}
                                    <td>
                                        <span class="{{ $claseEstado }}">
                                            {{ $estadoActual }}
                                        </span>
                                    </td>

                                    {{-- RUT --}}
                                    <td>{{ $r->rut_emisor }}</td>

                                    {{-- Emisor (desde cobranza) --}}
                                    <td>
                                        @if($tieneCobranza)
                                            {{ $r->cobranzaCompra->razon_social }}
                                        @else
                                            <span class="text-muted">Sin proveedor</span>
                                        @endif
                                    </td>

                                    {{-- Folio --}}
                                    <td>
                                        <a href="{{ route('honorarios.mensual.show', $r->id) }}"
                                           class="fw-bold text-decoration-none">
                                            {{ $r->folio }}
                                        </a>
                                    </td>

                                    {{-- Servicio (desde cobranza) --}}
                                    <td>
                                        @if($tieneCobranza)
                                            {{ $r->cobranzaCompra->servicio }}
                                        @else
                                            <span class="text-muted">Sin Servicio</span>
                                        @endif
                                    </td>

                                    {{-- Servicio Final (incluye "Otro" + botón) --}}
                                    <td>
                                        @if($esServicioOtro)
                                            @if($r->servicio_manual)
                                                <span class="badge bg-info">
                                                    {{ $r->servicio_manual }}
                                                </span>
                                            @else
                                                <span class="text-muted">Otro</span>
                                            @endif

                                            <div class="mt-1">
                                                <button class="btn btn-sm btn-outline-primary"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalServicio{{ $r->id }}">
                                                    Definir servicio
                                                </button>
                                            </div>
                                        @else
                                            {{ $r->servicio_final ?? '—' }}
                                        @endif
                                    </td>

                                    {{-- Fechas --}}
                                    <td>{{ $r->fecha_emision?->format('Y-m-d') }}</td>
                                    <td>{{ $r->fecha_vencimiento?->format('Y-m-d') }}</td>

                                    {{-- Estado SII --}}
                                    <td>
                                        <span class="{{ $r->estado === 'ANULADA' ? 'text-danger' : 'text-success' }}">
                                            {{ $r->estado }}
                                        </span>
                                    </td>

                                    {{-- Fecha Anulación --}}
                                    <td>{{ $r->fecha_anulacion }}</td>

                                    {{-- Monto Pagado --}}
                                    <td class="text-end fw-bold">
                                        {{ number_format($montoPagado, 0, ',', '.') }}
                                    </td>

                                    {{-- Saldo Pendiente --}}
                                    <td class="text-end fw-bold {{ $saldoPendiente === 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($saldoPendiente, 0, ',', '.') }}
                                    </td>

                                    {{-- Fecha Cambio Estado --}}
                                    <td>{{ $r->fecha_estado_financiero }}</td>
                                </tr>

                            @endforeach
                        </tbody>

                    </table>

                    {{-- Paginación --}}
                    <div class="mt-3 d-flex justify-content-center">
                        {{ $registros->links('pagination::bootstrap-4') }}
                    </div>

                </div>
            @endif

        </div>
    </div>



    {{-- =========================
        5) MODALES (FUERA DE LA TABLA)
        (mismo modal, solo reubicado)
    ========================== --}}
    @foreach($registros as $r)
        @php
            $tieneCobranza = (bool) $r->cobranzaCompra;
            $servicioCobranza = $tieneCobranza ? $r->cobranzaCompra->servicio : null;
            $esServicioOtro = $tieneCobranza && $servicioCobranza === 'Otro';
        @endphp

        @if($esServicioOtro)
            <div class="modal fade" id="modalServicio{{ $r->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <form method="POST" action="{{ route('honorarios.mensual.servicio.update', $r->id) }}">
                        @csrf
                        @method('PATCH')

                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    Definir servicio – Folio {{ $r->folio }}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Servicio</label>
                                    <input type="text"
                                           name="servicio_manual"
                                           class="form-control"
                                           required
                                           value="{{ $r->servicio_manual }}">
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button class="btn btn-secondary" data-bs-dismiss="modal">
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
        @endif
    @endforeach



    {{-- =========================
        6) NAVEGACIÓN FINAL + MODAL PAGO MASIVO
    ========================== --}}
    <div class="text-center mt-4">
        <a href="{{ route('boleta.mensual.panel') }}" class="btn btn-outline-primary px-4 py-2 rounded-pill">
            <- Ir a Panel Boletas honorarios
        </a>
    </div>

</div>

@include('boleta_mensual._modal_pago_masivo')

@endsection
