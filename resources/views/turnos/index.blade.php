@extends('layouts.app')

@section('content')
<div class="container">
    
    @if ($message = Session::get('success'))
        <div class="alert alert-success mt-3">
            {{ $message }}
        </div>
    @endif
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="text-center">Lista de Turnos</h1>
        <a href="{{ route('turnos.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Turno
        </a>
    </div>

    <div class="row">

        {{-- Filtros --}}
        <div class="col-lg-2">
            @include('layouts.filtros', [
                'titulo' => 'Filtrar Nombre',
                'action' => route('turnos.index'),
                'campos' => '
                    <input type="text" name="search" class="form-control" placeholder="Buscar Turno..." value="' . request('search') . '">
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
                        @foreach ($turnos as $turno)
                        <tr>
                            {{-- <td>{{ $turno->id }}</td> --}}
                            <td>{{ $turno->nombre }}</td>


                            {{-- Botones de acción --}}

                            @include('layouts.acciones', [
                                'edit' => route('turnos.edit', $turno->id),
                                'delete' => route('turnos.destroy', $turno->id),
                                'mensaje' => '¿Seguro que deseas eliminar este Turno?'
                            ])



                            {{-- <td style="width: 130px;" class="text-center">

                                <div class="d-flex flex-column gap-1">
                                    <a href="{{ route('turnos.edit', $turno->id) }}" class="btn btn-sm btn-warning w-100 text-center d-inline-block">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <form action="{{ route('turnos.destroy', $turno->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger w-100 text-center d-inline-block" onclick="return confirm('¿Estás seguro de que deseas eliminar este turno?')">
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
