@extends('layouts.app')
@php use Illuminate\Support\Str; @endphp

@php
    $importResult = (array) session('import_result');
@endphp

@section('content')
<div class="container">

    {{-- TÍTULO + MENSAJES --}}
    <h1 class="text-center mb-4" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);">Lista de Compras</h1>

    @if (session('import_result'))
        <div class="alert alert-info shadow-sm mb-4">
            <strong>📦 Importación finalizada</strong>
            <ul class="mb-0">
                <li>✅ Compras importadas: <strong>{{ session('import_result.importadas') }}</strong></li>
                <li>⚠️ Compras omitidas: <strong>{{ session('import_result.omitidas') }}</strong></li>
            </ul>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger shadow-sm mb-4">
            <strong>❌ Se encontraron errores en el formulario:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('import_result.importadas') == 0 && session('import_result.omitidas') > 0)
        <div class="alert alert-warning shadow-sm">
            ⚠️ Todas las filas del archivo fueron omitidas.
            @if (session('import_result.erroresDuplicados'))
                <details class="mt-2">
                    <summary>🔁 Compras duplicadas ({{ count(session('import_result.erroresDuplicados')) }})</summary>
                    <ul>
                        @foreach (session('import_result.erroresDuplicados') as $error)
                            <li>{!! $error !!}</li>
                        @endforeach
                    </ul>
                </details>
            @endif
            @if (session('import_result.erroresValidacion'))
                <details class="mt-3">
                    <summary>❌ Errores de validación ({{ count(session('import_result.erroresValidacion')) }})</summary>
                    <ul>
                        @foreach (session('import_result.erroresValidacion') as $error)
                            <li>{!! $error !!}</li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </div>
    @endif

    @if (session('import_result.importadas'))
        <div class="alert alert-success shadow-sm position-relative" role="alert">
            ✅ Compras importadas correctamente: <strong>{{ session('import_result.importadas') }}</strong>
            @if(session('import_result.detalles'))
                <button class="btn btn-sm btn-outline-dark position-absolute end-0 top-0 m-2" type="button" data-bs-toggle="collapse" data-bs-target="#importSuccessCollapse">
                    Ver detalles
                </button>
                <div class="collapse mt-3" id="importSuccessCollapse" style="max-height: 300px; overflow-y: auto; border-top: 1px solid #ccc; padding-top: 10px;">
                    <ul class="mb-0">
                        @foreach (session('import_result.detalles') as $detalle)
                            <li>{{ $detalle }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fa-regular fa-circle-check me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('proveedores_faltantes'))
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <a href="{{ route('compras.exportarProveedoresFaltantes') }}" class="btn btn-warning">
                📥 Descargar proveedores faltantes
            </a>
            <form action="{{ route('compras.limpiarProveedoresFaltantes') }}" method="POST">
                @csrf
                <button class="btn btn-outline-danger btn-sm" type="submit">
                    ❌ Limpiar lista de proveedores faltantes
                </button>
            </form>
        </div>
    @endif

    {{-- CONTENIDO EN DOS COLUMNAS --}}
    <div class="row">



        {{-- FILTROS --}}
        <div class="col-lg-2">

           


                <div class="card shadow-sm p-3 mb-4">
                    <h5 class="fw-bold mb-3">Gestión Masiva de Compras</h5>

                    {{-- Importar --}}
                    <form class="mb-2">
                        @csrf
                        <input type="file" name="archivo" id="archivoInputCompras" accept=".xlsx,.xls" style="display: none;">

                        <button type="button"
                            class="btn btn-outline-success btn-block py-2 text-center"
                            data-toggle="modal" data-target="#modalImportarExcelCompras">
                            <i class="fa fa-file-excel mr-1"></i> Importar Excel
                        </button>

                    </form>

                    {{-- Exportar --}}
                    <form action="{{ route('compras.exportar') }}" method="GET">
                        <button type="submit"
                            class="btn btn-outline-primary btn-block py-2 d-flex align-items-center justify-content-center">
                            <i class="fa-solid fa-file-excel me-1"></i> Exportar Excel
                        </button>
                    </form>
                </div>



                <div class="card shadow-sm p-3 mb-4">
                    <h5 class="fw-bold mb-3">🎯 Filtros de Búsqueda</h5>
                    <form method="GET" action="{{ route('compras.index') }}">
                        <div class="mb-3">
                            <label class="form-label">Razón Social</label>
                            <input type="text" name="search" class="form-control" placeholder="Ej: Acme Ltda." value="{{ request('search') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Año</label>
                            <select name="year" class="form-select">
                                <option value="">Todos</option>
                                @foreach ([2025, 2024, 2023] as $año)
                                    <option value="{{ $año }}" {{ request('year') == $año ? 'selected' : '' }}>{{ $año }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mes</label>
                            <select name="month" class="form-select">
                                <option value="">Todos</option>
                                @foreach (['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre'] as $mes)
                                    <option value="{{ $mes }}" {{ request('month') == $mes ? 'selected' : '' }}>{{ $mes }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Estado</label>
                            <select name="status" class="form-select">
                                <option value="">Todos</option>
                                @foreach (['Pendiente', 'Pagado', 'Abonado', 'No Pagar'] as $estado)
                                    <option value="{{ $estado }}" {{ request('status') == $estado ? 'selected' : '' }}>{{ $estado }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-grid gap-2 mt-3">
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                            <a href="{{ route('compras.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                        </div>
                    </form>
                </div>

    
        </div>

        {{-- ACCIONES + TABLA --}}
        <div class="col-lg-10">

            {{-- BOTONES PRINCIPALES --}}
            <div class="d-flex flex-wrap gap-3 mb-4">
                <button type="button" class="btn btn-outline-primary btn-sm"
                    data-toggle="modal" data-target="#modalImportarComprasInfo"
                    title="Ver estructura de la plantilla">
                    <i class="fa fa-info-circle mr-1"></i> Ver estructura y plantilla
                </button>


                <a href="{{ route('compras.create') }}" class="btn btn-primary ms-auto">
                    <i class="fa fa-plus me-1"></i> Agregar Compra Manual
                </a>

            </div>

            {{-- TABLA COMPRAS --}}
            <div class="table-responsive shadow-sm rounded">
                <table class="table table-hover align-middle table-striped">
                    <thead class="bg-secondary text-white">
                        <tr>
                            <th>#</th>
                            <th>Usuario</th>
                            <th>Centro de Costo</th>
                            <th>Glosa</th>
                            <th>Observación</th>
                            <th>Plazo Pago</th>
                            <th>Empresa</th>
                            <th>Año</th>
                            <th>Mes</th>
                            <th>Razón Social</th>
                            <th>RUT</th>
                            <th>Tipo Doc.</th>
                            <th>Fecha Doc.</th>
                            <th>N° Doc.</th>
                            <th>OC</th>
                            <th>Total</th>
                            <th>Vencimiento</th>
                            <th>Forma Pago</th>
                            <th>Archivo OC</th>
                            <th>Archivo Doc</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($compras as $compra)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $compra->user->name ?? '-' }}</td>
                                <td>{{ $compra->centroCosto->nombre ?? '-' }}</td>
                                <td>{{ $compra->glosa }}</td>
                                <td>{{ $compra->observacion }}</td>
                                <td>{{ $compra->plazoPago->nombre ?? '-' }}</td>
                                <td>{{ $compra->empresa->Nombre ?? '-' }}</td>
                                <td>{{ $compra->año }}</td>
                                <td>{{ $compra->mes }}</td>
                                <td>{{ $compra->proveedor->razon_social }}</td>
                                <td>{{ $compra->proveedor->rut }}</td>
                                <td>{{ $compra->tipoPago->nombre ?? '-' }}</td>
                                <td>{{ $compra->fecha_documento }}</td>
                                <td>{{ $compra->numero_documento }}</td>
                                <td>{{ $compra->oc }}</td>
                                <td>${{ number_format($compra->pago_total, 0, ',', '.') }}</td>
                                <td>{{ $compra->fecha_vencimiento }}</td>
                                <td>{{ $compra->formaPago->nombre ?? '-' }}</td>
                                <td>
                                    @if ($compra->archivo_oc)
                                        <a href="{{ Str::startsWith($compra->archivo_oc, ['http', 'https']) ? $compra->archivo_oc : route('compras.descargarArchivoOC', $compra->id) }}" target="_blank">Ver</a>
                                    @endif
                                </td>
                                <td>
                                    @if ($compra->archivo_documento)
                                        <a href="{{ Str::startsWith($compra->archivo_documento, ['http', 'https']) ? $compra->archivo_documento : route('compras.descargarArchivoDocumento', $compra->id) }}" target="_blank">Ver</a>
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('compras.updateStatus', $compra->id) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                            @foreach (['Pendiente', 'Pagado', 'Abonado', 'No Pagar'] as $estado)
                                                <option value="{{ $estado }}" {{ $compra->status === $estado ? 'selected' : '' }}>{{ $estado }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('compras.edit', $compra->id) }}" class="btn btn-warning btn-sm me-1">
                                        <i class="fa fa-pen"></i>
                                    </a>
                                    <form action="{{ route('compras.destroy', $compra->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar esta compra?');">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-danger btn-sm">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="22" class="text-center text-muted">No hay compras registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Paginación --}}
            <div class="mt-3 d-flex justify-content-center">
                {{ $compras->appends(request()->query())->links('pagination::bootstrap-4') }}
            </div>

        </div>
    </div> {{-- fin row --}}
</div> {{-- fin container --}}

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('toggleImportarBtn')?.addEventListener('click', () => {
            const panel = document.getElementById('importarPanel');
            if (panel) panel.classList.toggle('show');
        });
    });


        document.getElementById('formImportarCompras')?.addEventListener('submit', function () {
        document.getElementById('paso1').classList.add('d-none');
        document.getElementById('paso2').classList.remove('d-none');
    });




</script>


@include('compras.modal_importar_excel')
@include('compras.modal_estructura_plantilla')


@endsection


