@extends('layouts.app')

@section('content')
<div class="container">


    <h1>Lista de Tipos de Vestimenta</h1>
    <a href="{{ route('tipo_vestimentas.create') }}" class="btn btn-primary">Crear Tipo de Vestimenta</a>


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


        @foreach ($tipoVestimentas as $tipoVestimenta)
        <tr>
            <td>{{ $tipoVestimenta->Nombre }}</td>
            <td>
                <a href="{{ route('tipo_vestimentas.edit', $tipoVestimenta->id) }}" class="btn btn-warning">
                    <i class="fas fa-edit"></i>Editar
                </a>
                <form action="{{ route('tipo_vestimentas.destroy', $tipoVestimenta->id) }}" method="POST" style="display:inline-block;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" onclick="return confirm('¿Seguro que deseas eliminar esta vestimenta?');">
                        <i class="fas fa-trash-alt"></i>Eliminar
                    </button>
                </form>
            </td>
        </tr>
        @endforeach
        
    </table>


</div>
@endsection
