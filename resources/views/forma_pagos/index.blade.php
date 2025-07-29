@extends('layouts.app')

@section('content')

<div class="container">


    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="text-center">Listado Formas de Pago</h1>
        <a href="{{ route('forma_pagos.create') }}" class="btn btn-primary ">
            <i class="fas fa-plus"></i> Forma Pago
        </a>
    </div>


    <div class="row">


        <div class="col-lg-2">

            @component('layouts.columna_izquierda', [
                'tituloTarjeta' => 'Gestión Masiva Forma Pago',
                'tituloFiltros' => 'Filtrar Forma Pago',
                'action' => route('forma_pagos.index')
            ])
                @slot('acciones')

                    <form class="mb-2">

                        <div class="text-center">
                        
                            <a href="{{ route('exportar.forma_pago') }}" class="btn btn-success">
                                <i class="fas fa-file-excel"></i> Exportar
                            </a>
                        </div>
                        
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
                        @foreach ($forma_pagos  as $formapago)
                        <tr>
                            <td>{{ $formapago->nombre }}</td>


                            @include('layouts.acciones', [
                                'edit' => route('forma_pagos.edit', $formapago->id),
                                'delete' => route('forma_pagos.destroy', $formapago->id),
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