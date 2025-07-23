@extends('layouts.app')

@section('content')
<div class="container">

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">

        <h1 class="text-center">Lista de Salud</h1>
        <a href="{{ route('saluds.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Salud
        </a>

    </div>

    <div class="row">
        {{-- Filtros --}}
        <div class="col-lg-2">
            @include('layouts.filtros', [
                'titulo' => 'Filtrar Nombre',
                'action' => route('saluds.index'),
                'campos' => '
                    <input type="text" name="search" class="form-control" placeholder="Buscar Sistema salud..." value="' . request('search') . '">
                '
            ])
        </div>
        {{-- Tabla de resultados --}}
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
                        @foreach ($saluds as $salud)
                        <tr>
                            
                            <td>{{ $salud->Nombre }}</td>



                            {{-- Botones de acción --}}
                            @include('layouts.acciones', [
                                'edit' => route('saluds.edit', $salud->id),
                                'delete' => route('saluds.destroy', $salud->id),
                                'mensaje' => '¿Seguro que deseas eliminar este sistema de salud?'
                            ])


                            {{-- <td style="width: 130px;" class="text-center">

                                <div class="d-flex flex-column gap-1">

                                    <a href="{{ route('saluds.edit', $salud->id) }}" class="btn btn-sm btn-warning w-100 text-center d-inline-block">
                                        <i class="fas fa-edit"></i>Editar
                                    </a>
                                    <form action="{{ route('saluds.destroy', $salud->id) }}" method="POST" class="w-100">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger w-100 text-center d-inline-block" onclick="return confirm('¿Estás seguro de que deseas eliminar este sistema de salud?')">
                                            <i class="fas fa-trash-alt"></i>Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td> --}}



                        </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>
        </div>

    </div>

</div>
@endsection
