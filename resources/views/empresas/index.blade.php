{{-- <!-- resources/views/empresas/index.blade.php -->

@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Gestionar Empresas</h1>
    <button class="btn btn-primary" data-toggle="modal" data-target="#empresasModal">Agregar Empresa</button>

    <!-- Incluir el modal desde partials -->
    @include('partials.empresas_modal')

    <!-- Lista de empresas -->
    <table class="table mt-3">
        <thead>
            <tr>
                <th>Logo</th>
                <th>Nombre</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($empresas as $empresa)
                <tr>
                    <td>
                        @if($empresa->logo)
                            <img src="{{ asset('storage/' . $empresa->logo) }}" alt="Logo de {{ $empresa->Nombre }}" style="max-height: 50px;">
                        @else
                            No hay logo
                        @endif
                    </td>
                    <td>{{ $empresa->Nombre }}</td>
                    <td>
                        <!-- Botones para editar y eliminar -->
                        <button class="btn btn-warning" data-toggle="modal" data-target="#editEmpresaModal{{ $empresa->id }}">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        <form action="{{ route('empresas.destroy', $empresa->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" onclick="return confirm('¿Estás seguro de que deseas eliminar la empresa?')">
                                <i class="fas fa-trash-alt"></i> Eliminar
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @foreach ($empresas as $empresa)
        <!-- Modal para editar empresa -->
        <div class="modal fade" id="editEmpresaModal{{ $empresa->id }}" tabindex="-1" role="dialog" aria-labelledby="editEmpresaModalLabel{{ $empresa->id }}" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editEmpresaModalLabel{{ $empresa->id }}">Editar Empresa</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="{{ route('empresas.update', $empresa->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        @include('empresas.form')
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                            <button type="submit" class="btn btn-primary">Guardar cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection --}}



<!-- resources/views/empresas/index.blade.php -->

@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Gestionar Empresas</h1>

    <!-- Botón para redirigir a la página de creación -->
    <a href="{{ route('empresas.create') }}" class="btn btn-primary mb-3">Añadir Empresa</a>

    <!-- Lista de empresas -->
    <table class="table mt-3">
        <thead>
            <tr>
                <th>Logo</th>
                <th>Nombre</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($empresas as $empresa)
                <tr>
                    <td>
                        @if($empresa->logo)
                            <img src="{{ asset('storage/' . $empresa->logo) }}" alt="Logo de {{ $empresa->Nombre }}" style="max-height: 50px;">
                        @else
                            No hay logo
                        @endif
                    </td>
                    <td>{{ $empresa->Nombre }}</td>
                    <td>
                        <!-- Enlace para editar empresa -->
                        <a href="{{ route('empresas.edit', $empresa->id) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <form action="{{ route('empresas.destroy', $empresa->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" onclick="return confirm('¿Estás seguro de que deseas eliminar la empresa?')">
                                <i class="fas fa-trash-alt"></i> Eliminar
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

