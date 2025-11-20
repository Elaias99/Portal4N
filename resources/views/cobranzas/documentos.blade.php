@extends('layouts.app')

@vite('resources/css/cuentas-cobrar.css')

@section('content')

{{-- MENSAJES UNIFICADOS Y OPTIMIZADOS --}}
<div class="container" style="max-width: 1150px;">

    {{-- 🔴 ERROR --}}
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


    {{-- 🟡 WARNING --}}
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


    {{-- 🟢 SUCCESS --}}
    @if(session('success'))
        <div class="alert alert-success custom-alert mx-auto shadow-sm" style="max-width: 100%; border-left:5px solid #28a745; border-radius:10px; padding:12px 16px;">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                <div><strong>Éxito:</strong> {{ session('success') }}</div>
            </div>
        </div>
    @endif

</div>





    <div class="container-fluid" style="max-width: 100%;">

        <div class="mb-3">
            <a href="{{ route('cobranzas.general') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fa fa-arrow-left"></i> Volver al Panel Principal
            </a>
        </div>

        <div class="mb-1">
            <h1 class="text-center mb-3" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);">
                Reporte Cuentas por Cobrar
            </h1>
        </div>

        {{-- === FILTROS + GESTIÓN MASIVA === --}}
        <div class="d-flex justify-content-between align-items-start gap-3 mb-4" style="align-items: stretch;">

            {{-- TARJETA DE FILTROS --}}
            <div class="flex-grow-1">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <form method="GET" action="{{ route('cobranzas.documentos') }}">
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

                                {{-- 🔹 Filtro solo con dos opciones --}}
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


                                {{-- 🔹 Filtro por estado actual (manual) --}}
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
                                {{-- 🔹 Texto alineado a la izquierda --}}
                                <div>
                                    <strong>Saldo pendiente total:</strong> 
                                    <span class="text-success fw-semibold">
                                        ${{ number_format($totalSaldoPendiente, 0, ',', '.') }}
                                    </span>
                                </div>

                                {{-- 🔹 Botón alineado a la derecha --}}
                                <a href="{{ route('cobranzas.index') }}" class="btn btn-outline-secondary btn-sm">
                                    Detalle Cliente
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

                        {{-- 🔹 Nuevo botón de Historial de Movimientos --}}
                        @if (Auth::id() != 375)
                            <a href="{{ route('panelfinanza.show') }}" 
                            class="btn btn-outline-secondary btn-sm w-100 mb-3 d-flex align-items-center justify-content-center gap-2">
                                <i class="fa-solid fa-clock-rotate-left"></i> 
                                <span>Historial de Movimientos</span>
                            </a>
                        @endif

                        {{-- 🔹 Importación de Excel --}}
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




                        {{-- PAGO MASIVO --}}

                        {{-- @if (Auth::id() != 375)
                            <button type="button"
                                    class="btn btn-outline-primary btn-sm w-100 mb-3 d-flex align-items-center justify-content-center gap-2"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalPagosMasivos">
                                <i class="bi bi-cash-stack"></i>
                                <span>Pagos Masivos</span>
                            </button>
                        @endif --}}












                    </div>
                </div>
          

        </div>

        {{-- === TABLA DE REGISTROS === --}}
        <div class="table-responsive rounded shadow-sm">
            <table class="table table-hover align-middle custom-table">


                @include('cobranzas.partials.filtros')





                

                <tbody>
                    @foreach ($documentoFinancieros as $doc)
                        <tr>
                            {{-- 🔹 Estado visible según status_original (solo 2 colores) --}}
                            <td>
                                @php
                                    // El color siempre depende del estado automático
                                    $color = $doc->status_original === 'Vencido' ? 'bg-danger' : 'bg-success';

                                    // Solo usamos el status manual si es uno de los estados válidos
                                    $estadosManuales = ['Abono', 'Cruce', 'Pago', 'Pronto pago', 'Cobranza judicial'];

                                    $estadoMostrar = in_array($doc->status, $estadosManuales)
                                        ? $doc->status
                                        : $doc->status_original;
                                @endphp

                                <span class="badge {{ $color }}">
                                    {{ $estadoMostrar }}
                                </span>

                                {{-- Botón Editar (solo si NO es Nota de Crédito) --}}
                                @if($doc->tipo_documento_id != 61)
                                    @if (Auth::id() != 375)
                                        <button type="button"
                                                class="btn btn-sm btn-outline-secondary mt-2"
                                                data-toggle="modal"
                                                data-target="#modalStatus-{{ $doc->id }}">
                                            Editar
                                        </button>
                                    @endif

                                    @include('cobranzas.modal_status', ['doc' => $doc])
                                @else
                                    <small class="text-muted d-block mt-2">No editable</small>
                                @endif
                            </td>


                            {{-- 🔹 Empresa --}}
                            <td class="text-nowrap">{{ $doc->empresa?->Nombre ?? 'Sin empresa' }}</td>

                            {{-- 🔹 Tipo Documento --}}
                            {{-- 🔹 Tipo Documento --}}
                            <td class="text-nowrap">{{ $doc->tipoDocumento?->nombre ?? 'Sin tipo' }}</td>



                            {{-- 🔹 Rut Cliente --}}
                            <td class="text-nowrap">{{ $doc->rut_cliente }}</td>

                            {{-- 🔹 Razón Social --}}
                            <td class="text-nowrap">{{ $doc->razon_social }}</td>

                            {{-- 🔹 Folio --}}
                            <td>{{ $doc->folio }}
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





                            {{-- 🔹 Fecha Documento --}}
                            <td><span style="white-space: nowrap;">{{ $doc->fecha_docto ? \Carbon\Carbon::parse($doc->fecha_docto)->format('d-m-Y') : '-' }}</span></td>


                            <td><span style="white-space: nowrap;">{{ $doc->fecha_vencimiento ? \Carbon\Carbon::parse($doc->fecha_vencimiento)->format('d-m-Y') : '-' }}</span></td>


                            <td><span style="white-space: nowrap;">{{ $doc->fecha_estado_manual ? \Carbon\Carbon::parse($doc->fecha_estado_manual)->format('d-m-Y') : '-' }}</span></td>


                            {{-- 🔹 Montos --}}
                            <td class="text-right">${{ number_format($doc->monto_exento, 0, ',', '.') }}</td>
                            <td class="text-right">${{ number_format($doc->monto_neto, 0, ',', '.') }}</td>
                            <td class="text-right">${{ number_format($doc->monto_iva, 0, ',', '.') }}</td>
                            <td class="text-right fw-bold">${{ number_format($doc->monto_total, 0, ',', '.') }}</td>

                            {{-- 🔹 Saldo Pendiente --}}
                            <td class="text-right fw-bold {{ $doc->saldo_pendiente == 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($doc->saldo_pendiente, 0, ',', '.') }}
                            </td>


                            {{-- 🔹 Abonos --}}
                            <td class="text-center">

                                {{-- 🚫 Si es una Nota de Crédito --}}
                                @if($doc->tipo_documento_id == 61)
                                    <span class="text-muted d-block">No aplica</span>
                                @else
                                    {{-- 🔹 Un solo botón para ver todos los detalles --}}
                                    <a href="{{ route('documentos.detalles', $doc->id) }}?{{ http_build_query(request()->query()) }}" 
                                    class="btn btn-sm btn-outline-primary w-100">
                                    <i class="bi bi-eye"></i> Ver Detalles
                                    </a>

                                @endif

                            </td>



                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        <div class="mt-3 d-flex justify-content-center">
            {{ $documentoFinancieros->appends(request()->query())->links('pagination::bootstrap-4') }}
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
    </script>

@include('cobranzas._modal_create_cobranza')
@include('cobranzas.modal_pagos_masivos')
@include('cobranzas.partials.modal_ExportarVenta')


@endsection



