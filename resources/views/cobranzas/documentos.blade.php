@extends('layouts.app')

<style>
    /* ===== Estilos premium de la tabla ===== */

    body {
        font-family: 'Segoe UI', Tahoma, sans-serif;
        background-color: #f8f9fb;
    }

    h1 {
        font-weight: 700;
        color: #1c1c1c;
        font-size: 1.8rem;
    }

    /* Encabezados */
    .table thead th {
        background-color: #f4f6f8;
        font-weight: 600;
        font-size: 0.9rem;
        text-align: center;
        padding: 12px 10px;
        border-bottom: 2px solid #dee2e6;
    }

    /* Celdas */
    .table tbody td {
        padding: 12px 10px;
        vertical-align: middle;
        font-size: 0.88rem;
        border-top: 1px solid #e9ecef;
    }

    /* Zebra striping */
    .table tbody tr:nth-child(odd) {
        background-color: #fafbfc;
    }

    /* Filas hover */
    .table-hover tbody tr:hover td {
        background-color: #eef4fb;
    }

    /* Alinear montos */
    .text-right {
        text-align: right;
    }

    /* Resaltar monto total */
    .fw-bold {
        color: #0d47a1;
        font-weight: 600;
    }

    /* Contenedor elegante */
    .table-container {
        border: 1px solid #dee2e6;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 3px 8px rgba(0,0,0,0.06);
        background: #fff;
    }

    /* Estilos para STATUS */
    .status-badge {
        display: inline-block;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 5px 10px;
        border-radius: 20px;
        min-width: 90px;
        text-align: center;
    }
    .status-pendiente {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }
    .status-pagado {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .status-rechazado {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .status-form select {
        font-size: 0.8rem;
        border-radius: 6px;
        padding: 4px 6px;
    }

    .custom-alert {
        border-left: 6px solid;
        border-radius: 8px;
        padding: 12px 15px;
        background-color: #fff;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        margin-bottom: 15px;
        font-size: 0.9rem;
    }

    .error-list {
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #f1f1f1;
        border-radius: 6px;
        padding: 10px;
        background: #fffdf6;
    }

    .alert-success {
        border-left-color: #28a745 !important;
    }

    .alert-warning {
        border-left-color: #ffc107 !important;
    }

    .alert-icon {
        width: 12px;
        height: 12px;
        border-radius: 50%;
    }

    .alert ul li {
        margin-bottom: 4px;
    }
</style>

@section('content')

    {{-- Mensajes --}}
    @if(session('success'))
        <div class="alert alert-success custom-alert">
            <div class="d-flex align-items-center">
                <div class="alert-icon bg-success mr-2"></div>
                <div>
                    <strong>Éxito:</strong> {{ session('success') }}
                </div>
            </div>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning custom-alert">
            <div class="d-flex align-items-start">
                <div class="alert-icon bg-warning mr-2"></div>
                <div>
                    <strong>Atención:</strong> {{ session('warning') }}
                    @if(session('detalles_errores'))
                        <button class="btn btn-link btn-sm p-0 ml-2" 
                                type="button" 
                                data-toggle="collapse" 
                                data-target="#detallesErrores">
                            Ver detalles
                        </button>
                        <div id="detallesErrores" class="collapse mt-2">
                            <div class="error-list">
                                <ul class="small mb-0">
                                    @foreach (session('detalles_errores') as $error)
                                        <li>{!! $error !!}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Reporte de Documentos Financieros</h1>
        </div>

        <div class="row">
            {{-- Filtros e Importación --}}
            <div class="col-lg-3 mb-4">
                @component('layouts.columna_izquierda', [
                    'tituloTarjeta' => 'Accesos rápidos',
                    'tituloFiltros' => 'Filtrar Por',
                    'action' => route('cobranzas.documentos')
                ])
                    @slot('acciones')
                        <div class="mb-3">
                            <form action="{{ route('cobranzas.import') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="file" name="file" class="form-control mb-2" required>
                                <button class="btn btn-primary w-100" type="submit">Importar Excel</button>
                            </form>
                        </div>

                        <div class="mb-3">
                            <a href="{{ route('cobranzas.export') }}" class="btn btn-success w-100">
                                Exportar Excel
                            </a>
                        </div>

                        <div class="mb-3">
                            <a href="{{ route('cobranzas.index') }}" class="btn btn-outline-secondary w-100">
                                Creditos
                            </a>
                        </div>



                    @endslot

                    @slot('filtros')
                        <div class="mb-3">
                            <label class="form-label">Razón Social:</label>
                            <input type="text" name="razon_social" class="form-control" value="{{ request('razon_social') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">RUT Cliente:</label>
                            <input type="text" name="rut_cliente" class="form-control" value="{{ request('rut_cliente') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Folio:</label>
                            <input type="text" name="folio" class="form-control" value="{{ request('folio') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Fecha Docto:</label>
                            <div class="row">
                                <div class="col-6">
                                    <label class="small text-muted">Desde</label>
                                    <input type="date" 
                                        name="fecha_inicio" 
                                        class="form-control" 
                                        value="{{ request('fecha_inicio') }}">
                                </div>
                                <div class="col-6">
                                    <label class="small text-muted">Hasta</label>
                                    <input type="date" 
                                        name="fecha_fin" 
                                        class="form-control" 
                                        value="{{ request('fecha_fin') }}">
                                </div>
                            </div>
                        </div>


                        <div class="mb-3">
                            <label class="form-label">Fecha Vencimiento:</label>
                            <div class="row">
                                <div class="col-6">
                                    <label class="small text-muted">Desde</label>
                                    <input type="date" 
                                        name="vencimiento_inicio" 
                                        class="form-control" 
                                        value="{{ request('vencimiento_inicio') }}">
                                </div>
                                <div class="col-6">
                                    <label class="small text-muted">Hasta</label>
                                    <input type="date" 
                                        name="vencimiento_fin" 
                                        class="form-control" 
                                        value="{{ request('vencimiento_fin') }}">
                                </div>
                            </div>
                        </div>


                        







                        <div class="mb-3">
                            <label class="form-label">Estado:</label>
                            <select name="status" class="form-control">
                                <option value="">-- Todos --</option>
                                <option value="Al día" {{ request('status') == 'Al día' ? 'selected' : '' }}>Al día</option>
                                <option value="Vencido" {{ request('status') == 'Vencido' ? 'selected' : '' }}>Vencido</option>
                                <option value="Abono" {{ request('status') == 'Abono' ? 'selected' : '' }}>Abono</option>
                                <option value="Pago" {{ request('status') == 'Pago' ? 'selected' : '' }}>Pago</option>
                                <option value="Cobranza judicial" {{ request('status') == 'Cobranza judicial' ? 'selected' : '' }}>Cobranza judicial</option>
                            </select>
                        </div>

                    @endslot
                @endcomponent
            </div>

            {{-- Tabla principal --}}
            <div class="col-lg-9">
                <div class="table-responsive table-container">
                    <table class="table table-hover align-middle">


                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Empresa</th>
                            <th>Tipo Doc</th>
                            <th>Rut Cliente</th>
                            <th>Razón Social</th>
                            <th>Folio</th>
                            <th>Fecha Docto</th>
                            <th>Fecha Vencimiento</th>
                            <th>Fecha Estado Manual</th>
                            <th class="text-right">Monto Exento</th>
                            <th class="text-right">Monto Neto</th>
                            <th class="text-right">Monto IVA</th>
                            <th class="text-right">Monto Total</th>
                            <th class="text-right">Saldo Pendiente</th>
                            <th class="text-right">Acción</th>
                        </tr>
                    </thead>


                    <tbody>
                        @foreach ($documentoFinancieros as $doc)
                            <tr>
                                <td>

                                    @if($doc->status_final === 'Vencido')
                                        <span class="status-badge status-rechazado">Vencido</span>
                                    @elseif($doc->status_final === 'Al día')
                                        <span class="status-badge status-pagado">Al día</span>
                                    @elseif(in_array($doc->status_final, ['Abono', 'Pago', 'Cobranza judicial']))
                                        <span class="status-badge {{ $doc->esta_vencido ? 'status-rechazado' : 'status-pagado' }}">
                                            {{ $doc->status_final }}
                                        </span>
                                    @else
                                        <span class="text-muted">Sin estado</span>
                                    @endif



                                    {{-- Botón para abrir modal --}}
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-secondary mt-2" 
                                            data-toggle="modal" 
                                            data-target="#modalStatus-{{ $doc->id }}">
                                        Editar
                                    </button>

                                    @include('cobranzas.modal_status', ['doc' => $doc])
                                </td>

                                <td>{{ $doc->empresa?->Nombre ?? 'Sin empresa' }}</td>
                                <td>{{ $doc->tipo_doc }}</td>
                                <td>{{ $doc->rut_cliente }}</td>
                                <td>{{ $doc->razon_social }}</td>
                                <td>{{ $doc->folio }}</td>
                                <td>{{ \Carbon\Carbon::parse($doc->fecha_docto)->format('d-m-Y') }}</td>

                                <td>
                                    @if($doc->fecha_vencimiento)
                                        {{ \Carbon\Carbon::parse($doc->fecha_vencimiento)->format('d-m-Y') }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <td>
                                    @if($doc->fecha_estado_manual)
                                        {{ \Carbon\Carbon::parse($doc->fecha_estado_manual)->format('d-m-Y') }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <td class="text-right">${{ number_format($doc->monto_exento, 0, ',', '.') }}</td>
                                <td class="text-right">${{ number_format($doc->monto_neto, 0, ',', '.') }}</td>
                                <td class="text-right">${{ number_format($doc->monto_iva, 0, ',', '.') }}</td>
                                <td class="text-right fw-bold">${{ number_format($doc->monto_total, 0, ',', '.') }}</td>

                                {{-- Saldo Pendiente --}}
                                <td class="text-right text-danger fw-bold">
                                    ${{ number_format($doc->monto_total - $doc->abonos->sum('monto'), 0, ',', '.') }}
                                </td>

                                <td class="text-center">
                                    @if($doc->abonos->isNotEmpty())
                                        <a href="{{ route('abonos.index', $doc->id) }}" class="btn btn-sm btn-outline-primary">
                                            Ver abonos ({{ $doc->abonos->count() }})
                                        </a>
                                    @else
                                        <span class="text-muted">Sin abonos</span>
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
        </div>
    </div>

<script>
    function toggleFechaEstado(select, id) {
        const inputFecha = document.getElementById('fecha-input-' + id);
        if (['Abono', 'Pago', 'Cobranza judicial'].includes(select.value)) {
            inputFecha.style.display = 'block';
        } else {
            inputFecha.style.display = 'none';
            inputFecha.value = '';
            document.getElementById('fecha-hidden-' + id).value = '';
        }
    }
</script>

@endsection
