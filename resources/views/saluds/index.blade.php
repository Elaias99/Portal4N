@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Lista de Salud</h1>
    <a href="{{ route('saluds.create') }}" class="btn btn-primary">Crear salud</a>
    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif
    <table class="table table-bordered">
        <tr>
            
            <th>Nombre</th>
            <th>Acciones</th>
        </tr>
        @foreach ($saluds as $salud)
        <tr>
            
            <td>{{ $salud->Nombre }}</td>
            <td>
                <a href="{{ route('saluds.edit', $salud->id) }}" class="btn btn-warning">
                    <i class="fas fa-edit"></i>Editar
                </a>
                <form action="{{ route('saluds.destroy', $salud->id) }}" method="POST" style="display:inline-block;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" onclick="return confirm('¿Estás seguro de que deseas eliminar este sistema de salud?')">
                        <i class="fas fa-trash-alt"></i>Eliminar
                    </button>
                </form>
            </td>
        </tr>
        @endforeach
    </table>
</div>
@endsection
