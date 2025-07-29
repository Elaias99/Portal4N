@extends('layouts.app')

@section('content')
<div class="container">

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="text-center">Listado Bancos</h1>
        <a href="{{ route('bancos.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Banco
        </a>
    </div>


    <div class="row">


        {{-- Filtros --}}
        <div class="col-lg-2">




            @component('layouts.columna_izquierda', [
                'tituloTarjeta' => 'Gestión Masiva Bancos',
                'tituloFiltros' => 'Filtrar Bancos',
                'action' => route('bancos.index')
            ])
                @slot('acciones')

                    <form class="mb-2">
                        
                        <a href="{{ route('exportar.bancos') }}" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Exportar lista de bancos
                        </a>
                        
                    </form>
     
                @endslot

                @slot('filtros')

                    <div class="mb-3">
                        <label class="form-label">Filtrar Nombre:</label>
                        <input type="text" name="search" id="comunaSearch" class="form-control" placeholder="Buscar banco..." value="{{ request('search') }}">
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
                        @foreach ($bancos as $banco)
                        <tr>
                            <td>{{ $banco->nombre }}</td>


                            @include('layouts.acciones', [
                                'edit' => route('bancos.edit', $banco->id),
                                'delete' => route('bancos.destroy', $banco->id),
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