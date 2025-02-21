@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Administración Estados Laborales</h1>
    <a href="{{ route('situacions.create') }}" class="btn btn-primary">Agregar Estado Laboral</a>
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
        @foreach ($situacions as $situacion)
        <tr>
            
            <td>{{ $situacion->Nombre }}</td>
            <td>
                <a href="{{ route('situacions.edit', $situacion->id) }}" class="btn btn-warning">
                    <i class="fas fa-edit"></i>Editar
                </a>
                <form action="{{ route('situacions.destroy', $situacion->id) }}" method="POST" style="display:inline-block;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" onclick="return confirm('¿Estás seguro de que deseas eliminar esta situación laboral?')">
                        <i class="fas fa-trash-alt"></i>Eliminar
                    </button>
                </form>
            </td>
        </tr>
        @endforeach
    </table>
</div>
@endsection
