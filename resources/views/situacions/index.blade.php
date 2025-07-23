@extends('layouts.app')

@section('content')
<div class="container">



    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="text-center">Listado Estados Laborales</h1>
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
                    <input type="text" name="search" class="form-control" placeholder="Buscar Estado lab..." value="' . request('search') . '">
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


                        @foreach ($situacions as $situacion)
                        <tr>
                            
                            <td>{{ $situacion->Nombre }}</td>



                            {{-- Botones de acción --}}
                            @include('layouts.acciones', [
                                'edit' => route('situacions.edit', $situacion->id),
                                'delete' => route('situacions.destroy', $situacion->id),
                                'mensaje' => '¿Seguro que deseas eliminar este Estado Laboral?'
                            ])

                            {{-- <td style="width: 130px;" class="text-center">
                                <div class="d-flex flex-column gap-1">



                                    <a href="{{ route('situacions.edit', $situacion->id) }}" class="btn btn-sm btn-warning w-100 text-center d-inline-block">
                                        <i class="fas fa-edit"></i>Editar
                                    </a>


                                    <form action="{{ route('situacions.destroy', $situacion->id) }}" method="POST" class="w-100">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger w-100 text-center d-inline-block" onclick="return confirm('¿Estás seguro de que deseas eliminar esta situación laboral?')">
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
