@extends('layouts.app')

@section('content')
<div class="container">

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">

        <h1 class="text-center">Lista de Tipos de Vestimenta</h1>
        <a href="{{ route('tipo_vestimentas.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tipo de Vestimenta
        </a>

    </div>

    <div class="row">

        <div class="col-lg-2">
            @include('layouts.filtros', [
                'titulo' => 'Filtrar Nombre',
                'action' => route('tipo_vestimentas.index'),
                'campos' => '
                    <input type="text" name="search" class="form-control" placeholder="Buscar Tipo Vestimenta..." value="' . request('search') . '">
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
                        @foreach ($tipoVestimentas as $tipoVestimenta)
                        <tr>
                            <td>{{ $tipoVestimenta->Nombre }}</td>



                            {{-- Botones de acción --}}
                            @include('layouts.acciones', [
                                'edit' => route('tipo_vestimentas.edit', $tipoVestimenta->id),
                                'delete' => route('tipo_vestimentas.destroy', $tipoVestimenta->id),
                                'mensaje' => '¿Seguro que deseas eliminar este Tipo de Vestiemnta?'
                            ])

                            {{-- <td style="width: 130px;" class="text-center">

                                <div class="d-flex flex-column gap-1">

                                    <a href="{{ route('tipo_vestimentas.edit', $tipoVestimenta->id) }}" class="btn btn-sm btn-warning w-100 text-center d-inline-block">
                                        <i class="fas fa-edit"></i>Editar
                                    </a>
                                    <form action="{{ route('tipo_vestimentas.destroy', $tipoVestimenta->id) }}" method="POST" class="w-100">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger w-100 text-center d-inline-block" onclick="return confirm('¿Seguro que deseas eliminar esta vestimenta?');">
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
