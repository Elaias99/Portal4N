@extends('layouts.app')

@section('content')

<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="text-center">Listado Centro Costo</h1>
        <a href="{{ route('centro_costos.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Centro costo
        </a>
    </div>


    <div class="row">

        {{-- Filtros --}}
        <div class="col-lg-2">

            @component('layouts.columna_izquierda', [
                'tituloTarjeta' => 'Gestión Masiva Cetro Costo',
                'tituloFiltros' => 'Filtrar Cetro Costo',
                'action' => route('centro_costos.index')
            ])
                @slot('acciones')

                    <form class="mb-2">
                        
                        <a href="{{ route('exportar.centro_costo') }}" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Exportar
                        </a>
                        
                    </form>
     
                @endslot

                @slot('filtros')

                    <div class="mb-3">
                        <label class="form-label">Filtrar Nombre:</label>
                        <input type="text" name="search" class="form-control" placeholder="Buscar..." value="{{ request('search') }}">
                    </div>

                @endslot
            @endcomponent

        </div>



        <div class="col-lg-10">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Nombre</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($centrocostos as $centrocosto)
                        <tr>
                            <td>{{ $centrocosto->nombre }}</td>


                            @include('layouts.acciones', [
                                'edit' => route('centro_costos.edit', $centrocosto->id),
                                'delete' => route('centro_costos.destroy', $centrocosto->id),
                                'mensaje' => '¿Seguro que deseas eliminar este Registro?'
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