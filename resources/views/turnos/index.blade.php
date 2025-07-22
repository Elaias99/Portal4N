@extends('layouts.app')

@section('content')
<div class="container">
    
    @if ($message = Session::get('success'))
        <div class="alert alert-success mt-3">
            {{ $message }}
        </div>
    @endif
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Lista de Turnos</h1>
        <a href="{{ route('turnos.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Crear Nuevo Turno
        </a>
    </div>



    <div class="row">

        {{-- Filtros --}}
        <div class="col-lg-2">
            @include('layouts.filtros', [
                'titulo' => 'Filtrar Nombre',
                'action' => route('turnos.index'),
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
        </div>




    </div>








</div>
@endsection
