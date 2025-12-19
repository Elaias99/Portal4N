@extends('layouts.app')


@section('content')

    {{-- 🔹 Mensajes de estado --}}
    {{-- 🟢 ÉXITO --}}
    @if(session('success'))
        <div class="alert alert-success custom-alert mx-auto shadow-sm" style="max-width:100%; border-left:5px solid #28a745; border-radius:10px; padding:12px 16px;">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                <div><strong>Éxito:</strong> {{ session('success') }}</div>
            </div>
        </div>
    @endif

    {{-- 🟡 ADVERTENCIA --}}
    @if(session('warning'))
        <div class="alert alert-warning custom-alert mx-auto shadow-sm" style="max-width:100%; border-left:5px solid #ffc107; border-radius:10px; padding:12px 16px;">
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
                                <li class="mb-1">Folio duplicado: {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- 🔴 ERROR --}}
    @if(session('error'))
        <div class="alert alert-danger custom-alert mx-auto shadow-sm" style="max-width:100%; border-left:5px solid #dc3545; border-radius:10px; padding:12px 16px;">
            <div class="d-flex align-items-center">
                <i class="bi bi-x-circle-fill text-danger me-2"></i>
                <div><strong>Error:</strong> {{ session('error') }}</div>
            </div>
        </div>
    @endif


    {{-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// --}}
    {{-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// --}}
    {{-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// --}}
    {{-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// --}}
<div class="container" style="max-width: 100%;">
    {{-- Volver --}}
    <div class="mb-3">
        <a href="{{ route('cobranzas.general') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa fa-arrow-left"></i> Volver al Panel Principal
        </a>
    </div>

    <h1 class="text-center mb-4">Reporte Cuentas por Pagar</h1>

    {{-- === FILTROS + GESTIÓN MASIVA === --}}
    <div class="d-flex justify-content-between align-items-start gap-3 mb-4" style="align-items: stretch;">

        {{-- TARJETA DE FILTROS --}}
        <div class="flex-grow-1">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
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





                            <div class="col-md-1">
                                <label class="form-label small text-muted">Saldo Pendiente</label>
                                <input 
                                        type="text" 
                                        name="saldo_pendiente" 
                                        class="form-control form-control-sm"
                                        placeholder="Ej: 260000" 
                                        value="{{ request('saldo_pendiente') }}"
                                        min="0"
                                    >
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


                            {{-- 🔹 Fecha de Documento --}}
                            <div class="col-md-1 dropdown-fechas">
                                <label class="form-label small text-muted">Fecha Documento</label>
                                <div class="dropdown w-100">
                                    <button class="btn dropdown-toggle btn-sm w-100 text-start" type="button"
                                            id="dropdownFechasDocto" data-bs-toggle="dropdown" aria-expanded="false">
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

                            {{-- 🔹 Fecha de Vencimiento --}}
                            <div class="col-md-1 dropdown-fechas">
                                <label class="form-label small text-muted">Fecha Vencimiento</label>
                                <div class="dropdown w-100">
                                    <button class="btn dropdown-toggle btn-sm w-100 text-start" type="button"
                                            id="dropdownFechasVenc" data-bs-toggle="dropdown" aria-expanded="false">
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


                        </div>

                        {{-- Botones de acción --}}
                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <a href="{{ route('finanzas_compras.index') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-x-circle"></i> Limpiar
                            </a>
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="bi bi-search"></i> Filtrar
                            </button>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            {{-- 🔹 Texto alineado a la izquierda --}}
                            <div>
                                <strong>Saldo pendiente total:</strong> 
                                <span class="text-success fw-semibold">
                                    ${{ number_format($totalSaldoPendiente, 0, ',', '.') }}
                                </span>
                            </div>

                            <a href="{{ route('cobranzas-compras.index') }}" class="btn btn-outline-secondary btn-sm">
                                Detalle Proveedor
                            </a>


                        </div>


                    </form>
                </div>
            </div>
        </div>

        {{-- TARJETA DE GESTIÓN MASIVA --}}
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <h6 class="fw-bold mb-3">Gestión Masiva</h6>



                @if (Auth::id() != 375)
                    {{-- Nuevo botón Historial --}}
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




                <!-- Botón de Exportar que abre el modal -->
                <button type="button"
                        class="btn btn-outline-success btn-sm w-100 mb-3 d-flex align-items-center justify-content-center gap-2"
                        data-bs-toggle="modal" data-bs-target="#modalExportarCompra">
                    <i class="bi bi-file-earmark-arrow-down"></i>
                    <span>Exportar Excel</span>
                </button>


                
                <button type="button"
                                class="btn btn-outline-primary btn-sm w-100 mb-3 d-flex align-items-center justify-content-center gap-2"
                                data-bs-toggle="modal"
                                data-bs-target="#modalPagosMasivos">
                    <i class="bi bi-cash-stack"></i>
                    <span>Pagos Masivos</span>
                </button>
                







            </div>
        </div>


    </div>




    {{-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// --}}
    {{-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// --}}
    {{-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// --}}
    {{-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// --}}
    {{-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// --}}
    {{-- 🔹 Tabla de registros limpia y responsiva sin scroll --}}
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="card-title mb-3">Documentos Importados</h5>

            @if($documentosCompras->count() > 0)
                <div class="table-responsive-sm">
                    <table class="table table-striped table-hover table-sm align-middle text-center">

                        
                        <thead class="table-light text-uppercase align-middle">
                            <tr class="small">

                                {{-- 🏢 Empresa --}}
                                @include('cobranzas.partials.filtros_compras', [
                                    'label' => 'Empresa',
                                    'columna' => 'empresa_id',
                                    'sortBy' => $sortBy ?? null,
                                    'sortOrder' => $sortOrder ?? 'asc',
                                    'placeholder' => 'Buscar empresa...'
                                ])

                                {{-- ⚙️ Status (status_original) --}}
                                @include('cobranzas.partials.filtros_compras', [
                                    'label' => 'Status',
                                    'columna' => 'status_original',
                                    'sortBy' => $sortBy ?? null,
                                    'sortOrder' => $sortOrder ?? 'asc',
                                    'placeholder' => 'Al día / Vencido...'
                                ])

                                {{-- 🧾 Tipo Doc --}}
                                @include('cobranzas.partials.filtros_compras', [
                                    'label' => 'Tipo Doc',
                                    'columna' => 'tipo_documento_id',
                                    'sortBy' => $sortBy ?? null,
                                    'sortOrder' => $sortOrder ?? 'asc',
                                    'placeholder' => 'Buscar tipo doc...'
                                ])

                                {{-- 🛒 Tipo Compra (sin filtro directo) --}}
                                {{-- <th>Tipo Compra</th> --}}

                                {{-- 🆔 RUT Proveedor --}}
                                @include('cobranzas.partials.filtros_compras', [
                                    'label' => 'RUT Proveedor',
                                    'columna' => 'rut_proveedor',
                                    'sortBy' => $sortBy ?? null,
                                    'sortOrder' => $sortOrder ?? 'asc',
                                    'placeholder' => 'Ej: 76123456-7'
                                ])

                                {{-- 🏢 Razón Social --}}
                                @include('cobranzas.partials.filtros_compras', [
                                    'label' => 'Razón Social',
                                    'columna' => 'razon_social',
                                    'sortBy' => $sortBy ?? null,
                                    'sortOrder' => $sortOrder ?? 'asc',
                                    'placeholder' => 'Buscar razón social...'
                                ])

                                {{-- 📄 Folio --}}
                                @include('cobranzas.partials.filtros_compras', [
                                    'label' => 'Folio',
                                    'columna' => 'folio',
                                    'sortBy' => $sortBy ?? null,
                                    'sortOrder' => $sortOrder ?? 'asc',
                                    'placeholder' => 'N° folio...'
                                ])

                                {{-- 📅 Fecha Docto --}}
                                @include('cobranzas.partials.filtros_compras', [
                                    'label' => 'Fecha Docto',
                                    'columna' => 'fecha_docto',
                                    'sortBy' => $sortBy ?? null,
                                    'sortOrder' => $sortOrder ?? 'asc',
                                    'placeholder' => 'AAAA-MM-DD'
                                ])

                                {{-- 📅 Fecha Vencimiento --}}
                                @include('cobranzas.partials.filtros_compras', [
                                    'label' => 'Fecha Vencimiento',
                                    'columna' => 'fecha_vencimiento',
                                    'sortBy' => $sortBy ?? null,
                                    'sortOrder' => $sortOrder ?? 'asc',
                                    'placeholder' => 'AAAA-MM-DD'
                                ])

                                {{-- 💰 Monto Neto --}}
                                <th>Monto Neto</th>

                                {{-- 💵 IVA Rec --}}
                                <th>IVA Rec.</th>

                                {{-- 💰 Total --}}
                                @include('cobranzas.partials.filtros_compras', [
                                    'label' => 'Total',
                                    'columna' => 'monto_total',
                                    'sortBy' => $sortBy ?? null,
                                    'sortOrder' => $sortOrder ?? 'asc',
                                    'placeholder' => '≥ monto...'
                                ])

                                {{-- 💸 Saldo Pendiente (sin filtro directo) --}}
                                <th>Saldo Pendiente</th>



                                {{-- ⚡ Acción --}}
                                {{-- <th>Acción</th> --}}


                                {{-- 📅 Fecha Estado Manual --}}
                                @include('cobranzas.partials.filtros_compras', [
                                    'label' => 'Fecha Estado Manual',
                                    'columna' => 'fecha_estado_manual',
                                    'sortBy' => $sortBy ?? null,
                                    'sortOrder' => $sortOrder ?? 'asc',
                                    'placeholder' => 'AAAA-MM-DD'
                                ])

                            </tr>
                        </thead>


                        <tbody>
                            @foreach ($documentosCompras as $doc)
                                @php
                                    $color = $doc->status_original === 'Vencido' ? 'bg-danger' : 'bg-success';
                                    $estadoMostrar = $doc->estado_visible;
                                @endphp



                                <tr class="small">
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
                                    <td>{{ $doc->fecha_vencimiento }}</td>
                                    <td class="text-end">${{ number_format($doc->monto_neto, 0, ',', '.') }}</td>
                                    <td class="text-end">${{ number_format($doc->monto_iva_recuperable, 0, ',', '.') }}</td>
                                    <td class="text-end fw-semibold">${{ number_format($doc->monto_total, 0, ',', '.') }}</td>
                                    <td class="text-end fw-semibold {{ $doc->saldo_pendiente == 0 ? 'text-success' : 'text-danger' }}">
                                        ${{ number_format($doc->saldo_pendiente, 0, ',', '.') }}
                                    </td>
                                    
                                    {{-- <td>
                                        @if(!$esNotaCredito)
                                            <a href="{{ route('finanzas_compras.show', $doc->id) }}?{{ http_build_query(request()->query()) }}" 
                                            class="btn btn-outline-primary btn-sm w-100">
                                                <i class="bi bi-eye"></i> Ver
                                            </a>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td> --}}

                                    <td>{{ $doc->fecha_estado_manual ?? '-' }}</td>

                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- 🔹 Paginación --}}
                <div class="mt-3 d-flex justify-content-center">
                    {{ $documentosCompras->appends(request()->query())->links('pagination::bootstrap-4') }}
                </div>
            @else
                <p class="text-muted text-center mb-0">Aún no hay registros importados.</p>
            @endif
        </div>
    </div>


</div>


<script>
    function toggleFechaEstado(select, id) {
        const inputFecha = document.getElementById('fecha-input-' + id);
        const hiddenFecha = document.getElementById('fecha-hidden-' + id);

        // Mostrar el campo de fecha solo para estados manuales
        if (['Abono', 'Pago', 'Pronto pago', 'Cobranza judicial'].includes(select.value)) {
            if (inputFecha) inputFecha.style.display = 'block';
        } else {
            if (inputFecha) {
                inputFecha.style.display = 'none';
                inputFecha.value = '';
            }
            if (hiddenFecha) hiddenFecha.value = '';
        }
    }

    // 👇 Este bloque asegura que Bootstrap Modal esté correctamente inicializado
    document.addEventListener('DOMContentLoaded', function () {
        const modalElements = document.querySelectorAll('.modal');
        modalElements.forEach(function (modalEl) {
            modalEl.addEventListener('show.bs.modal', function () {
                // Reposicionar o limpiar formularios si hace falta
            });
        });
    });
</script>

@include('cobranzas._modal_create_cobranza')
@include('cobranzas.partials.modal_ExportarCompra')
@include('cobranzas.modal_pagos_masivos')
@include('cobranzas.finanzas_compras.modal_sugerencias')




@endsection
