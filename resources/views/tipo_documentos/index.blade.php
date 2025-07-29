@extends('layouts.app')

@section('content')

<div class="container">


    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="text-center">Listado Tipo Documentos</h1>
        <a href="{{ route('tipo_documentos.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tipo Documento
        </a>
    </div>




    <div class="row">


        <div class="col-lg-2">

            @component('layouts.columna_izquierda', [
                'tituloTarjeta' => 'Gestión Masiva Tipo de Doc.',
                'tituloFiltros' => 'Filtrar Tipo de Doc.',
                'action' => route('tipo_documentos.index')
            ])
                @slot('acciones')

                    <form class="mb-2">
                        
                        <a href="{{ route('exportar.tipo_documentos') }}" class="btn btn-success">
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
                        @foreach ($tipo_documentos  as $tipodocumento)
                        <tr>
                            <td>{{ $tipodocumento->nombre }}</td>


                            @include('layouts.acciones', [
                                'edit' => route('tipo_documentos.edit', $tipodocumento->id),
                                'delete' => route('tipo_documentos.destroy', $tipodocumento->id),
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