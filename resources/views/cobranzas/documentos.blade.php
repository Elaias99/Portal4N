@extends('layouts.app')

@vite('resources/css/cuentas-cobrar.css')

@section('content')

{{-- MENSAJES UNIFICADOS Y OPTIMIZADOS --}}
<div class="container" style="max-width: 1150px;">

    {{-- ERROR --}}
    @if(session('error'))
        <div class="alert alert-danger custom-alert mx-auto shadow-sm" style="max-width: 100%; border-left:5px solid #dc3545; border-radius:10px; padding:12px 16px;">
            <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between">
                <div class="d-flex align-items-center mb-2 mb-md-0">
                    <i class="bi bi-x-circle-fill text-danger me-2"></i>
                    <div><strong>Error:</strong> {{ session('error') }}</div>
                </div>

                @if(session('detalles_errores'))
                    <button class="btn btn-link btn-sm p-0 text-decoration-none text-danger"
                            type="button"
                            data-toggle="collapse"
                            data-target="#detallesErrores"
                            aria-expanded="false"
                            aria-controls="detallesErrores">
                        <i class="bi bi-caret-down-fill"></i> Ver detalles
                    </button>
                @endif
            </div>

            @if(session('detalles_errores'))
                <div id="detallesErrores" class="collapse mt-2">
                    <div class="error-list border-top pt-2"
                        style="max-height:180px; overflow-y:auto; background:#fff8f8; border-radius:8px; padding:8px 10px;">
                        <ul class="small mb-0 ps-3" style="list-style-type:'⚠️ '; line-height:1.4;">
                            @foreach (session('detalles_errores') as $error)
                                <li class="mb-1">{!! $error !!}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    @endif


    {{-- WARNING --}}
    @if(session('warning'))
        <div class="alert alert-warning custom-alert mx-auto shadow-sm" style="max-width: 100%; border-left:5px solid #ffc107; border-radius:10px; padding:12px 16px;">
            <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between">
                <div class="d-flex align-items-center mb-2 mb-md-0">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                    <div><strong>Atención:</strong> {{ session('warning') }}</div>
                </div>

                @if(session('detalles_errores'))
                    <button class="btn btn-link btn-sm p-0 text-decoration-none text-warning"
                            type="button"
                            data-toggle="collapse"
                            data-target="#detallesErrores"
                            aria-expanded="false"
                            aria-controls="detallesErrores">
                        <i class="bi bi-caret-down-fill"></i> Ver detalles
                    </button>
                @endif
            </div>

            @if(session('detalles_errores'))
                <div id="detallesErrores" class="collapse mt-2">
                    <div class="error-list border-top pt-2"
                        style="max-height:180px; overflow-y:auto; background:#fffef5; border-radius:8px; padding:8px 10px;">
                        <ul class="small mb-0 ps-3" style="list-style-type:'⚠️ '; line-height:1.4;">
                            @foreach (session('detalles_errores') as $error)
                                <li class="mb-1">{!! $error !!}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    @endif


    {{-- SUCCESS --}}
    @if(session('success'))
        <div class="alert alert-success custom-alert mx-auto shadow-sm" style="max-width: 100%; border-left:5px solid #28a745; border-radius:10px; padding:12px 16px;">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                <div><strong>Éxito:</strong> {{ session('success') }}</div>
            </div>
        </div>
    @endif

