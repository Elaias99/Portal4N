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
        <h2 class="fw-bold mb-2">Gestión Proveedores</h2>
        <p class="text-muted mb-3">
            Visualiza y administra la información de los proveedores asociados a documentos.
        </p>

        <div class="d-flex justify-content-center gap-3 flex-wrap">
            @if(request('origen') === 'honorarios')
                <a href="{{ route('honorarios.mensual.index') }}"
                class="btn btn-outline-secondary px-4">
                    Volver a Honorarios
                </a>
            @else
                <a href="{{ route('finanzas_compras.index') }}"
                class="btn btn-outline-secondary px-4">
                    Volver a Compras
                </a>
            @endif


            @if (Auth::id() != 375)
                <a href="{{ route('cobranzas-compras.create') }}" class="btn btn-primary px-4">
                    Nueva Cobranza
                </a>
            @endif

            <a href="{{ route('cobranzasCompra.export') }}" class="btn btn-outline-success px-4">
                Exportar Excel
            </a>
        </div>
    </div>

    {{-- Buscador --}}
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

    {{-- Contenedor Tabla --}}
    <div class="card border-0 shadow-sm rounded-4 w-100">
        <div class="card-body p-4">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-semibold mb-0">Listado de Cobranzas de Compras</h5>
                <span class="text-muted small">Total: {{ $cobranzasCompras->count() }} registros</span>
            </div>

            <table class="table table-hover align-middle mb-0 new-table">
                <thead>
                    <tr>
                        <th>Rut Cliente</th>
                        <th>Razón Social</th>
                        <th>Servicio</th>
                        <th>Créditos</th>
                        <th>Tipo</th>
                        <th>Zona</th>
                        <th>Importancia</th>
                        <th>Responsable</th>
                        <th class="text-center">Detalles</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($cobranzasCompras as $c)
                        <tr>
                            <td>{{ $c->rut_cliente }}</td>
                            <td>{{ $c->razon_social }}</td>
                            <td>{{ $c->servicio }}</td>
                            <td>{{ $c->creditos }}</td>
                            <td>{{ $c->tipo }}</td>
                            <td>{{ $c->zona }}</td>
                            <td>{{ $c->importancia }}</td>
                            <td>{{ $c->responsable }}</td>

                            {{-- Botón Detalles --}}
                            <td class="text-center">
                                <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#detalles{{ $c->id }}">
                                    Ver
                                </button>
                            </td>



                                {{-- Acciones --}}
                                <td>
                                    @if (Auth::id() != 375)
                                        <a href="{{ route('cobranzas-compras.edit', $c) }}" class="btn btn-sm btn-warning">
                                            Editar
                                        </a>

                                        <form action="{{ route('cobranzas-compras.destroy', $c) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                onclick="return confirm('¿Seguro que deseas eliminar esta cobranza?')">
                                                Eliminar
                                            </button>
                                        </form>
                                    @endif
                                </td>



                        </tr>

                        {{-- Modal Detalles --}}
                        <div class="modal fade" id="detalles{{ $c->id }}" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content shadow">
                                    <div class="modal-header ">
                                        <h5 class="modal-title">Detalles de Cobranza</h5>

                                        <button type="button"
                                                class="btn btn-light btn-sm rounded-circle shadow-sm"
                                                data-dismiss="modal"
                                                aria-label="Cerrar"
                                                style="
                                                    position: absolute;
                                                    top: 16px;
                                                    right: 16px;
                                                    width: 32px;
                                                    height: 32px;
                                                    display: flex;
                                                    align-items: center;
                                                    justify-content: center;
                                                    z-index: 10;
                                                ">
                                            <span aria-hidden="true" class="text-dark" style="font-size: 1.2rem;">&times;</span>
                                        </button>


                                        
                                    </div>

                                    <div class="modal-body">
                                        <ul class="list-group">
                                            <li class="list-group-item"><strong>Facturación:</strong> {{ $c->facturacion }}</li>
                                            <li class="list-group-item"><strong>Forma Pago:</strong> {{ $c->forma_pago }}</li>
                                            <li class="list-group-item"><strong>Nombre Cuenta:</strong> {{ $c->nombre_cuenta }}</li>
                                            <li class="list-group-item"><strong>Rut Cuenta:</strong> {{ $c->rut_cuenta }}</li>
                                            <li class="list-group-item"><strong>Número Cuenta:</strong> {{ $c->numero_cuenta }}</li>
                                            <li class="list-group-item"><strong>Banco:</strong> {{ optional($c->banco)->nombre }}</li>
                                            <li class="list-group-item"><strong>Tipo Cuenta:</strong> {{ optional($c->tipoCuenta)->nombre }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                    @endforeach
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

{{-- Estilos --}}
<style>

    .container {
        max-width: 95% !important;
    }

    .new-table th,
    .new-table td {
        white-space: nowrap;
        font-size: 14px;
        padding: 8px 12px;
    }

    .search-box {
        background: #fff;
        border-radius: 2rem;
        overflow: hidden;
        max-width: 420px;
        width: 100%;
    }

    .btn-search {
        background-color: #4a5fdc;
        color: #fff;
        width: 45px;
        height: 45px;
    }

</style>

@endsection
