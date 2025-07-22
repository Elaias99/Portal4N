@extends('layouts.app')

@section('content')
<div class="container">

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">

        <h1 class="text-center mb-4">Lista de Salud</h1>
        <a href="{{ route('saluds.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Crear salud
        </a>

    </div>

    <div class="row">
        {{-- Filtros --}}
        <div class="col-lg-2">
            @include('layouts.filtros', [
                'titulo' => 'Filtrar Nombre',
                'action' => route('saluds.index'),
                'campos' => '
                    <input type="text" name="search" class="form-control" placeholder="Buscar..." value="' . request('search') . '">
                '
            ])
        </div>
        {{-- Tabla de resultados --}}
        <div class="col-lg-10">
            <div class="card-body">


                <table class="table table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
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
                    </tbody>

                </table>
            </div>
        </div>

    </div>

</div>
@endsection
