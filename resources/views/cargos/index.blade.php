@extends('layouts.app')

@section('content')
<div class="container">

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif


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
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cargos as $cargo)

                        <tr>
                            <td>{{ $cargo->Nombre }}</td>

                            @include('layouts.acciones', [
                                'edit' => route('cargos.edit', $cargo->id),
                                'delete' => route('cargos.destroy', $cargo->id),
                                'mensaje' => '¿Seguro que deseas eliminar este Cargo?'
                            ])


                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>
@endsection
