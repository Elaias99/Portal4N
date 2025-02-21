@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Lista de Sistemas de Trabajo</h1>
    <a href="{{ route('sistema_trabajos.create') }}" class="btn btn-primary mb-3">Crear Nuevo Sistema de Trabajo</a>
    
    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            {{ $message }}
        </div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                {{-- <th>ID</th> --}}
                <th>Nombre</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sistemasTrabajo as $sistema)
            <tr>
                {{-- <td>{{ $sistema->id }}</td> --}}
                <td>{{ $sistema->nombre }}</td>
                <td>
                    <a href="{{ route('sistema_trabajos.edit', $sistema->id) }}" class="btn btn-warning">Editar</a>
                    <form action="{{ route('sistema_trabajos.destroy', $sistema->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('¿Estás seguro de que deseas eliminar este sistema?')">Eliminar</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
