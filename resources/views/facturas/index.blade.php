@extends('layouts.app')

@section('content')


<div class="container">
    <h1 class="text-center mb-4">
        <i class="bi bi-file-earmark-text"></i> Lista de Facturas
    </h1>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <!-- Botón para agregar una nueva factura -->
        <a href="{{ route('facturas.create') }}" class="btn btn-primary btn-lg shadow">
            <i class="bi bi-file-earmark-plus me-2"></i> Agregar Nueva Factura
        </a>

        <!-- Filtro y buscador -->
        <form method="GET" action="{{ route('facturas.index') }}" class="d-flex align-items-center">
            <input type="text" name="search" class="form-control me-2" placeholder="Buscar factura o proveedor..." value="{{ request('search') }}">
            <select name="status" class="form-select me-2">
                <option value="">Todos los estados</option>
                <option value="Pendiente" {{ request('status') == 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                <option value="Pagado" {{ request('status') == 'Pagado' ? 'selected' : '' }}>Pagado</option>
                <option value="Abonado" {{ request('status') == 'Abonado' ? 'selected' : '' }}>Abonado</option>
                <option value="No Pagar" {{ request('status') == 'No Pagar' ? 'selected' : '' }}>No Pagar</option>
            </select>

            <button type="submit" class="btn btn-outline-primary">
                <i class="bi bi-search"></i> Buscar
            </button>

            <br>
        
            <!-- Dropdown para exportar -->
            <div class="dropdown me-2">
                <button class="btn btn-outline-dark dropdown-toggle d-flex align-items-center" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                     Exportar
                </button>
                <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="{{ route('facturas.export') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="16" height="16" viewBox="0 0 48 48" class="me-2">
                                <path fill="#4CAF50" d="M41,10H25v28h16c0.553,0,1-0.447,1-1V11C42,10.447,41.553,10,41,10z"></path>
                                <path fill="#FFF" d="M32 15H39V18H32zM32 25H39V28H32zM32 30H39V33H32zM32 20H39V23H32zM25 15H30V18H25zM25 25H30V28H25zM25 30H30V33H25zM25 20H30V23H25z"></path>
                                <path fill="#2E7D32" d="M27 42L6 38 6 10 27 6z"></path>
                                <path fill="#FFF" d="M19.129,31l-2.411-4.561c-0.092-0.171-0.186-0.483-0.284-0.938h-0.037c-0.046,0.215-0.154,0.541-0.324,0.979L13.652,31H9.895l4.462-7.001L10.274,17h3.837l2.001,4.196c0.156,0.331,0.296,0.725,0.42,1.179h0.04c0.078-0.271,0.224-0.68,0.439-1.22L19.237,17h3.515l-4.199,6.939l4.316,7.059h-3.74V31z"></path>
                            </svg> Exportar a Excel
                        </a>
                    </li>
                </ul>
            </div>
        
            
        </form>
        
        

    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow" role="alert">
            <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Tabla de facturas -->
    <div class="table-responsive rounded shadow">
        <table class="table table-hover align-middle" id="facturasTable">
            <thead class="bg-primary text-white">
                <tr>
                    <th>#</th>
                    <th>RUT <i class="bi bi-person-badge"></i></th>
                    <th>Razón Social <i class="bi bi-building"></i></th>
                    <th>Banco <i class="bi bi-bank"></i></th>
                    <th>Empresa <i class="bi bi-briefcase"></i></th>
                    <th>Tipo de Documento <i class="bi bi-file-text"></i></th>
                    <th>Tipo de Pago <i class="bi bi-cash-stack"></i></th>
                    <th>Centro de Costo <i class="bi bi-geo-alt"></i></th>
                    <th>Glosa <i class="bi bi-card-text"></i></th>
                    <th>Comentario <i class="bi bi-card-text"></i></th>
                    <th>Fecha Emisión <i class="bi bi-calendar"></i></th>
                    <th>Fecha de Pago <i class="bi bi-calendar-check"></i></th>
                    <th>Status <i class="bi bi-info-circle"></i></th>
                    <th class="text-center">Opciones <i class="bi bi-tools"></i></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($facturas as $factura)
                    <tr class="hover-row">
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $factura->proveedor->rut }}</td>
                        <td>{{ $factura->proveedor->razon_social }}</td>
                        <td>{{ $factura->proveedor->banco }}</td>
                        <td>{{ $factura->empresa->Nombre }}</td>

                        <td>{{ $factura->tipo_documento }}</td>
                        <td>{{ $factura->proveedor->tipo_pago }}</td>
                        <td>{{ $factura->centro_costo }}</td>
                        <td>{{ $factura->glosa }}</td>
                        <td>{{ $factura->comentario }}</td>
                        <td>{{ $factura->created_at->format('d/m/Y') }}</td>
                        <td>{{ $factura->fecha_pago ? $factura->fecha_pago->format('d/m/Y') : 'Indefinido' }}</td>
                        <td>
                            <form action="{{ route('facturas.update-status', $factura->id) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('PUT')
                                <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                                    <option value="Pendiente" {{ $factura->status == 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                                    <option value="Pagado" {{ $factura->status == 'Pagado' ? 'selected' : '' }}>Pagado</option>
                                    <option value="Abonado" {{ $factura->status == 'Abonado' ? 'selected' : '' }}>Abonado</option>
                                    <option value="No Pagar" {{ $factura->status == 'No Pagar' ? 'selected' : '' }}>No Pagar</option>
                                </select>
                            </form>
                        </td>
                        <td class="text-center">
                            <form action="{{ route('facturas.destroy', $factura->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro de eliminar esta factura?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger btn-hover">
                                    <i class="bi bi-trash"></i> Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="text-center text-muted">
                            <i class="bi bi-folder-x"></i> No hay facturas registradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        
    </div>
    
    
    <a href="{{ route('proveedores.index') }}" class="btn btn-secondary">Regresar a Proveedores</a>
    

</div>

<!-- Animation Styles -->
<style>
    .hover-row:hover {
        background-color: #f8f9fa !important;
        transition: background-color 0.3s ease;
    }
    .btn-hover:hover {
        transform: scale(1.05);
        transition: transform 0.2s ease-in-out;
    }
</style>
@endsection