</div>





    <div class="container-fluid cc" style="max-width: 100%;">

        <x-finanzas.header
            :back-route="route('cobranzas.general')"
            title="Reporte Cuentas por Cobrar"
        />

        <x-finanzas.top-section>
            <x-slot:filters>
                <x-finanzas.filters-card>
                    <form method="GET" action="{{ route('cobranzas.documentos') }}">
                        <input type="hidden" name="page" value="{{ request('page', 1) }}">

                        <div class="row g-3 align-items-end">
                            
                            <div class="col-md-2">
                                <label class="form-label small text-muted">Razón Social</label>
                                <input type="text" name="razon_social" class="form-control form-control-sm"
                                    placeholder="" value="{{ request('razon_social') }}">
                            </div>

                            <div class="col-md-1">
                                <label class="form-label small text-muted">RUT Cliente</label>
                                <input type="text" name="rut_cliente" class="form-control form-control-sm"
                                    placeholder="" value="{{ request('rut_cliente') }}">
                            </div>

                            <div class="col-md-1">
                                <label class="form-label small text-muted">Folio</label>
                                <input type="text" name="folio" class="form-control form-control-sm"
                                    placeholder="N°" value="{{ request('folio') }}">
                            </div>

                            <div class="col-md-1 dropdown-saldo">
                                <label class="form-label small text-muted">Saldo</label>

                                <div class="dropdown w-100">
                                    <button
                                        class="form-control form-control-sm dropdown-toggle text-start"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false"
                                    >
                                        Buscar saldo por
                                    </button>

                                    <div class="dropdown-menu p-3" style="min-width: 220px;">
                                        <label class="form-label small text-muted mb-1">Tipo</label>
                                        <select
                                            name="saldo_tipo"
                                            class="form-select form-select-sm mb-2"
                                        >
                                            <option value="saldo_pendiente"
                                                {{ request('saldo_tipo', 'saldo_pendiente') === 'saldo_pendiente' ? 'selected' : '' }}>
                                                Saldo pendiente
                                            </option>

                                            <option value="monto_total"
                                                {{ request('saldo_tipo') === 'monto_total' ? 'selected' : '' }}>
                                                Monto original
                                            </option>
                                        </select>

                                        <label class="form-label small text-muted mb-1">Monto</label>
                                        <input
                                            type="text"
                                            name="saldo_valor"
                                            class="form-control form-control-sm"
                                            placeholder="Ej: $260000"
                                            value="{{ request('saldo_valor') }}"
                                            min="0"
                                        >
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-1">
                                <label class="form-label small text-muted">Estado Original</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    <option value="Al día" {{ request('status') == 'Al día' ? 'selected' : '' }}>
                                        Al día ({{ $totalAlDia ?? 0 }})
                                    </option>
                                    <option value="Vencido" {{ request('status') == 'Vencido' ? 'selected' : '' }}>
                                        Vencido ({{ $totalVencido ?? 0 }})
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-2">
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

                            <div class="col-md-1 dropdown-fechas">
                                <label class="form-label small text-muted">Fecha Origen</label>
                                <div class="dropdown w-100">
                                    <button class="btn dropdown-toggle btn-sm w-100 text-start" type="button"
                                            id="dropdownFechas" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-calendar3"></i> Fecha Dcto.
                                    </button>

                                    <div class="dropdown-menu p-3">
                                        <label class="form-label small text-muted">Desde</label>
                                        <input type="date" name="fecha_inicio" class="form-control form-control-sm mb-2"
                                            value="{{ request('fecha_inicio') }}">

                                        <label class="form-label small text-muted">Hasta</label>
                                        <input type="date" name="fecha_fin" class="form-control form-control-sm mb-2"
                                            value="{{ request('fecha_fin') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-1 dropdown-fechas">
                                <label class="form-label small text-muted">Fecha Vencimiento</label>
                                <div class="dropdown w-100">
                                    <button class="btn dropdown-toggle btn-sm w-100 text-start" type="button"
                                            id="dropdownVencimiento" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-calendar-event"></i>Fecha Venc.
                                    </button>

                                    <div class="dropdown-menu p-3">
                                        <label class="form-label small text-muted">Desde</label>
                                        <input type="date" name="vencimiento_inicio" class="form-control form-control-sm mb-2"
                                            value="{{ request('vencimiento_inicio') }}">

                                        <label class="form-label small text-muted">Hasta</label>
                                        <input type="date" name="vencimiento_fin" class="form-control form-control-sm mb-2"
                                            value="{{ request('vencimiento_fin') }}">
                                    </div>
                                </div>
                            </div>



                            <div class="col-md-2">
                                <label class="form-label small text-muted">Fecha de Corte</label>
                                <input
                                    type="date"
                                    name="fecha_corte"
                                    class="form-control form-control-sm"
                                    value="{{ request('fecha_corte') }}"
                                >
                            </div>












                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <a href="{{ route('cobranzas.documentos') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-x-circle"></i> Limpiar
                            </a>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <strong>Saldo pendiente total:</strong>
                                <span class="text-success fw-semibold">
                                    ${{ number_format($totalSaldoPendiente, 0, ',', '.') }}
                                </span>
                            </div>

                            <a href="{{ route('cobranzas.index') }}" class="btn btn-outline-secondary btn-sm">
                                Detalle Cliente
                            </a>
                        </div>
                    </form>
                </x-finanzas.filters-card>
            </x-slot:filters>

            <x-slot:actions>
                <x-finanzas.mass-actions-card title="Gestión Masiva">
                    @if (Auth::id() != 375)
                        <a href="{{ route('panelfinanza.show') }}"
                        class="btn btn-outline-secondary btn-sm w-100 mb-3 d-flex align-items-center justify-content-center gap-2">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <span>Historial de Movimientos</span>
                        </a>
                    @endif

                    @if (Auth::id() != 375)
                        <form action="{{ route('cobranzas.import') }}" method="POST" enctype="multipart/form-data" class="mb-3">
                            @csrf
                            <input type="file" name="file" class="form-control form-control-sm mb-2" required>
                            <button type="submit" class="btn btn-success btn-sm w-100">
                                <i class="bi bi-file-earmark-arrow-up"></i> Importar Excel
                            </button>
                        </form>
                    @endif

                    <button type="button"
                            class="btn btn-outline-success btn-sm w-100 mb-3 d-flex align-items-center justify-content-center gap-2"
                            data-bs-toggle="modal" data-bs-target="#modalExportarVenta">
                        <i class="bi bi-file-earmark-arrow-down"></i>
                        <span>Exportar Excel</span>
                    </button>

                    @if(request()->filled('fecha_corte'))
                        <form method="GET"
                            action="{{ route('cobranzas.documentos.exportar_al_corte') }}"
                            class="mb-3">
                            
                            <input type="hidden" name="fecha_corte" value="{{ request('fecha_corte') }}">
                            <input type="hidden" name="razon_social" value="{{ request('razon_social') }}">
                            <input type="hidden" name="rut_cliente" value="{{ request('rut_cliente') }}">
                            <input type="hidden" name="folio" value="{{ request('folio') }}">
                            <input type="hidden" name="fecha_inicio" value="{{ request('fecha_inicio') }}">
                            <input type="hidden" name="fecha_fin" value="{{ request('fecha_fin') }}">
                            <input type="hidden" name="vencimiento_inicio" value="{{ request('vencimiento_inicio') }}">
                            <input type="hidden" name="vencimiento_fin" value="{{ request('vencimiento_fin') }}">
                            <input type="hidden" name="saldo_tipo" value="{{ request('saldo_tipo') }}">
                            <input type="hidden" name="saldo_valor" value="{{ request('saldo_valor') }}">
                            <input type="hidden" name="status" value="{{ request('status') }}">
                            <input type="hidden" name="estado_pago" value="{{ request('estado_pago') }}">

                            <button type="submit"
                                    class="btn btn-warning btn-sm w-100 d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-file-earmark-spreadsheet"></i>
                                <span>Exportar Excel al corte</span>
                            </button>
                        </form>
                    @endif

                    




                </x-finanzas.mass-actions-card>
            </x-slot:actions>
        </x-finanzas.top-section>


        





        {{-- === TABLA DE REGISTROS === --}}
        <x-finanzas.plain-table>

            @include('cobranzas.partials.filtros')

            <tbody>
            @foreach ($documentosOriginal as $doc)
                <tr>

                    {{-- Empresa --}}
                    <td class="text-nowrap">
                        {{ $doc->empresa?->Nombre ?? 'Sin empresa' }}
                    </td>

                    {{-- Status (status_original / status manual) --}}
                    <td>
                        @php
                            $color = $doc->status_original === 'Vencido' ? 'bg-danger' : 'bg-success';
                        @endphp

                        @if($doc->tipo_documento_id == 61)

                        @else
                            <span class="badge {{ $color }}">{{ $doc->estado_visible }}</span>

                            @include('cobranzas.modal_status', ['doc' => $doc])
                        @endif
                    </td>

                    <td title="{{ $doc->tipoDocumento?->nombre }}">
                        {{ \Illuminate\Support\Str::limit($doc->tipoDocumento?->nombre ?? '-', 18) }}
                    </td>

                    {{-- RUT Proveedor --}}
                    <td class="text-nowrap">
                        {{ $doc->rut_cliente }}
                    </td>

                    {{-- Razón Social --}}
                    <td class="text-nowrap">
                        {{ $doc->razon_social }}
                    </td>

                    {{-- Folio --}}
                    <td>
                        <a href="{{ route('documentos.detalles', $doc->id) }}?{{ http_build_query(request()->query()) }}"
                            class="fw-semibold text-decoration-none">
                                {{ $doc->folio }}
                        </a>

                        @if($doc->referenciados->count() > 0)
                            <small class="badge bg-info text-dark ms-1">
                                Referenciado por NC Nº{{ $doc->referenciados->pluck('folio')->join(', ') }}
                            </small>
                        @elseif($doc->referencia)
                            <small class="badge bg-warning text-dark ms-1">
                                Referencia a Factura Nº{{ $doc->referencia->folio }}
                            </small>
                        @endif
                    </td>

                    {{-- Fecha Docto --}}
                    <td>
                        {{ $doc->fecha_docto ? \Carbon\Carbon::parse($doc->fecha_docto)->format('d-m-Y') : '-' }}
                    </td>

                    {{-- Fecha Vencimiento --}}
                    <td>
                        {{ $doc->fecha_vencimiento ? \Carbon\Carbon::parse($doc->fecha_vencimiento)->format('d-m-Y') : '-' }}
                    </td>

                    {{-- Monto Neto --}}
                    <td class="text-end">
                        ${{ number_format($doc->monto_neto, 0, ',', '.') }}
                    </td>

                    {{--IVA Rec. --}}
                    <td class="text-end">
                        ${{ number_format($doc->monto_iva, 0, ',', '.') }}
                    </td>

                    {{-- Total --}}
                    <td class="text-end fw-bold">
                        ${{ number_format($doc->monto_total, 0, ',', '.') }}
                    </td>

                    {{--Saldo Pendiente --}}
                    <td class="text-end fw-bold {{ $doc->saldo_pendiente == 0 ? 'text-success' : 'text-danger' }}">
                        ${{ number_format($doc->saldo_pendiente, 0, ',', '.') }}
                    </td>

                    {{-- Fecha Estado Manual --}}
                    <td>
                        {{ $doc->fecha_ultima_transaccion
                            ? \Carbon\Carbon::parse($doc->fecha_ultima_transaccion)->format('d-m-Y')
                            : '-' }}
                    </td>

                </tr>
            @endforeach
            </tbody>

        </x-finanzas.plain-table>

        {{-- Paginación --}}
        <div class="mt-3 d-flex justify-content-center">
            {{ $documentosOriginal->appends(request()->query())->links('pagination::bootstrap-4') }}

        </div>
    </div>





@include('cobranzas._modal_create_cobranza')
@include('cobranzas.modal_pagos_masivos')
@include('cobranzas.partials.modal_ExportarVenta')

@vite('resources/js/cobranzas_documentos.js')

@endsection



