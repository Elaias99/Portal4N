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


                        </tr>
                        @endforeach

                    </tbody>
                </table>
            </div>
        </div>



    </div>



</div>
@endsection
