@extends('layouts.app')

@section('content')

<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="text-center">Listado Tipo Cuenta</h1>
        <a href="{{ route('tipo_cuentas.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tipo Cuenta
        </a>
    </div>



    <div class="row">

        <div class="col-lg-2">

            @component('layouts.columna_izquierda', [
                'tituloTarjeta' => 'Gestión Masiva Tipo de cuenta',
                'tituloFiltros' => 'Filtrar Tipo de Cuenta',
                'action' => route('tipo_cuentas.index')
            ])
                @slot('acciones')

                    <form class="mb-2">
                        
                        <a href="{{ route('exportar.tipo_cuentas') }}" class="btn btn-success">
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
                        @foreach ($tipo_cuentas  as $tipocuenta)
                        <tr>
                            <td>{{ $tipocuenta->nombre }}</td>


                            @include('layouts.acciones', [
                                'edit' => route('tipo_cuentas.edit', $tipocuenta->id),
                                'delete' => route('tipo_cuentas.destroy', $tipocuenta->id),
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