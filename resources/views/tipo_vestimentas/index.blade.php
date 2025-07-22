@extends('layouts.app')

@section('content')
<div class="container">

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">

        <h1 class="text-center mb-4">Lista de Tipos de Vestimenta</h1>
        <a href="{{ route('tipo_vestimentas.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i>Tipo de Vestimenta
        </a>

    </div>





    <div class="row">

        <div class="col-lg-2">
            @include('layouts.filtros', [
                'titulo' => 'Filtrar Nombre',
                'action' => route('tipo_vestimentas.index'),
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
                    </tbody>
                    
                </table>
            </div>
        </div>



    </div>

</div>
@endsection
