@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="text-center mb-4" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);">Lista de Proveedores</h1>

    <div class="row">
        {{-- Filtros a la izquierda --}}
        <div class="col-lg-2 mb-4">
            <div class="card shadow-sm p-3">
                <h5 class="fw-bold mb-3">Filtrar Proveedores</h5>

                <form method="GET" action="{{ route('proveedores.index') }}">
                    <div class="mb-3">
                        <label class="form-label">Razón Social:</label>
                        <input type="text" name="razon_social" class="form-control" placeholder="Ej: Acme Ltda." value="{{ request('razon_social') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">RUT:</label>
                        <input type="text" name="rut" class="form-control" placeholder="Ej: 12345678-9" value="{{ request('rut') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Banco:</label>
                        <select name="banco" class="form-select form-select-sm">
                            <option value="">- Seleccionar Banco -</option>
                            @foreach($bancos as $banco)
                                <option value="{{ $banco->id }}" {{ request('banco') == $banco->id ? 'selected' : '' }}>
                                    {{ $banco->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Comuna:</label>
                        <select name="comuna" class="form-select form-select-sm">
                            <option value="">- Seleccionar Comuna -</option>
                            @foreach($comunas as $comuna)
                                <option value="{{ $comuna->id }}" {{ request('comuna') == $comuna->id ? 'selected' : '' }}>
                                    {{ $comuna->Nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="d-grid gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                        <a href="{{ route('proveedores.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Contenido principal: acciones + tabla --}}
        <div class="col-lg-9">
            {{-- Acciones principales --}}
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('proveedores.plantilla') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fa fa-download me-1"></i> Descargar Plantilla de Proveedores
                    </a>

                    

                    <button id="toggleImportarBtn" class="btn btn-outline-success btn-sm">
                        <i class="fa fa-file-import mr-1"></i> Importar Excel
                    </button>
                    
                    <form id="importForm" action="{{ route('importar.proveedores') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="archivo" id="archivoInput" accept=".xlsx,.xls" style="display: none;">
                    </form>
                    
                </div>

                <a href="{{ route('proveedores.create') }}" class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="tooltip" title="Agregar Proveedor">
                    <i class="fa-solid fa-user-plus me-1"></i> Agregar
                </a>
            </div>

            {{-- Buscador --}}
            <div class="mb-3">
                <form method="GET" action="{{ route('proveedores.index') }}" class="d-flex align-items-center gap-2">
                    <input 
                        type="text" 
                        name="search" 
                        id="search" 
                        value="{{ request('search') }}"
                        class="form-control shadow-sm" 
                        placeholder="Buscar proveedor...">
                    <button type="submit" class="btn btn-outline-primary shadow-sm">
                        <i class="fa-solid fa-search"></i>
                    </button>
                </form>
            </div>

            {{-- Alerta de éxito --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fa-regular fa-circle-check me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- Tabla de proveedores --}}
            <div class="table-responsive shadow-sm rounded">
                <table class="table table-hover align-middle">
                    <thead class="bg-secondary text-white">
                        <tr>
                            <th>#</th>
                            <th>Razón Social</th>
                            <th>RUT Razón Social</th>
                            <th>Teléfono Empresa</th>
                            <th>Banco</th>
                            <th>Representante Legal</th>
                            <th>Teléfono Representante</th>
                            <th class="text-center"></th>
                            
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($proveedores as $proveedor)
                            <tr class="accordion-toggle" data-toggle="collapse" data-target="#details-{{ $proveedor->id }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $proveedor->razon_social }}</td>
                                <td>{{ $proveedor->rut }}</td>
                                <td>{{ $proveedor->telefono_empresa }}</td>
                                <td>{{ $proveedor->banco->nombre ?? 'Sin banco' }}</td>
                                <td>{{ $proveedor->Nombre_RepresentanteLegal }}</td>
                                <td>{{ $proveedor->Telefono_RepresentanteLegal }}</td>
                                <td class="text-center">
                                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="tooltip" title="Ver Detalles">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </td>
                                {{-- <td>
                                    <a href="{{ route('proveedores.edit', $proveedor->id) }}" class="btn btn-warning btn-sm">Editar</a>
                                    <form action="{{ route('proveedores.destroy', $proveedor->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de que deseas eliminar este proveedor?')">Eliminar</button>
                                    </form>
                                </td> --}}
                            </tr>

                            {{-- Fila de detalles expandible --}}
                            <tr class="collapse" id="details-{{ $proveedor->id }}">
                                <td colspan="9" class="bg-light">
                                    <div class="p-4 rounded shadow-sm border">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h4 class="mb-3">Direcciones</h4>
                                                <p><strong>Dirección Facturación:</strong> {{ $proveedor->direccion_facturacion }}</p>
                                                <p><strong>Dirección Despacho:</strong> {{ $proveedor->direccion_despacho }}</p>
                                                <p><strong>Comuna Empresa:</strong> {{ $proveedor->comuna->Nombre ?? 'No asignada' }}</p>

                                                <h4 class="mt-4 mb-3">Datos Bancarios</h4>
                                                <p><strong>Banco:</strong> {{ $proveedor->banco->nombre ?? 'Sin banco' }}</p>
                                                <p><strong>Tipo de Cuenta:</strong> {{ $proveedor->tipoCuenta->nombre ?? 'Sin Tipo de Cuenta' }}</p>
                                                <p><strong>Número de Cuenta:</strong> {{ $proveedor->nro_cuenta }}</p>
                                                <p><strong>Correo Bancario:</strong> {{ $proveedor->correo_banco }}</p>
                                                <p><strong>Razón Social Asociada a la Cuenta:</strong> {{ $proveedor->nombre_razon_social_banco }}</p>
                                                <p><strong>Método de Pago:</strong> {{ $proveedor->tipoPago->nombre ?? 'Sin asignar' }}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <h4 class="mb-3">Representante Legal</h4>
                                                <p><strong>Correo Electrónico:</strong> {{ $proveedor->Correo_RepresentanteLegal }}</p>

                                                <h4 class="mt-4 mb-3">Contactos Adicionales</h4>
                                                <ul>
                                                    <li><strong>Nombre:</strong> {{ $proveedor->contacto_nombre }}</li>
                                                    <li><strong>Teléfono:</strong> {{ $proveedor->contacto_telefono }}</li>
                                                    <li><strong>Correo:</strong> {{ $proveedor->contacto_correo }}</li>
                                                    <li><strong>Cargo:</strong> {{ $proveedor->cargo_contacto1 }}</li>
                                                </ul>
                                                <ul>
                                                    <li><strong>Nombre:</strong> {{ $proveedor->nombre_contacto2 }}</li>
                                                    <li><strong>Teléfono:</strong> {{ $proveedor->telefono_contacto2 }}</li>
                                                    <li><strong>Correo:</strong> {{ $proveedor->correo_contacto2 }}</li>
                                                    <li><strong>Cargo:</strong> {{ $proveedor->cargo_contacto2 }}</li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-end gap-2 mt-3">
                                            {{-- Botón Editar --}}
                                            <a href="{{ route('proveedores.edit', $proveedor->id) }}" 
                                               class="btn btn-outline-warning btn-sm shadow-sm d-flex align-items-center gap-1"
                                               data-bs-toggle="tooltip" title="Editar proveedor">
                                                <i class="fa-regular fa-pen-to-square"></i> 
                                            </a>
                                        
                                            {{-- Botón Eliminar --}}
                                            <form action="{{ route('proveedores.destroy', $proveedor->id) }}" method="POST"
                                                  onsubmit="return confirm('¿Estás seguro de eliminar este proveedor?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="btn btn-outline-danger btn-sm shadow-sm d-flex align-items-center gap-1"
                                                        data-bs-toggle="tooltip" title="Eliminar proveedor">
                                                    <i class="fa-solid fa-trash"></i> 
                                                </button>
                                            </form>
                                        </div>
                                        


                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">No hay proveedores registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Tooltips --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });
</script>

<script>
    document.getElementById('toggleImportarBtn').addEventListener('click', function () {
        document.getElementById('archivoInput').click();
    });

    document.getElementById('archivoInput').addEventListener('change', function () {
        document.getElementById('importForm').submit();
    });
</script>

@endsection
