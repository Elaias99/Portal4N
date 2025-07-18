@extends('layouts.app')
@php use Illuminate\Support\Str; @endphp

@php
    $importResult = (array) session('import_result');
@endphp

@section('content')
<div class="container">
    <h1 class="text-center mb-4" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);">Lista de Compras</h1>

    {{-- MENSAJES DE IMPORTACIÓN --}}
    @if (session('import_result'))
        <div class="alert alert-info shadow-sm mb-3">
            <strong>📦 Importación finalizada</strong>
            <ul class="mb-1">
                <li>✅ Compras importadas: <strong>{{ session('import_result.importadas') }}</strong></li>
                <li>⚠️ Compras omitidas: <strong>{{ session('import_result.omitidas') }}</strong></li>
            </ul>

            @if (count(session('import_result.detalles', [])))
                <details class="mt-2">
                    <summary>✅ Ver detalles de compras importadas ({{ count(session('import_result.detalles')) }})</summary>
                    <ul class="mt-2">
                        @foreach (session('import_result.detalles') as $detalle)
                            <li>{{ $detalle }}</li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </div>

        {{-- 🔁 COMPRAS DUPLICADAS --}}
        @if (count(session('import_result.erroresDuplicados', [])))
            <div class="alert alert-warning shadow-sm">
                🔁 Compras duplicadas detectadas.
                <details class="mt-2">
                    <summary>Ver duplicados ({{ count(session('import_result.erroresDuplicados')) }})</summary>
                    <ul class="mt-2">
                        @foreach (session('import_result.erroresDuplicados') as $e)
                            <li>{!! $e !!}</li>
                        @endforeach
                    </ul>
                </details>
            </div>
        @endif

        {{-- ❌ ERRORES DE VALIDACIÓN --}}
        @if (count(session('import_result.erroresValidacion', [])))
            <div class="alert alert-danger shadow-sm">
                ❌ Errores de validación encontrados.
                <details class="mt-2">
                    <summary>Ver errores de validación ({{ count(session('import_result.erroresValidacion')) }})</summary>
                    <ul class="mt-2">
                        @foreach (session('import_result.erroresValidacion') as $e)
                            <li>{!! $e !!}</li>
                        @endforeach
                    </ul>
                </details>
            </div>
        @endif

        {{-- ❗ CAMPOS INVÁLIDOS --}}
        @if (count(session('import_result.erroresCamposInvalidos', [])))
            <div class="alert alert-warning shadow-sm">
                ❗ Problemas con campos como fechas, montos o usuarios.
                <details class="mt-2">
                    <summary>Ver campos inválidos ({{ count(session('import_result.erroresCamposInvalidos')) }})</summary>
                    <ul class="mt-2">
                        @foreach (session('import_result.erroresCamposInvalidos') as $e)
                            <li>{!! $e !!}</li>
                        @endforeach
                    </ul>
                </details>
            </div>
        @endif
    @endif

    {{-- ⚠️ ERRORES DEL FORMULARIO (LARAVEL) --}}
    @if ($errors->any())
        <div class="alert alert-danger shadow-sm">
            <strong>❌ Se encontraron errores en el formulario:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif



    @if (session('proveedores_faltantes'))
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <a href="{{ route('compras.exportarProveedoresFaltantes') }}" class="btn btn-warning">
                📥 Descargar proveedores faltantes
            </a>
            <form action="{{ route('compras.limpiarProveedoresFaltantes') }}" method="POST">
                @csrf
                <button class="btn btn-outline-danger btn-sm">❌ Limpiar lista</button>
            </form>
        </div>
    @endif

    {{-- CONTENIDO --}}
    <div class="row">
        {{-- FILTROS Y GESTIÓN --}}
        <div class="col-lg-2 mb-4">

                {{-- Gestión Masiva --}}
            <div class="card shadow-sm p-3 mb-4">
                <h5 class="fw-bold mb-3">Gestión Masiva de Compras</h5>

                {{-- Importar --}}
                <form class="mb-2">
                    @csrf
                    <input type="file" name="archivo" id="archivoInput" accept=".xlsx,.xls" style="display: none;">
                    <button type="button" class="btn btn-outline-success btn-block py-2 d-flex align-items-center justify-content-center"
                        data-toggle="modal" data-target="#modalImportarExcelCompras">
                        <i class="fa-solid fa-file-excel me-1"></i> Importar Excel
                    </button>
                </form>

                {{-- Exportar --}}
                <form class="mb-2">
                    <button type="button" class="btn btn-outline-success btn-block py-2 d-flex align-items-center justify-content-center"
                        data-toggle="modal" data-target="#modalExportarCompras">
                        <i class="fa-solid fa-file-excel me-1"></i> Exportar Excel
                    </button>
                </form>
            </div>

            <div class="card shadow-sm p-3">
                    <form method="GET" action="{{ route('compras.index') }}">
                        <h5 class="fw-bold mb-3">Filtrar Compras</h5>

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

                        <div class="d-grid gap-2 mt-3">
                            <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                            <a href="{{ route('compras.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                        </div>
                    </form>
            </div>

        </div>

        {{-- TABLA PRINCIPAL --}}
        <div class="col-lg-9">
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

            {{-- TABLA --}}
            <div class="table-responsive shadow-sm rounded">
                <table class="table table-hover align-middle">
                    <thead class="bg-secondary text-white">
                        <tr>
                            <th>Usuario</th>
                            <th>Centro Costo</th>
                            <th>Glosa</th>
                            <th>Obs.</th>
                            <th>Plazo</th>
                            <th>Empresa</th>
                            <th>Año</th>
                            <th>Mes</th>
                            <th>Proveedor</th>
                            <th>RUT</th>
                            <th>Tipo Doc</th>
                            <th>Fecha</th>
                            <th>N°</th>
                            <th>OC</th>
                            <th>Total</th>
                            <th>Venc.</th>
                            <th>Forma</th>
                            <th>OC File</th>
                            <th>Doc File</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($compras as $compra)
                            <tr>
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
                                        <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
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

            {{-- PAGINACIÓN --}}
            <div class="mt-3 d-flex justify-content-center">
                {{ $compras->appends(request()->query())->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
</div>

@include('compras.modal_importar_excel')
@include('compras.modal_estructura_plantilla')

@include('compras.modal_exportar_compras')

@endsection
