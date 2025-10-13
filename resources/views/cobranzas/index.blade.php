@extends('layouts.app')

@section('content')
<div class="container py-4">

    {{-- Mensajes de éxito / error --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    {{-- Encabezado principal --}}
    <div class="text-center mb-4">
        <h2 class="fw-bold mb-2">Gestión de Cobranzas</h2>
        <p class="text-muted mb-3">Visualiza y administra la información de las cobranzas registradas en el sistema.</p>

        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="{{ route('cobranzas.documentos') }}" class="btn btn-outline-secondary px-4">
                Volver a Reporte
            </a>

            <a href="{{ route('cobranzas.create') }}" class="btn btn-primary px-4">
                Nueva Cobranza
            </a>
        </div>
    </div>

    {{-- Barra de búsqueda --}}
    <form method="GET" action="{{ route('cobranzas.index') }}" class="mb-4 d-flex justify-content-center">
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


    {{-- Tarjeta principal --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">

            {{-- Título interno --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-semibold mb-0">Listado de Cobranzas</h5>
                <span class="text-muted small">Total: {{ $cobranzas->count() }} registros</span>
            </div>

            {{-- Tabla --}}
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start">Rut Cliente</th>
                            <th class="text-start">Razón Social</th>
                            <th class="text-center">Servicio</th>
                            <th class="text-center">Créditos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cobranzas as $cobranza)
                            <tr class="table-row-hover">
                                <td class="text-start">{{ $cobranza->rut_cliente }}</td>
                                <td class="text-start">{{ $cobranza->razon_social }}</td>
                                <td class="text-center">{{ $cobranza->servicio }}</td>
                                <td class="text-center">{{ $cobranza->creditos }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    No hay cobranzas registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
        {{-- Paginación --}}
        @if ($cobranzas->hasPages())
            <div class="mt-4 d-flex justify-content-center">
                {{ $cobranzas->appends(request()->query())->links('pagination::bootstrap-4') }}
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
