@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="text-center" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);">Lista de Proveedores</h1>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <!-- Exportar Dropdown -->
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle shadow-sm" type="button" id="exportDropdown" data-toggle="dropdown" aria-expanded="false">
                <i class="fa-regular fa-file-excel me-2"></i> Exportar
            </button>
            <div class="dropdown-menu shadow-sm fade" aria-labelledby="exportDropdown">
                <a class="dropdown-item d-flex align-items-center" href="{{ route('proveedores.export') }}">
                    <i class="fa-solid fa-file-export me-2 text-success"></i> Exportar a Excel
                </a>
            </div>
        </div>

        <!-- Barra de búsqueda -->
        <div class="d-flex align-items-center">
            <input type="text" class="form-control shadow-sm me-2" placeholder="Buscar proveedor..." id="search">
            <button class="btn btn-outline-primary shadow-sm">
                <i class="fa-solid fa-search"></i>
            </button>
        </div>

        <!-- Botón Agregar -->
        <div class="d-flex align-items-center">
            <a href="{{ route('proveedores.create') }}" 
               class="btn btn-outline-primary shadow-sm" 
               data-bs-toggle="tooltip" 
               title="Agregar Proveedor">
                <i class="fa-solid fa-user-plus fa-lg"></i>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fa-regular fa-circle-check me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Tabla de Proveedores -->
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
                    <!-- Fila principal -->
                    <tr class="accordion-toggle" data-toggle="collapse" data-target="#details-{{ $proveedor->id }}" aria-expanded="false">
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $proveedor->razon_social }}</td>
                        <td>{{ $proveedor->rut }}</td>
                        <td>{{ $proveedor->telefono_empresa }}</td>
                        <td>{{ $proveedor->banco }}</td>
                        <td>{{ $proveedor->Nombre_RepresentanteLegal }}</td>
                        <td>{{ $proveedor->Telefono_RepresentanteLegal }}</td>
                        <td class="text-center">
                            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="tooltip" title="Ver Detalles">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <!-- Detalles del proveedor -->
                    <tr class="collapse" id="details-{{ $proveedor->id }}">
                        <td colspan="8" class="bg-light">
                            <div class="p-4 rounded shadow-sm border">
                                <div class="row">
                                    <!-- Columna 1: Direcciones -->
                                    <div class="col-md-6">
                                        <h4 class="mb-3">Direcciones</h4>
                                        <p><strong>Dirección Facturación:</strong> {{ $proveedor->direccion_facturacion }}</p>
                                        <p><strong>Dirección Despacho:</strong> {{ $proveedor->direccion_despacho }}</p>
                                        <p><strong>Comuna Empresa:</strong> {{ $proveedor->comuna_empresa }}</p>

                                        <!-- Datos Bancarios -->
                                        <h4 class="mt-4 mb-3">Datos Bancarios</h4>
                                        <p><strong>Banco:</strong> {{ $proveedor->banco }}</p>
                                        <p><strong>Tipo de Cuenta:</strong> {{ $proveedor->tipo_cuenta }}</p>
                                        <p><strong>Número de Cuenta:</strong> {{ $proveedor->nro_cuenta }}</p>
                                        <p><strong>Correo Bancario:</strong> {{ $proveedor->correo_banco }}</p>
                                        <p><strong>Razón Social Asociada a la Cuenta:</strong> {{ $proveedor->nombre_razon_social_banco }}</p>
                                        <p><strong>Método de Pago:</strong> {{ $proveedor->tipo_pago }}</p>




                                    </div>
                    
                                    <!-- Columna 2: Contactos y Bancarios -->
                                    <div class="col-md-6">
                                        <!-- Representante Legal -->
                                        <h4 class="mb-3">Representante Legal</h4>
                                        <p><strong>Correo Electrónico:</strong> {{ $proveedor->Correo_RepresentanteLegal }}</p>
                    
                                        <!-- Contactos Adicionales -->
                                        <h4 class="mt-4 mb-3">Contactos Adicionales</h4>
                                        <p><strong>Contacto 1:</strong></p>
                                        <ul>
                                            <li><strong>Nombre:</strong> {{ $proveedor->contacto_nombre }}</li>
                                            <li><strong>Teléfono:</strong> {{ $proveedor->contacto_telefono }}</li>
                                            <li><strong>Correo:</strong> {{ $proveedor->contacto_correo }}</li>
                                            <li><strong>Cargo:</strong> {{ $proveedor->cargo_contacto1 }}</li>
                                        </ul>
                                        <p><strong>Contacto 2:</strong></p>
                                        <ul>
                                            <li><strong>Nombre:</strong> {{ $proveedor->nombre_contacto2 }}</li>
                                            <li><strong>Teléfono:</strong> {{ $proveedor->telefono_contacto2 }}</li>
                                            <li><strong>Correo:</strong> {{ $proveedor->correo_contacto2 }}</li>
                                            <li><strong>Cargo:</strong> {{ $proveedor->cargo_contacto2 }}</li>
                                        </ul>
                    

                                    </div>
                                </div>
                    
                                <!-- Acciones -->
                                <div class="d-flex justify-content-end mt-3">
                                    <a href="{{ route('proveedores.edit', $proveedor->id) }}" class="btn btn-warning btn-sm me-2 shadow-sm" data-bs-toggle="tooltip" title="Editar">
                                        <i class="fa-regular fa-pen-to-square"></i> Editar
                                    </a>
                                    <form action="{{ route('proveedores.destroy', $proveedor->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este proveedor?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm shadow-sm" data-bs-toggle="tooltip" title="Eliminar">
                                            <i class="fa-solid fa-trash"></i> Eliminar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>



                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">No hay proveedores registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Activación de Tooltips -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });
</script>
@endsection
