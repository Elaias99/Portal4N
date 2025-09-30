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
        max-height: 200px; /* 👈 límite en altura */
        overflow-y: auto; /* 👈 scroll interno */
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
                                        <li>{{ $error }}</li>
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
                            {{-- Botón de exportación --}}
                            <a href="{{ route('cobranzas.export') }}" class="btn btn-success w-100">
                                Exportar Excel
                            </a>
                        </div>



                        <div class="mb-3">
                            <a href="{{ route('cobranzas.index') }}" class="btn btn-outline-secondary w-100">
                                Cobranzas
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
                            <label class="form-label">Fecha Documento:</label>
                            <input type="date" name="fecha_docto" class="form-control" value="{{ request('fecha_docto') }}">
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
                                <th>Fecha Recepción</th>
                                <th>Fecha Acuse Recibo</th>
                                <th>Fecha Reclamo</th>
                                <th class="text-right">Monto Exento</th>
                                <th class="text-right">Monto Neto</th>
                                <th class="text-right">Monto IVA</th>
                                <th class="text-right">Monto Total</th>
                                
                                <th>Tipo Docto. Ref.</th>
                                <th>Folio Docto. Ref.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($documentoFinancieros as $doc)
                                <tr>
                                    <td>
                                        <form action="{{ route('documentos.updateStatus', $doc->id) }}" method="POST" class="status-form">
                                            @csrf
                                            @method('PATCH')

                                            @if($doc->status == 'Pagado')
                                                <span class="status-badge status-pagado">Pagado</span>
                                            @elseif($doc->status == 'Pendiente')
                                                <span class="status-badge status-pendiente">Pendiente</span>
                                            @elseif($doc->status == 'Rechazado')
                                                <span class="status-badge status-rechazado">Rechazado</span>
                                            @else
                                                <span class="text-muted">Sin estado</span>
                                            @endif

                                            <select name="status" onchange="this.form.submit()" class="form-control form-control-sm mt-1">
                                                <option value="" {{ !$doc->status ? 'selected' : '' }}>Sin estado</option>
                                                <option value="Pendiente" {{ $doc->status == 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                                                <option value="Pagado" {{ $doc->status == 'Pagado' ? 'selected' : '' }}>Pagado</option>
                                                <option value="Rechazado" {{ $doc->status == 'Rechazado' ? 'selected' : '' }}>Rechazado</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>{{ $doc->empresa?->Nombre ?? 'Sin empresa' }}</td>
                                    <td>{{ $doc->tipo_doc }}</td>
                                    <td>{{ $doc->rut_cliente }}</td>
                                    <td>{{ $doc->razon_social }}</td>
                                    <td>{{ $doc->folio }}</td>
                                    <td>{{ \Carbon\Carbon::parse($doc->fecha_docto)->format('d-m-Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($doc->fecha_recepcion)->format('d-m-Y H:i') }}</td>
                                    <td>{{ $doc->fecha_acuse_recibo ? \Carbon\Carbon::parse($doc->fecha_acuse_recibo)->format('d-m-Y H:i') : '-' }}</td>
                                    <td>{{ $doc->fecha_reclamo ? \Carbon\Carbon::parse($doc->fecha_reclamo)->format('d-m-Y H:i') : '-' }}</td>
                                    <td class="text-right">${{ number_format($doc->monto_exento, 0, ',', '.') }}</td>
                                    <td class="text-right">${{ number_format($doc->monto_neto, 0, ',', '.') }}</td>
                                    <td class="text-right">${{ number_format($doc->monto_iva, 0, ',', '.') }}</td>
                                    <td class="text-right fw-bold">${{ number_format($doc->monto_total, 0, ',', '.') }}</td>
                                    <td>{{ $doc->tipo_docto_referencia }}</td>
                                    <td>{{ $doc->folio_docto_referencia }}</td>
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
@endsection
