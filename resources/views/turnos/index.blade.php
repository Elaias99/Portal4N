@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Lista de Turnos</h1>
    <a href="{{ route('turnos.create') }}" class="btn btn-primary">Crear Nuevo Turno</a>
    
    @if ($message = Session::get('success'))
        <div class="alert alert-success mt-3">
            {{ $message }}
        </div>
    @endif

    <table class="table mt-3">
        <thead>
            <tr>
                {{-- <th>ID</th> --}}
                <th>Nombre</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($turnos as $turno)
            <tr>
                {{-- <td>{{ $turno->id }}</td> --}}
                <td>{{ $turno->nombre }}</td>
                <td>
                    <a href="{{ route('turnos.edit', $turno->id) }}" class="btn btn-warning">Editar</a>
                    <form action="{{ route('turnos.destroy', $turno->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('¿Estás seguro de que deseas eliminar este turno?')">Eliminar</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
