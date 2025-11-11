@extends('layouts.app')

@section('content')
<div class="container py-4">

    {{-- Mensajes --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="text-center mb-4">
        <h2 class="fw-bold mb-2">Gestión de Cobranzas de Compras</h2>
        <p class="text-muted mb-3">Visualiza y administra la información de las cobranzas asociadas a documentos de compras.</p>

        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="{{ route('finanzas_compras.index') }}" class="btn btn-outline-secondary px-4">
                Volver a Compras
            </a>

            <a href="{{ route('cobranzas-compras.create') }}" class="btn btn-primary px-4">
                Nueva Cobranza
            </a>


        </div>
    </div>

    {{-- Búsqueda --}}
    <form method="GET" action="{{ route('cobranzas-compras.index') }}" class="mb-4 d-flex justify-content-center">
        <div class="search-box d-flex align-items-center shadow-sm">
            <input 
                type="text" 
                name="buscar" 
                class="form-control border-0 ps-3" 
                placeholder="Buscar por razón social o RUT..."
                value="{{ request('buscar') }}"
            >
            <button type="submit" class="btn-search">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </div>
    </form>

    {{-- Tabla --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-semibold mb-0">Listado de Cobranzas de Compras</h5>
                <span class="text-muted small">Total: {{ $cobranzasCompras->count() }} registros</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start">Rut Cliente</th>
                            <th class="text-start">Razón Social</th>
                            <th class="text-center">Servicio</th>
                            <th class="text-center">Créditos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>



                <tbody>
                    @forelse ($cobranzasCompras as $cobranzaCompra)
                        <tr class="table-row-hover">
                            <td class="text-start">{{ $cobranzaCompra->rut_cliente }}</td>
                            <td class="text-start">{{ $cobranzaCompra->razon_social }}</td>
                            <td class="text-center">{{ $cobranzaCompra->servicio }}</td>
                            <td class="text-center">{{ $cobranzaCompra->creditos }}</td>
                            <td>
                                {{-- ✅ Enlace de edición con binding --}}
                                <a href="{{ route('cobranzas-compras.edit', $cobranzaCompra) }}" class="btn btn-sm btn-warning">
                                    Editar
                                </a>

                                {{-- ✅ Formulario de eliminación con binding --}}
                                <form action="{{ route('cobranzas-compras.destroy', $cobranzaCompra) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger"
                                        onclick="return confirm('¿Seguro que deseas eliminar esta cobranza de compras?')">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No hay cobranzas de compras registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>






                </table>
            </div>
        </div>

        {{-- Paginación --}}
        @if ($cobranzasCompras->hasPages())
            <div class="mt-4 d-flex justify-content-center">
                {{ $cobranzasCompras->appends(request()->query())->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </div>
</div>

{{-- Estilos personalizados --}}
<style>
    .table-row-hover:hover {
        background-color: #f7f9fc;
        transition: background-color 0.3s ease;
    }

    .table thead th {
        font-weight: 600;
        color: #333;
    }

    .card {
        border-radius: 0.75rem !important;
    }

    .btn {
        font-weight: 500;
        border-radius: 0.5rem;
    }

    .btn-primary {
        background-color: #007bff;
        border: none;
    }

    .btn-primary:hover {
        background-color: #0069d9;
    }

    .btn-outline-secondary:hover {
        background-color: #f3f4f6;
    }

    .fw-bold {
        font-weight: 700 !important;
    }

    .fw-semibold {
        font-weight: 600 !important;
    }

    .search-box {
    background: #fff;
    border-radius: 2rem;
    overflow: hidden;
    width: 100%;
    max-width: 420px;
    transition: box-shadow 0.3s ease;
    }

    .search-box:hover {
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }

    .search-box input {
        height: 45px;
        font-size: 0.95rem;
        outline: none;
        box-shadow: none !important;
    }

    .btn-search {
        background-color: #4a5fdc;
        color: #fff;
        border: none;
        width: 45px;
        height: 45px;
        display: flex;
        justify-content: center;
        align-items: center;
        transition: background-color 0.3s ease;
    }

    .btn-search:hover {
        background-color: #3c4fc9;
    }

</style>
@endsection
