@extends('layouts.app')

@vite('resources/css/finanzas_compras.css')

@section('content')

    {{-- Mensajes de estado --}}
    <x-finanzas.flash-messages />
    {{-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// --}}
    {{-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// --}}
    {{-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// --}}
    {{-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// --}}
    <div class="container" style="max-width: 100%;">

        {{-- Volver --}}
        <x-finanzas.header
            :back-route="route('cobranzas.general')"
            title="Reporte Cuentas por Pagar"
        />
        {{-- === FILTROS + GESTIÓN MASIVA === --}}
        <x-finanzas.top-section>
            <x-slot:filters>
                <x-finanzas.filters-card>
                    <form method="GET" action="{{ route('finanzas_compras.index') }}">
                        <div class="row g-3 align-items-end">

                            <div class="col-md-2">
                                <label class="form-label small text-muted">Razón Social</label>
                                <input type="text" name="razon_social" class="form-control form-control-sm"
                                    placeholder="Buscar proveedor" value="{{ request('razon_social') }}">
                            </div>

                            <div class="col-md-1">
                                <label class="form-label small text-muted">RUT Cliente</label>
                                <input type="text" name="rut_proveedor" class="form-control form-control-sm"
                                    placeholder="Ej: 76432100-5" value="{{ request('rut_proveedor') }}">
                            </div>

                            <div class="col-md-1">
                                <label class="form-label small text-muted">Folio</label>
                                <input type="text" name="folio" class="form-control form-control-sm"
                                    placeholder="N° Folio" value="{{ request('folio') }}">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small text-muted">Empresa</label>
                                <select name="empresa_id" class="form-control form-control-sm">
                                    <option value="">Todas</option>

                                    @foreach ($empresas as $empresa)
                                        <option value="{{ $empresa->id }}"
                                            {{ request('empresa_id') == $empresa->id ? 'selected' : '' }}>
                                            {{ $empresa->Nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-1 dropdown-saldo">
                                <label class="form-label small text-muted">Saldo</label>

                                <div class="dropdown w-100 keep-open-on-drag">
                                    <button
                                        class="form-control form-control-sm dropdown-toggle text-start"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        data-bs-auto-close="outside"
                                        aria-expanded="false">
                                        Buscar saldo
                                    </button>

                                    <div class="dropdown-menu p-3" style="min-width: 220px;">
                                        <label class="form-label small text-muted mb-1">Tipo</label>
                                        <select name="saldo_tipo" class="form-select form-select-sm mb-2">
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
                                            placeholder="Ej: 260000"
                                            value="{{ request('saldo_valor') }}"
                                            min="0">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-1">
                                <label class="form-label small text-muted">Estado Original</label>
                                <select name="estado" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    <option value="Al día" {{ request('estado') == 'Al día' ? 'selected' : '' }}>
                                        Al día ({{ $totalAlDia ?? 0 }})
                                    </option>
                                    <option value="Vencido" {{ request('estado') == 'Vencido' ? 'selected' : '' }}>
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

                            <div class="col-md-1 dropdown-fechas">
                                <label class="form-label small text-muted">Fecha Documento</label>
                                <div class="dropdown w-100">
                                    <button
                                        class="form-control form-control-sm dropdown-toggle text-start"
                                        type="button"
                                        id="dropdownFechasDocto"
                                        data-bs-toggle="dropdown"
                                        data-bs-auto-close="outside"
                                        aria-expanded="false">
                                        <i class="bi bi-calendar3"></i> Fecha Dcto.
                                    </button>

                                    <div class="dropdown-menu p-3">
                                        <label class="form-label small text-muted">Desde</label>
                                        <input type="date" name="fecha_docto_inicio" class="form-control form-control-sm mb-2"
                                            value="{{ request('fecha_docto_inicio') }}">

                                        <label class="form-label small text-muted">Hasta</label>
                                        <input type="date" name="fecha_docto_fin" class="form-control form-control-sm"
                                            value="{{ request('fecha_docto_fin') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-1 dropdown-fechas">
                                <label class="form-label small text-muted">Fecha Vencimiento</label>
                                <div class="dropdown w-100">
                                    <button
                                        class="form-control form-control-sm dropdown-toggle text-start"
                                        type="button"
                                        id="dropdownFechasVenc"
                                        data-bs-toggle="dropdown"
                                        data-bs-auto-close="outside"
                                        aria-expanded="false">
                                        <i class="bi bi-calendar-event"></i> Fecha Venc.
                                    </button>

                                    <div class="dropdown-menu p-3">
                                        <label class="form-label small text-muted">Desde</label>
                                        <input type="date" name="fecha_venc_inicio" class="form-control form-control-sm mb-2"
                                            value="{{ request('fecha_venc_inicio') }}">

                                        <label class="form-label small text-muted">Hasta</label>
                                        <input type="date" name="fecha_venc_fin" class="form-control form-control-sm"
                                            value="{{ request('fecha_venc_fin') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-1">
                                <label class="form-label small text-muted">Referencias</label>
                                <select name="filtro_referencia" class="form-select form-select-sm">
                                    <option value="">Todas</option>
                                    <option value="referencia_a_otro" {{ request('filtro_referencia') == 'referencia_a_otro' ? 'selected' : '' }}>
                                        Referencia a otro
                                    </option>
                                    <option value="referenciado_por_otros" {{ request('filtro_referencia') == 'referenciado_por_otros' ? 'selected' : '' }}>
                                        Referenciado
                                    </option>
                                    <option value="ambas" {{ request('filtro_referencia') == 'ambas' ? 'selected' : '' }}>
                                        Ambas
                                    </option>
                                    <option value="con_cualquier_referencia" {{ request('filtro_referencia') == 'con_cualquier_referencia' ? 'selected' : '' }}>
                                        Cualquier referencia
                                    </option>
                                    <option value="sin_referencias" {{ request('filtro_referencia') == 'sin_referencias' ? 'selected' : '' }}>
                                        Sin referencias
                                    </option>
                                </select>
                            </div>

                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <a href="{{ route('finanzas_compras.index') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-x-circle"></i> Limpiar
                            </a>
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="bi bi-search"></i> Filtrar
                            </button>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <strong>Saldo pendiente total:</strong>
                                <span class="text-success fw-semibold">
                                    ${{ number_format($totalSaldoPendiente, 0, ',', '.') }}
                                </span>
                            </div>

                            <a href="{{ route('cobranzas-compras.index', ['origen' => 'compras']) }}"
                            class="btn btn-outline-secondary btn-sm">
                                Detalle Proveedor
                            </a>
                        </div>
                    </form>
                </x-finanzas.filters-card>
            </x-slot:filters>

            <x-slot:actions>
                <x-finanzas.mass-actions-card title="Gestión Masiva">
                    @if (Auth::id() != 375)
                        <a href="{{ route('panelfinanza.show_compras') }}"
                        class="btn btn-outline-secondary btn-sm w-100 mb-3 d-flex align-items-center justify-content-center gap-2">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <span>Historial de Compras</span>
                        </a>

                        <form action="{{ route('finanzas_compras.import') }}" method="POST" enctype="multipart/form-data" class="mb-3">
                            @csrf
                            <input type="file" name="file" class="form-control form-control-sm mb-2" required>
                            <button type="submit" class="btn btn-success btn-sm w-100">
                                <i class="bi bi-file-earmark-arrow-up"></i> Importar Excel
                            </button>
                        </form>
                    @endif

                    <button type="button"
                            class="btn btn-outline-success btn-sm w-100 mb-3 d-flex align-items-center justify-content-center gap-2"
                            data-bs-toggle="modal" data-bs-target="#modalExportarCompra">
                        <i class="bi bi-file-earmark-arrow-down"></i>
                        <span>Exportar Excel</span>
                    </button>

                    <button type="button" class="btn btn-success btn-sm w-100 mb-2" data-bs-toggle="modal" data-bs-target="#modalPagosMasivos">
                        Pagar
                    </button>

                    <button type="button" class="btn btn-outline-primary btn-sm w-100" id="btn-proximo-pago-documentos">
                        Definir próximo pago
                    </button>
                </x-finanzas.mass-actions-card>
            </x-slot:actions>
        </x-finanzas.top-section>





        {{-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// --}}
        {{-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// --}}
        {{-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// --}}
        {{-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// --}}
        {{-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// --}}
        {{-- Tabla de registros limpia y responsiva sin scroll --}}
        <x-finanzas.table-card title="Documentos Importados">

                @if($documentosCompras->count() > 0)
                    <div class="table-responsive-sm">
                        <table class="table table-striped table-hover table-sm align-middle text-center">

                            
                            <thead class="table-light text-uppercase align-middle">
                                <tr class="small">

                                    <th class="text-center" style="width:40px;">
                                        <input type="checkbox" id="check-all-documentos">
                                    </th>


                                    {{-- Empresa --}}
                                    @include('cobranzas.partials.filtros_compras', [
                                        'label' => 'Empresa',
                                        'columna' => 'empresa_id',
                                        'sortBy' => $sortBy ?? null,
                                        'sortOrder' => $sortOrder ?? 'asc',
                                        'placeholder' => 'Buscar empresa...'
                                    ])

                                    {{-- Status (status_original) --}}
                                    @include('cobranzas.partials.filtros_compras', [
                                        'label' => 'Status',
                                        'columna' => 'status_original',
                                        'sortBy' => $sortBy ?? null,
                                        'sortOrder' => $sortOrder ?? 'asc',
                                        'placeholder' => 'Al día / Vencido...'
                                    ])

                                    {{-- Tipo Doc --}}
                                    @include('cobranzas.partials.filtros_compras', [
                                        'label' => 'Tipo Doc',
                                        'columna' => 'tipo_documento_id',
                                        'sortBy' => $sortBy ?? null,
                                        'sortOrder' => $sortOrder ?? 'asc',
                                        'placeholder' => 'Buscar tipo doc...'
                                    ])

                                    {{-- Tipo Compra (sin filtro directo) --}}
                                    {{-- <th>Tipo Compra</th> --}}

                                    {{-- RUT Proveedor --}}
                                    @include('cobranzas.partials.filtros_compras', [
                                        'label' => 'RUT Proveedor',
                                        'columna' => 'rut_proveedor',
                                        'sortBy' => $sortBy ?? null,
                                        'sortOrder' => $sortOrder ?? 'asc',
                                        'placeholder' => 'Ej: 76123456-7'
                                    ])

                                    {{-- Razón Social --}}
                                    @include('cobranzas.partials.filtros_compras', [
                                        'label' => 'Razón Social',
                                        'columna' => 'razon_social',
                                        'sortBy' => $sortBy ?? null,
                                        'sortOrder' => $sortOrder ?? 'asc',
                                        'placeholder' => 'Buscar razón social...'
                                    ])

                                    {{-- Folio --}}
                                    @include('cobranzas.partials.filtros_compras', [
                                        'label' => 'Folio',
                                        'columna' => 'folio',
                                        'sortBy' => $sortBy ?? null,
                                        'sortOrder' => $sortOrder ?? 'asc',
                                        'placeholder' => 'N° folio...'
                                    ])

                                    {{-- Fecha Docto --}}
                                    @include('cobranzas.partials.filtros_compras', [
                                        'label' => 'Fecha Docto',
                                        'columna' => 'fecha_docto',
                                        'sortBy' => $sortBy ?? null,
                                        'sortOrder' => $sortOrder ?? 'asc',
                                        'placeholder' => 'AAAA-MM-DD'
                                    ])

                                    {{-- Fecha Vencimiento --}}
                                    @include('cobranzas.partials.filtros_compras', [
                                        'label' => 'Fecha Vencimiento',
                                        'columna' => 'fecha_vencimiento',
                                        'sortBy' => $sortBy ?? null,
                                        'sortOrder' => $sortOrder ?? 'asc',
                                        'placeholder' => 'AAAA-MM-DD'
                                    ])

                                    {{-- Monto Neto --}}
                                    <th>Monto Neto</th>

                                    {{-- IVA Rec --}}
                                    <th>IVA Rec.</th>

                                    {{-- Total --}}
                                    @include('cobranzas.partials.filtros_compras', [
                                        'label' => 'Total',
                                        'columna' => 'monto_total',
                                        'sortBy' => $sortBy ?? null,
                                        'sortOrder' => $sortOrder ?? 'asc',
                                        'placeholder' => '≥ monto...'
                                    ])

                                    {{-- Saldo Pendiente (sin filtro directo) --}}
                                    <th>Saldo Pendiente</th>

                                    <th>Fecha Último Movimiento</th>

                                </tr>
                            </thead>


                            <tbody>
                                @foreach ($documentosCompras as $doc)
                                    @php
                                        $color = $doc->status_original === 'Vencido' ? 'bg-danger' : 'bg-success';
                                        $estadoMostrar = $doc->estado_visible;

                                        $programacionActiva =
                                            $doc->pagoProgramado &&
                                            (int) $doc->saldo_pendiente > 0 &&
                                            $doc->pagos->isEmpty() &&
                                            $doc->prontoPagos->isEmpty() &&
                                            (int) $doc->tipo_documento_id !== 61;
                                    @endphp



                                    <tr class="small">


                                        <td class="text-center {{ $programacionActiva ? 'doc-programado' : '' }}">
                                            @if($doc->saldo_pendiente > 0 && $doc->tipo_documento_id != 61)
                                                <input type="checkbox"
                                                    class="check-documento"
                                                    value="{{ $doc->id }}"
                                                    data-id="{{ $doc->id }}"
                                                    data-empresa="{{ $doc->empresa?->Nombre ?? '' }}"
                                                    data-folio="{{ $doc->folio }}"
                                                    data-razon="{{ $doc->razon_social }}"
                                                    data-rut="{{ $doc->rut_proveedor }}"
                                                    data-fecha-docto="{{ $doc->fecha_docto ? \Carbon\Carbon::parse($doc->fecha_docto)->format('d-m-Y') : '' }}"
                                                    data-fecha-vencimiento="{{ $doc->fecha_vencimiento ? \Carbon\Carbon::parse($doc->fecha_vencimiento)->format('d-m-Y') : '' }}"
                                                    data-saldo="{{ $doc->saldo_pendiente }}"
                                                    data-total="{{ $doc->monto_total }}"
                                                >
                                            @endif
                                        </td>



                                        <td>{{ $doc->empresa?->Nombre ?? '—' }}</td>
                                        
                                        <td>
                                            @php
                                                $esNotaCredito = ($doc->tipo_documento_id == 61);
                                            @endphp

                                            {{-- Si es una nota de crédito, no mostramos estado ni botón --}}
                                            @if(!$esNotaCredito)
                                                <span class="badge {{ $color }}">{{ $estadoMostrar }}</span><br>

                                                {{-- @if (Auth::id() != 375)
                                                    <button type="button"
                                                            class="btn btn-outline-secondary btn-sm mt-1 px-2 py-0"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#modalEstadoCompra-{{ $doc->id }}">
                                                        Editar
                                                    </button>
                                                @endif --}}

                                                
                                            @else
                                                <span class="badge bg-secondary">Nota de Crédito</span>
                                            @endif
                                        </td>

                                        <td title="{{ $doc->tipoDocumento?->nombre }}">
                                            {{ \Illuminate\Support\Str::limit($doc->tipoDocumento?->nombre ?? '-', 18) }}
                                        </td>

                                        {{-- <td>{{ $doc->tipo_compra ?? '-' }}</td> --}}
                                        <td>{{ $doc->rut_proveedor }}</td>
                                        <td class="text-start">{{ $doc->razon_social }}</td>


                                        <td>
                                            <a href="{{ route('finanzas_compras.show', $doc->id) }}?{{ http_build_query(request()->query()) }}" 
                                                class="fw-semibold text-decoration-none">
                                                {{ $doc->folio }}
                                            </a>

                                            {{-- La factura tiene notas de crédito que la referencian --}}
                                            @if($doc->referenciados->count() > 0)
                                                <span class="badge bg-info text-dark ms-1">
                                                    Referenciado por NC Nº{{ $doc->referenciados->pluck('folio')->join(', ') }}
                                                </span>

                                            {{-- Este documento (una nota de crédito) referencia a una factura --}}
                                            @elseif($doc->referencia)
                                                <span class="badge bg-warning text-dark ms-1">
                                                    Referencia a Factura Nº{{ $doc->referencia->folio }}
                                                </span>
                                            @endif
                                        </td>





                                        <td>{{ $doc->fecha_docto ? \Carbon\Carbon::parse($doc->fecha_docto)->format('d-m-Y') : '-' }}</td>
                                        <td>{{ $doc->fecha_vencimiento ? \Carbon\Carbon::parse($doc->fecha_vencimiento)->format('d-m-Y') : '-' }}</td>
                                        <td class="text-end">${{ number_format($doc->monto_neto, 0, ',', '.') }}</td>
                                        <td class="text-end">${{ number_format($doc->monto_iva_recuperable, 0, ',', '.') }}</td>
                                        <td class="text-end fw-semibold">${{ number_format($doc->monto_total, 0, ',', '.') }}</td>
                                        <td class="text-end fw-semibold {{ $doc->saldo_pendiente == 0 ? 'text-success' : 'text-danger' }}">
                                            ${{ number_format($doc->saldo_pendiente, 0, ',', '.') }}
                                        </td>
                                        

                                        <td>
                                            <div>
                                                {{ $doc->fecha_ultima_gestion 
                                                    ? \Carbon\Carbon::parse($doc->fecha_ultima_gestion)->format('d-m-Y')
                                                    : '-' 
                                                }}
                                            </div>

                                            @if($programacionActiva)
                                                <div class="small text-primary fw-semibold mt-1">
                                                    Próx. pago: {{ $doc->pagoProgramado->fecha_programada?->format('d-m-Y') }}
                                                </div>
                                            @endif
                                        </td>

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Paginación --}}
                    <div class="mt-3 d-flex justify-content-center">
                        {{ $documentosCompras->appends(request()->query())->links('pagination::bootstrap-4') }}
                    </div>
                @else
                    <p class="text-muted text-center mb-0">Aún no hay registros importados.</p>
                @endif
        </x-finanzas.table-card>

    </div>

@include('cobranzas._modal_create_cobranza')
@include('cobranzas.partials.modal_ExportarCompra')
@include('cobranzas.modal_pagos_masivos')
@include('cobranzas.finanzas_compras.modal_sugerencias')
@include('cobranzas.finanzas_compras.modal_proximo_pago')


@vite('resources/js/finanzas_compras_proximo_pago.js')
@vite('resources/js/finanzas_compras_index.js')
@vite('resources/js/modal_pagos_masivos.js')



@endsection
