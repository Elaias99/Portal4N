@extends('layouts.app')

@section('content')
<div class="container">

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif


    {{-- <h1>Lista de Cargos</h1>
    <a href="{{ route('cargos.create') }}" class="btn btn-primary"></a> --}}

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-center">Lista de Cargos</h1>
        <a href="{{ route('cargos.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Crear Cargo
        </a>
    </div>







    <div class="row">

        <div class="col-lg-2">
            @include('layouts.filtros', [
                'titulo' => 'Filtrar Nombre',
                'action' => route('cargos.index'),
                'campos' => '
                    <input type="text" name="search" class="form-control" placeholder="Buscar cargo..." value="' . request('search') . '">
                '
            ])
        </div>






        <div class="col-lg-10">
            <div class="card-body">
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
        </div>




    </div>






</div>
@endsection
