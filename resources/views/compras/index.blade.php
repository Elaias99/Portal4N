@extends('layouts.app')
@section('content')

<style>

    /* --- TABLA CUSTOM --- */
    .custom-table thead {
        background-color: #f8f9fa;   /* gris claro */
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .custom-table th, 
    .custom-table td {
        border-color: #e9ecef !important;  /* bordes más suaves */
        padding: 0.75rem 0.5rem;
        vertical-align: middle;
    }

    .custom-table tbody tr:hover {
        background-color: #f5f7fa;  /* efecto hover más sutil */
    }

    /* Estado con colores */
    .custom-table .badge {
        font-size: 0.75rem;
        padding: 0.5em 0.6em;
        border-radius: 0.5rem;
    }


    



</style>


@if(session('compras_importadas'))
    <div class="container mb-4">
        <div class="alert alert-success col-lg-8 mx-auto">
            <h5>Importación exitosa</h5>
            <p>
                Se importaron correctamente 
                <strong>{{ session('compras_importadas') }}</strong> compras.
            </p>

            @if(session('compras_exitosas') && count(session('compras_exitosas')) > 0)
                <div style="max-height: 200px; overflow-y: auto;" class="mt-2">
                    <h6>Detalle:</h6>
                    <ul class="mb-0 small">
                        @foreach(session('compras_exitosas') as $compra)
                            <li>
                                {{ $compra['proveedor'] }} — 
                                Documento: {{ $compra['numero_documento'] ?? '-' }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
@endif


@if(session('errorsFK') || session('errorsDuplicados'))
<div class="container mb-4">

    @if(session('errorsFK'))
        <div class="alert alert-danger col-lg-8 mx-auto" style="max-height: 200px; overflow-y: auto;">
            <h5>Errores de Claves Foráneas</h5>
            <ul class="mb-0">
                @foreach(session('errorsFK') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('errorsDuplicados'))
        <div class="alert alert-warning col-lg-8 mx-auto" style="max-height: 200px; overflow-y: auto;">
            <h5>Duplicados Detectados</h5>
            <ul class="mb-0">
                @foreach(session('errorsDuplicados') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endif





<div class="container">
    <h1 class="text-center mb-4" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);">Lista de Compras</h1>

    @if(session('proveedores_faltantes_excel') && count(session('proveedores_faltantes_excel')) > 0)
        <a href="{{ route('compras.exportarProveedoresFaltantes') }}" 
        class="btn btn-outline-warning btn-block py-2 d-flex align-items-center justify-content-center mb-2">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> Descargar Proveedores Faltantes
        </a>
    @endif

    <div class="row">

        {{-- FILTROS Y GESTIÓN --}}
        <div class="col-lg-2 mb-1">
            @component('layouts.columna_izquierda', [
                'tituloTarjeta' => 'Gestión Masiva de Compras',
                'tituloFiltros' => 'Filtrar Compras',
                'action' => route('compras.index')
            ])
                @slot('acciones')
                    {{-- Importar --}}
                    <form class="mb-1">
                        @csrf
                        <input type="file" name="archivo" id="archivoInput" accept=".xlsx,.xls" style="display: none;">
                        <button type="button" class="btn btn-outline-success btn-block py-2 d-flex align-items-center justify-content-center"
                            data-toggle="modal" data-target="#modalImportarExcelCompras">
                            <i class="fa-solid fa-file-excel me-1"></i> Importar Excel
                        </button>
                    </form>

                    {{-- Exportar --}}
                    <form class="mb-1">
                        <button type="button" class="btn btn-outline-success btn-block py-2 d-flex align-items-center justify-content-center"
                            data-toggle="modal" data-target="#modalExportarCompras">
                            <i class="fa-solid fa-file-excel me-1"></i> Exportar Excel
                        </button>
                    </form>

                @endslot

                @slot('filtros')
                    <div class="mb-3">
                        <label class="form-label">Razón Social:</label>
                        <input type="text" name="search" class="form-control" placeholder="Ej: Acme Ltda." value="{{ request('search') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">RUT Proveedor:</label>
                        <input type="text" name="rut" class="form-control" placeholder="Ej: 12345678-9" value="{{ request('rut') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Estado:</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            @foreach (['Pendiente', 'Pagado', 'Abonado', 'No Pagar'] as $estado)
                                <option value="{{ $estado }}" {{ request('status') == $estado ? 'selected' : '' }}>
                                    {{ $estado }}
                                </option>
                            @endforeach
                        </select>
                    </div>


                    {{-- <div class="mb-3">
                        <label class="form-label">Plazo de Pago:</label>
                        <select name="plazo_pago_id" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            @foreach ($plazosPago as $plazo)
                                <option value="{{ $plazo->id }}" {{ request('plazo_pago_id') == $plazo->id ? 'selected' : '' }}>
                                    {{ $plazo->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div> --}}


                    <div class="mb-3">
                        <label class="form-label">Fecha Documento:</label>
                        <div class="input-group input-group-sm mb-3">
                            <span class="input-group-text">Desde</span>
                            <input type="date" name="fecha_desde" class="form-control" value="{{ request('fecha_desde') }}">
                        </div>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Hasta</span>
                            <input type="date" name="fecha_hasta" class="form-control" value="{{ request('fecha_hasta') }}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Fecha Vencimiento:</label>
                        <div class="input-group input-group-sm mb-3">
                            <span class="input-group-text">Desde</span>
                            <input type="date" name="vencimiento_desde" class="form-control" value="{{ request('vencimiento_desde') }}">
                        </div>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Hasta</span>
                            <input type="date" name="vencimiento_hasta" class="form-control" value="{{ request('vencimiento_hasta') }}">
                        </div>
                    </div>

                @endslot
            @endcomponent
        </div>

        <div class="col-lg-10">

            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">

                <div class="d-flex flex-wrap align-items-center">
                    <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2" data-toggle="modal" data-target="#modalImportarComprasInfo">
                        <i class="fa fa-info-circle mr-1"></i> Ver estructura y plantilla
                    </button>
                </div>
                <a href="{{ route('compras.create') }}" class="btn btn-primary btn-sm shadow-sm">
                    <i class="fa-solid fa-cart-plus me-1"></i> Agregar Compra 
                </a>
            </div>

            

            <div class="table-responsive rounded shadow-sm">
                <table class="table table-hover align-middle custom-table">


                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Estado</th>
                            <th>Usuario</th>

                            <th class="w-25">Proveedor</th>
                            <th class="d-none d-md-table-cell">RUT Proveedor</th>
                            <th>Empresa</th>

                            <th>Tipo Documento</th>
                            <th>N° Doc.</th>
                            <th class="text-center">Fecha Documento</th>

                            <th>Centro Costo</th>
                            <th class="w-25">Glosa</th>
                            <th class="d-none d-md-table-cell">Observación</th>
                            <th class="d-none d-md-table-cell">OC</th>

                            <th>Plazo de Pago</th>
                            <th class="d-none d-md-table-cell">Forma de Pago</th>
                            <th class="text-right">Total</th>
                            <th class="text-center">Fecha Vencimiento</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($compras as $compra)
                            <tr>
                                <td>{{ $compra->id ?? '-' }}</td>
                                <td>
                                    <span class="badge 
                                        @if($compra->status === 'Pagado') bg-success 
                                        @elseif($compra->status === 'Pendiente') bg-warning text-dark 
                                        @elseif($compra->status === 'No Pagar') bg-danger 
                                        @else bg-secondary @endif"
                                        @if($compra->status === 'Pagado' && $compra->updated_at)
                                            title="Pagado el {{ $compra->updated_at->format('d-m-Y') }}"
                                            data-toggle="tooltip"
                                        @endif>
                                        {{ $compra->status }}
                                    </span>
                                </td>
                                <td>{{ $compra->user->name ?? '-' }}</td>

                                <td>{{ $compra->proveedor->razon_social }}</td>
                                <td class="d-none d-md-table-cell">{{ $compra->proveedor->rut }}</td>
                                <td>{{ $compra->empresa->Nombre ?? '-' }}</td>

                                <td>{{ $compra->tipoPago->nombre ?? '-' }}</td>
                                <td>{{ $compra->numero_documento }}</td>
                                <td class="text-center text-muted text-nowrap">
                                    {{ optional($compra->fecha_documento)->format('Y-m-d') ?? '-' }}
                                </td>

                                <td>{{ $compra->centroCosto->nombre ?? '-' }}</td>
                                <td class="text-truncate" style="max-width:180px;">{{ $compra->glosa }}</td>
                                <td class="d-none d-md-table-cell text-truncate" style="max-width:180px;">{{ $compra->observacion }}</td>
                                <td class="d-none d-md-table-cell">{{ $compra->oc }}</td>

                                <td>{{ $compra->plazoPago->nombre ?? '-' }}</td>
                                <td class="d-none d-md-table-cell">{{ $compra->formaPago->nombre ?? '-' }}</td>
                                <td class="text-right">${{ number_format($compra->pago_total, 0, ',', '.') }}</td>
                                <td class="text-center text-muted text-nowrap">
                                    {{ optional($compra->fecha_vencimiento)->format('Y-m-d') ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="17" class="text-center text-muted">
                                    No hay compras registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>






                </table>
            </div>




            <div class="mt-3 d-flex justify-content-center">
                {{ $compras->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
</div>

@include('compras.modal_importar_excel')
@include('compras.modal_estructura_plantilla')
@include('compras.modal_exportar_compras')



@endsection
