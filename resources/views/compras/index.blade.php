@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="text-center" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);">Lista de Compras</h1>

    <!-- Botón Agregar -->
    <div class="d-flex align-items-center mb-3">
        <a href="{{ route('compras.create') }}" 
           class="btn btn-outline-primary shadow-sm" 
           data-bs-toggle="tooltip" 
           title="Agregar Compra">
            <i class="fa-solid fa-plus fa-lg"></i>
        </a>
    </div>

    <!-- Filtros -->
    <form method="GET" action="{{ route('compras.index') }}" class="mb-4">
        <div class="row g-3">
            <!-- Filtro Año -->
            <div class="col-md-3">
                <label for="year" class="form-label">Año</label>
                <select name="year" id="year" class="form-select">
                    <option value="">Todos</option>
                    <option value="2025" {{ request('year') == '2025' ? 'selected' : '' }}>2025</option>
                    <option value="2024" {{ request('year') == '2024' ? 'selected' : '' }}>2024</option>
                    <option value="2023" {{ request('year') == '2023' ? 'selected' : '' }}>2023</option>
                </select>
            </div>

            <!-- Filtro Mes -->
            <div class="col-md-3">
                <label for="month" class="form-label">Mes</label>
                <select name="month" id="month" class="form-select">
                    <option value="">Todos</option>
                    <option value="Enero" {{ request('month') == 'Enero' ? 'selected' : '' }}>Enero</option>
                    <option value="Febrero" {{ request('month') == 'Febrero' ? 'selected' : '' }}>Febrero</option>
                    <option value="Marzo" {{ request('month') == 'Marzo' ? 'selected' : '' }}>Marzo</option>
                    <option value="Abril" {{ request('month') == 'Abril' ? 'selected' : '' }}>Abril</option>
                </select>
            </div>

            <!-- Filtro Proveedor -->
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

            <!-- Filtro Estado -->
            <div class="col-md-3">
                <label for="status" class="form-label">Estado</label>
                <select name="status" id="status" class="form-select">
                    <option value="">Todos</option>
                    <option value="Pendiente" {{ request('status') == 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                    <option value="Pagado" {{ request('status') == 'Pagado' ? 'selected' : '' }}>Pagado</option>
                    <option value="Abonado" {{ request('status') == 'Abonado' ? 'selected' : '' }}>Abonado</option>
                    <option value="No Pagar" {{ request('status') == 'No Pagar' ? 'selected' : '' }}>No Pagar</option>
                </select>
            </div>
        </div>

        <!-- Botones -->
        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="{{ route('compras.index') }}" class="btn btn-secondary">Limpiar Filtros</a>
        </div>
    </form>

    <!-- Mensaje de éxito -->
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
                    <th>Tipo Pago</th>
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
                        <td>{{ $compra->centro_costo }}</td>
                        <td>{{ $compra->glosa }}</td>
                        <td>{{ $compra->observacion }}</td>
                        <td>{{ $compra->tipo_pago }}</td>
                        <td>{{ $compra->empresa->Nombre }}</td>
                        <td>{{ $compra->año }}</td>
                        <td>{{ $compra->mes }}</td>
                        <td>{{ $compra->proveedor->razon_social }}</td>
                        <td>{{ $compra->proveedor->rut }}</td>
                        <td>{{ $compra->tipo_documento }}</td>
                        <td>{{ $compra->fecha_documento }}</td>
                        <td>{{ $compra->numero_documento }}</td>
                        <td>{{ $compra->oc }}</td>
                        <td>{{ number_format($compra->pago_total, 2) }}</td>
                        <td>{{ $compra->fecha_vencimiento }}</td>
                        <td>{{ $compra->forma_pago }}</td>
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
