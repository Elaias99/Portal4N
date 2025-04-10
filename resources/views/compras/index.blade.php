@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="text-center" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);">Lista de Compras</h1>

    <div class="mt-3">
        <a href="{{ route('compras.plantilla') }}" class="btn btn-outline-primary btn-sm">
            <i class="fa fa-download me-1"></i> Descargar Plantilla Excel
        </a>
    </div>
    
    @php
        \Illuminate\Support\Facades\Log::info('✅ Vista de compras cargada correctamente — botón de descarga visible');
    @endphp
    

    <!-- Botón Agregar -->
    @if (session('import_result'))
    <div class="alert alert-info shadow-sm">
        <strong>📦 Importación finalizada</strong>
        <ul>
            <li>✅ Compras importadas: <strong>{{ session('import_result.importadas') }}</strong></li>
            <li>⚠️ Compras omitidas: <strong>{{ session('import_result.omitidas') }}</strong></li>
        </ul>
        @if (count(session('import_result.errores')))
            <details>
                <summary>Ver errores ({{ count(session('import_result.errores')) }})</summary>
                <ul class="mt-2">
                    @foreach (session('import_result.errores') as $error)
                        <li>❌ {{ $error }}</li>
                    @endforeach
                </ul>
            </details>
        @endif
    </div>
    @endif

    {{-- ✅ ACCIONES PRINCIPALES --}}
    <div class="d-flex justify-content-between align-items-center mb-3">


        



        <div>
            <button id="toggleFiltrosBtn" class="btn btn-outline-secondary btn-sm me-2">
                <i class="fa fa-sliders-h mr-1"></i> Filtros
            </button>

            <button id="toggleImportarBtn" class="btn btn-outline-success btn-sm">
                <i class="fa fa-file-import mr-1"></i> Importar Excel
            </button>
        </div>

        <a href="{{ route('compras.create') }}" class="btn btn-primary shadow-sm">
            <i class="fa fa-plus mr-1"></i> Agregar Compra Manual
        </a>
    </div>

    


    
        {{-- Botón toggle de filtros --}}
        {{-- ✅ PANEL: FILTROS --}}
        <div class="collapse show mb-3" id="filtrosPanel">
            <div class="card card-body shadow-sm">
                <form method="GET" action="{{ route('compras.index') }}">
                    <div class="row g-3">
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
                                @foreach (['Enero','Febrero','Marzo','Abril'] as $mes)
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
                                @foreach (['Pendiente','Pagado','Abonado','No Pagar'] as $estado)
                                    <option value="{{ $estado }}" {{ request('status') == $estado ? 'selected' : '' }}>{{ $estado }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <a href="{{ route('compras.index') }}" class="btn btn-secondary">Limpiar Filtros</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- ✅ PANEL: IMPORTACIÓN --}}
        <div class="collapse mb-4" id="importarPanel">
            <div class="card card-body shadow-sm border-left-success">
                <form action="{{ route('compras.importar') }}" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
                    @csrf
                    <input type="file" name="archivo_excel" class="form-control form-control-sm" required>
                    <button type="submit" class="btn btn-success btn-sm shadow-sm">
                        <i class="fa-solid fa-file-import me-1"></i> Importar
                    </button>
                </form>
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
                                <a href="{{ route('compras.descargarArchivoOC', $compra->id) }}" target="_blank">Ver O.C</a>
                            @endif
                        </td>
                        <td>
                            @if($compra->archivo_documento)
                                <a href="{{ route('compras.descargarArchivoDocumento', $compra->id) }}" target="_blank">Ver Documento</a>
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