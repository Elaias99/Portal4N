<!-- resources/views/estado_civil/index.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Estados Civiles</h1>
    <a href="{{ route('estado_civil.create') }}" class="btn btn-primary">Agregar Estado Civil</a>
    <table class="table mt-3">
        <thead>
            <tr>
                {{-- <th>ID</th> --}}
                <th>Nombre</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($estadoCivils as $estadoCivil)
                <tr>
                    {{-- <td>{{ $estadoCivil->id }}</td> --}}
                    <td>{{ $estadoCivil->Nombre }}</td>
                    <td>
                        <a href="{{ route('estado_civil.edit', $estadoCivil->id) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i>Editar
                        </a>
                        <form action="{{ route('estado_civil.destroy', $estadoCivil->id) }}" method="POST" style="display:inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" onclick="return confirm('¿Confirmas que quieres eliminar este estado civil?')">
                                <i class="fas fa-trash-alt"></i>Eliminar
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
