@extends('layouts.app')

@section('content')
<div class="container">



    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Administración Estados Laborales</h1>
        <a href="{{ route('situacions.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i>Estado Laboral
        </a>
    </div>


    <div class="row">

        {{-- Filtros --}}
        <div class="col-lg-2">
            @include('layouts.filtros', [
                'titulo' => 'Filtrar Nombre',
                'action' => route('situacions.index'),
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
                            
                            <th>Nombre</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>


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

                    </tbody>
                </table>
            </div>
        </div>



    </div>



</div>
@endsection
