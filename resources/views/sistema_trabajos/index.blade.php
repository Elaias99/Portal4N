@extends('layouts.app')

@section('content')
<div class="container">

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            {{ $message }}
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="text-center">Lista de Sistemas de Trabajo</h1>
        <a href="{{ route('sistema_trabajos.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Sistema de Trabajo
        </a>
    </div>

    <div class="row">

        <div class="col-lg-2">
            @include('layouts.filtros', [
                'titulo' => 'Filtrar Nombre',
                'action' => route('sistema_trabajos.index'),
                'campos' => '
                    <input type="text" name="search" class="form-control" placeholder="Buscar Sistema Trabajo..." value="' . request('search') . '">
                '
            ])
        </div>

        <div class="col-lg-10">
            <div class="table-responsive">

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

                            {{-- Botones de acción --}}

                            @include('layouts.acciones', [
                                'edit' => route('sistema_trabajos.edit', $sistema->id),
                                'delete' => route('sistema_trabajos.destroy', $sistema->id),
                                'mensaje' => '¿Seguro que deseas eliminar este Sistema de trabajo?'
                            ])





                            {{-- <td style="width: 130px;" class="text-center">

                                <div class="d-flex flex-column gap-1">
                                    <a href="{{ route('sistema_trabajos.edit', $sistema->id) }}" class="btn btn-sm btn-warning w-100 text-center d-inline-block">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <form action="{{ route('sistema_trabajos.destroy', $sistema->id) }}" method="POST" class="w-100">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger w-100 text-center d-inline-block" onclick="return confirm('¿Estás seguro de que deseas eliminar este sistema?')">
                                            <i class="fas fa-trash-alt"></i> Eliminar
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
