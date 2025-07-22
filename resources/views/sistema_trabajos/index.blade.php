@extends('layouts.app')

@section('content')
<div class="container">

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            {{ $message }}
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Lista de Sistemas de Trabajo</h1>
        <a href="{{ route('sistema_trabajos.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Crear Nuevo Sistema de Trabajo
        </a>
    </div>





    <div class="row">

        <div class="col-lg-2">
            @include('layouts.filtros', [
                'titulo' => 'Filtrar Nombre',
                'action' => route('sistema_trabajos.index'),
                'campos' => '
                    <input type="text" name="search" class="form-control" placeholder="Buscar..." value="' . request('search') . '">
                '
            ])
        </div>






        <div class="col-lg-10">
            <div class="card-body">

                <table class="table table-hover">
                    <thead class="thead-light">
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
        </div>
    </div>






</div>
@endsection
