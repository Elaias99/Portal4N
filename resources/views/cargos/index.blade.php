@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Lista de Cargos</h1>
    <a href="{{ route('cargos.create') }}" class="btn btn-primary">Crear Cargo</a>
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
        @foreach ($cargos as $cargo)
        <tr>
            <td>{{ $cargo->Nombre }}</td>
            <td>
                <a href="{{ route('cargos.edit', $cargo->id) }}" class="btn btn-warning">

                    <i class="fas fa-edit"></i>Editar
                </a>
                <form action="{{ route('cargos.destroy', $cargo->id) }}" method="POST" style="display:inline-block;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" onclick="return confirm('¿Seguro que deseas eliminar este Cargo?');">
                        <i class="fas fa-trash-alt"></i>Eliminar
                    </button>
                </form>
            </td>
        </tr>
        @endforeach
    </table>
</div>
@endsection
