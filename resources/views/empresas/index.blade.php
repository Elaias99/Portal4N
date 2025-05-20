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
                            No hay logo
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

