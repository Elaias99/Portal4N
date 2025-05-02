@extends('layouts.app')
@php use Illuminate\Support\Str; @endphp

@php
    $importResult = (array) session('import_result');
@endphp

@section('content')
<div class="container">
    {{-- ✅ TÍTULO PRINCIPAL --}}
    <h1 class="text-center mb-5" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);">Lista de Compras</h1>

    {{-- ✅ MENSAJES DEL SISTEMA --}}
    @if (session('import_result'))
        <div class="alert alert-info shadow-sm mb-4">
            <strong>📦 Importación finalizada</strong>
            <ul class="mb-0">
                <li>✅ Compras importadas: <strong>{{ session('import_result.importadas') }}</strong></li>
                <li>⚠️ Compras omitidas: <strong>{{ session('import_result.omitidas') }}</strong></li>
            </ul>
        </div>
    @endif

    {{-- ✅ ERRORES DE VALIDACIÓN --}}
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

    {{-- ERRORES DE IMPORTACIÓN --}}
    @if ($importResult && isset($importResult['errores']) && count($importResult['errores']) > 0)

        <div class="alert alert-warning shadow-sm mt-2 position-relative" role="alert">
            <strong>⚠️ Se encontraron errores al importar el archivo:</strong>

            <button class="btn btn-sm btn-outline-dark position-absolute end-0 top-0 m-2" type="button" data-bs-toggle="collapse" data-bs-target="#errorListCollapse" aria-expanded="false">
                Ver detalles
            </button>

            <div class="collapse mt-3" id="errorListCollapse" style="max-height: 300px; overflow-y: auto; border-top: 1px solid #ccc; padding-top: 10px;">
                <ul class="mb-0">
                    @foreach ($importResult['errores'] as $error)
                        <li>{!! $error !!}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif


    {{-- MENSAJE DE IMPORTADAS CORRECTAMENTE --}}
    @if (session('import_result.importadas'))
        <div class="alert alert-success shadow-sm position-relative" role="alert">
            ✅ Compras importadas correctamente: <strong>{{ session('import_result.importadas') }}</strong>

            {{-- Botón para mostrar detalles --}}
            @if(session('import_result.detalles') && count(session('import_result.detalles')) > 0)
                <button class="btn btn-sm btn-outline-dark position-absolute end-0 top-0 m-2" type="button" data-bs-toggle="collapse" data-bs-target="#importSuccessCollapse" aria-expanded="false">
                    Ver detalles
                </button>

                <div class="collapse mt-3" id="importSuccessCollapse" style="max-height: 300px; overflow-y: auto; border-top: 1px solid #ccc; padding-top: 10px;">
                    <ul class="mb-0">
                        @foreach (session('import_result.detalles', []) as $detalle)
                            <li>{{ $detalle }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif


    {{-- BOTÓN PARA DESCARGAR PROVEEDORES FALTANTES --}}
    @if (session('proveedores_faltantes') && count(session('proveedores_faltantes')) > 0)
        <a href="{{ route('compras.exportarProveedoresFaltantes') }}" class="btn btn-warning mb-2">
            📥 Descargar proveedores faltantes
        </a>

        <form action="{{ route('compras.limpiarProveedoresFaltantes') }}" method="POST" style="display:inline;">
            @csrf
            <button class="btn btn-outline-danger btn-sm" type="submit">
                ❌ Limpiar lista de proveedores faltantes
            </button>
        </form>
    @endif




    {{-- ✅ ACCIONES PRINCIPALES --}}
    <div class="d-flex flex-wrap gap-3 mb-4">
        <a href="{{ route('compras.plantilla') }}" class="btn btn-outline-primary">
            <i class="fa fa-download me-1"></i> Descargar Plantilla Excel
        </a>

        <button id="toggleFiltrosBtn" class="btn btn-outline-secondary">
            <i class="fa fa-sliders-h me-1"></i> Filtros
        </button>

        <button id="toggleImportarBtn" class="btn btn-outline-success">
            <i class="fa fa-file-import me-1"></i> Importar Excel
        </button>

        <a href="{{ route('compras.create') }}" class="btn btn-primary ms-auto">
            <i class="fa fa-plus me-1"></i> Agregar Compra Manual
        </a>
    </div>

            {{-- ✅ PANEL: FILTROS --}}
    <div class="collapse show mb-4" id="filtrosPanel">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-4">🎯 Filtros de Búsqueda Avanzada</h5>

                <form method="GET" action="{{ route('compras.index') }}">
                    <div class="row g-4">
                        <div class="col-md-3">
                            <label for="year" class="form-label">Año</label>
                            <select name="year" id="year" class="form-select">
                                <option value="">Todos</option>
                                @foreach ([2025, 2024, 2023] as $año)
                                    <option value="{{ $año }}" {{ request('year') == $año ? 'selected' : '' }}>{{ $año }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="month" class="form-label">Mes</label>
                            <select name="month" id="month" class="form-select">
                                <option value="">Todos</option>
                                @foreach (['Enero', 'Febrero', 'Marzo', 'Abril'] as $mes)
                                    <option value="{{ $mes }}" {{ request('month') == $mes ? 'selected' : '' }}>{{ $mes }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="provider" class="form-label">Proveedor</label>
                            <select name="provider" id="provider" class="form-select">
                                <option value="">Todos</option>
                                @foreach ($proveedores as $proveedor)
                                    <option value="{{ $proveedor->razon_social }}" {{ request('provider') == $proveedor->razon_social ? 'selected' : '' }}>
                                        {{ $proveedor->razon_social }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="status" class="form-label">Estado</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">Todos</option>
                                @foreach (['Pendiente', 'Pagado', 'Abonado', 'No Pagar'] as $estado)
                                    <option value="{{ $estado }}" {{ request('status') == $estado ? 'selected' : '' }}>{{ $estado }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-3">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <a href="{{ route('compras.index') }}" class="btn btn-secondary">Limpiar Filtros</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

        {{-- ✅ PANEL: IMPORTACIÓN --}}
        <div class="collapse mb-5" id="importarPanel">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">⬆ Importar Compras desde Excel</h5>
    
                    <form action="{{ route('compras.importar') }}" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-3">
                        @csrf
                        <input type="file" name="archivo_excel" class="form-control form-control-sm" required>
                        <button type="submit" class="btn btn-success">
                            <i class="fa-solid fa-file-import me-1"></i> Importar
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ✅ ALERTA ÉXITO --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fa-regular fa-circle-check me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    
    <!-- Tabla Scrollable -->
    <div class="table-responsive shadow-sm rounded" style="overflow-x: auto;">
        <table class="table table-hover align-middle table-striped">
            <thead class="bg-secondary text-white">
                <tr>
                    <th>#</th>
                    <th>Usuario</th>
                    <th>Centro de Costo</th>
                    <th>Glosa</th>
                    <th>Observacion</th>

                    <th>Plazo de Pago</th>


                    <th>Empresa Facturadora</th>
                    <th>Año</th>
                    <th>Mes de servicio</th>
                    <th>Razón Social</th>
                    <th>Rut Razón Social</th>

                    <th>Tipo de Documento</th>


                    <th>Fecha del Documento</th>
                    <th>Número del Documento</th>
                    <th>Orden de Compra (O.C)</th>
                    <th>Pago Total</th>
                    <th>Fecha Vencimiento</th>
                    <th>Forma de Pago</th>
                    <th>Archivo O.C</th>
                    <th>Archivo Documento</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($compras as $compra)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $compra->user->name ?? 'No especificado' }}</td>
                        <td>{{ $compra->centroCosto?->nombre ?? 'No asignado' }}</td>
                        <td>{{ $compra->glosa }}</td>
                        <td>{{ $compra->observacion }}</td>

                        <td>{{ $compra->plazoPago->nombre ?? 'No especificado' }}</td>


                        <td>{{ $compra->empresa->Nombre }}</td>
                        <td>{{ $compra->año }}</td>
                        <td>{{ $compra->mes }}</td>
                        <td>{{ $compra->proveedor->razon_social }}</td>
                        <td>{{ $compra->proveedor->rut }}</td>

                        
                        <td>{{ $compra->tipoPago->nombre ?? 'No especificado' }}</td>

                        
                        
                        
                        <td>{{ $compra->fecha_documento }}</td>
                        <td>{{ $compra->numero_documento }}</td>
                        <td>{{ $compra->oc }}</td>


                        <td>${{ number_format($compra->pago_total, 0, ',', '.') }}</td>



                        <td>{{ $compra->fecha_vencimiento }}</td>

                        <td>{{ $compra->formaPago->nombre ?? 'No especificado' }}</td>



                        

                        <td>
                            @if($compra->archivo_oc)
                                @if(Str::startsWith($compra->archivo_oc, ['http://', 'https://']))
                                    <a href="{{ $compra->archivo_oc }}" target="_blank">Ver O.C</a>
                                @else
                                    <a href="{{ route('compras.descargarArchivoOC', $compra->id) }}" target="_blank">Ver O.C</a>
                                @endif
                            @endif
                        </td>

                        <td>
                            @if($compra->archivo_documento)
                                @if(Str::startsWith($compra->archivo_documento, ['http://', 'https://']))
                                    <a href="{{ $compra->archivo_documento }}" target="_blank">Ver Documento</a>
                                @else
                                    <a href="{{ route('compras.descargarArchivoDocumento', $compra->id) }}" target="_blank">Ver Documento</a>
                                @endif
                            @endif
                        </td>



                        <td>
                            <form action="{{ route('compras.updateStatus', $compra->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                        
                                <select name="status" class="form-select" style="min-width: 120px;" onchange="this.form.submit()">
                                    <option value="Pendiente" {{ $compra->status == 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                                    <option value="Pagado" {{ $compra->status == 'Pagado' ? 'selected' : '' }}>Pagado</option>
                                    <option value="Abonado" {{ $compra->status == 'Abonado' ? 'selected' : '' }}>Abonado</option>
                                    <option value="No Pagar" {{ $compra->status == 'No Pagar' ? 'selected' : '' }}>No Pagar</option>
                                </select>
                            </form>
                        </td>
                        
                        
                        
                        
                        


                        <td class="text-center">
                            <a href="{{ route('compras.edit', $compra->id) }}" class="btn btn-warning btn-sm me-2 shadow-sm" data-bs-toggle="tooltip" title="Editar">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('compras.destroy', $compra->id) }}" method="POST" class="d-inline"
                                onsubmit="return confirm('¿Está seguro de que desea eliminar esta compra? Esta acción no se puede deshacer.');">
                              @csrf
                              @method('DELETE')
                              <button type="submit" class="btn btn-danger btn-sm shadow-sm" data-bs-toggle="tooltip" title="Eliminar">
                                  <i class="fa-solid fa-trash"></i>
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
    <div class="mt-3 d-flex justify-content-center">
        {{ $compras->appends(request()->query())->links('pagination::bootstrap-4') }}
    </div>







</div>
@endsection
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
        $('#toggleFiltrosBtn').on('click', function () {
            $('#filtrosPanel').slideToggle();
        });

        $('#toggleImportarBtn').on('click', function () {
            $('#importarPanel').slideToggle();
        });
    });
</script>