@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-center">Listado de Regiones</h1>
        <a href="{{ route('regions.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Región
        </a>
    </div>
        
    <div class="row">

        <div class="col-lg-2">

            @component('layouts.columna_izquierda', [
                'tituloTarjeta' => 'Gestión Masiva Regiones',
                'tituloFiltros' => 'Filtrar Region',
                'action' => route('regions.index')
            ])
                @slot('acciones')

                    <form class="mb-2">
                        
                        <a href="{{ route('comunas.export') }}" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Exportar a Excel
                        </a>
                        
                    </form>
     
                @endslot

                @slot('filtros')

                    <div class="mb-3">
                        <label class="form-label">Filtrar Nombre:</label>
                        <input type="text" name="search" class="form-control" placeholder="Buscar región..." value="{{ request('search') }}">
                    </div>

                @endslot
            @endcomponent

        </div>

        {{-- Tabla Principal --}}
        <div class="col-lg-10">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Abreviatura</th>
                            <th>Nombre</th>
                            <th>Numero Romano</th>
                            <th>Numero</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($regions as $region)
                            <tr>
                                <td>{{ $region->Abreviatura }}</td>
                                <td>{{ $region->Nombre }}</td>
                                <td>{{ $region->NumeroRomano }}</td>

                                <td>{{ $region->Numero }}</td>



                                {{-- Botones de acción --}}
                                @include('layouts.acciones', [
                                    'edit' => route('regions.edit', $region->id),
                                    'delete' => route('regions.destroy', $region->id),
                                    'mensaje' => '¿Seguro que deseas eliminar esta Región?'
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

@section('scripts')
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
@endsection


