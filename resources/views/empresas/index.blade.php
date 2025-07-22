<!-- resources/views/empresas/index.blade.php -->

@extends('layouts.app')

@section('content')
<div class="container">

    {{-- Encabezado con botón, adaptado para pantallas pequeñas --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-center">Gestionar Empresas</h1>

        <!-- Botón para redirigir a la página de creación -->
        <a href="{{ route('empresas.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Añadir Empresa
        </a>
    </div>

    <div class="row">
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-body">
                    {{-- Tabla responsiva con scroll horizontal en móviles --}}
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-center">
                            <thead class="thead-light">
                                <tr>
                                    <th>Logo</th>
                                    <th>Nombre</th>
                                    <th>Rut</th>
                                    <th>Giro</th>
                                    <th>Dirección</th>
                                    <th>Cta Corriente</th>
                                    <th>Mail Formalizado</th>
                                    <th>Banco</th>
                                    <th>Comuna</th>
                                    <th>Región</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($empresas as $empresa)
                                    <tr>
                                        <td>
                                            @if($empresa->logo)
                                                <img src="{{ url($empresa->logo) }}" alt="Logo de {{ $empresa->Nombre }}" style="max-height: 50px;">
                                            @else
                                                <span class="text-muted">Sin logo</span>
                                            @endif
                                        </td>
                                        <td>{{ $empresa->Nombre }}</td>
                                        <td>{{ $empresa->rut }}</td>
                                        <td>{{ $empresa->giro }}</td>
                                        <td>{{ $empresa->direccion }}</td>
                                        <td>{{ $empresa->cta_corriente }}</td>
                                        <td>{{ $empresa->mail_formalizado }}</td>
                                        <td>{{ $empresa->banco->nombre }}</td>
                                        <td>{{ $empresa->comuna->Nombre }}</td>
                                        <td>{{ $empresa->comuna->region->Nombre ?? 'Sin región' }}</td>
                                        <td>
                                            <a href="{{ route('empresas.edit', $empresa->id) }}" class="btn btn-sm btn-warning mb-1">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                            <form action="{{ route('empresas.destroy', $empresa->id) }}" method="POST" style="display:inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de que deseas eliminar la empresa?')">
                                                    <i class="fas fa-trash-alt"></i> Eliminar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div> {{-- .table-responsive --}}
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
