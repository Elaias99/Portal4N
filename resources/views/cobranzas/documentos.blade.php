@extends('layouts.app')

<style>
    /* ===== Estilos personalizados de la tabla ===== */

    /* Encabezados */
    .table thead th {
        background-color: #f1f3f5;
        font-weight: 600;
        font-size: 1rem;
        text-align: center;
        padding: 14px 12px;
    }

    /* Celdas */
    .table tbody td {
        padding: 16px 12px;
        vertical-align: middle;
        font-size: 0.95rem;
        border-top: 1px solid #e9ecef;
        border-right: 1px solid #e9ecef;
    }

    /* Última columna sin borde */
    .table tbody td:last-child,
    .table thead th:last-child {
        border-right: none;
    }

    /* Zebra striping */
    .table tbody tr:nth-child(odd) {
        background-color: #fcfcfc;
    }

    /* Filas hover */
    .table-hover tbody tr:hover td {
        background-color: #e7f1ff;
    }

    /* Alinear montos a la derecha (sin cambiar tipografía) */
    .text-right {
        text-align: right;
    }

    /* Resaltar monto total */
    .fw-bold {
        color: #0d6efd;
        font-weight: 600;
    }

    /* Contenedor con bordes redondeados */
    .table-container {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }
</style>


@section('content')

    {{-- Mensaje de éxito --}}
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning">
            <strong>{{ session('warning') }}</strong>
            <ul>
                @foreach (session('detalles_errores') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="mb-4">Documentos Financieros</h1>
        </div>

        <div class="row">
            <div class="col-lg-2">
                @component('layouts.columna_izquierda', [
                        'tituloTarjeta' => 'Accesos rápidos',
                        'tituloFiltros' => 'Filtrar Por',
                        'action' => route('cobranzas.documentos')
                ])
                    @slot('acciones')
                        <div class="d-grid gap-2 mt-2">
                            {{-- Formulario de importación --}}
                            <form action="{{ route('cobranzas.import') }}" method="POST" enctype="multipart/form-data" class="mb-3">
                                @csrf
                                <div class="mb-2">
                                    <input type="file" name="file" class="form-control" required>
                                </div>
                                <button class="btn btn-primary w-100" type="submit">Importar Excel</button>
                            </form>
                        </div>

                    @endslot

                    @slot('filtros')
                        <div class="mb-3">
                            <label class="form-label">Razón Social:</label>
                            <input type="text" name="razon_social" class="form-control" placeholder="Buscar..." value="{{ request('razon_social') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">RUT Cliente:</label>
                            <input type="text" name="rut_cliente" class="form-control" placeholder="Ej: 76170725-6" value="{{ request('rut_cliente') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Folio:</label>
                            <input type="text" name="folio" class="form-control" placeholder="Número de folio..." value="{{ request('folio') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fecha Documento:</label>
                            <input type="date" name="fecha_docto" class="form-control" value="{{ request('fecha_docto') }}">
                        </div>
                    @endslot
                @endcomponent
            </div>

            <div class="col-lg-10">
                {{-- Tabla de registros --}}
                <div class="table-responsive table-container">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
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
