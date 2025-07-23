<!-- resources/views/estado_civil/index.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container">

    {{-- Encabezado responsivo --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="text-center">Listado Estados Civiles</h1>
        <a href="{{ route('estado_civil.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Estado Civil
        </a>
    </div>

    <div class="row">
        {{-- Filtros --}}
        <div class="col-lg-2">
            @include('layouts.filtros', [
                'titulo' => 'Filtrar Nombre',
                'action' => route('estado_civil.index'),
                'campos' => '
                    <input type="text" name="search" class="form-control" placeholder="Buscar estado civil..." value="' . request('search') . '">
                '
            ])
        </div>

        {{-- Tabla de resultados --}}
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
                        @foreach ($estadoCivils as $estadoCivil)
                            <tr>
                                <td>{{ $estadoCivil->Nombre }}</td>

                                {{-- Botones de acción --}}

                                @include('layouts.acciones', [
                                    'edit' => route('estado_civil.edit', $estadoCivil->id),
                                    'delete' => route('estado_civil.destroy', $estadoCivil->id),
                                    'mensaje' => '¿Seguro que deseas eliminar este Estado Civil?'
                                ])

                                {{-- <td style="width: 130px;" class="text-center">
                                    <div class="d-flex flex-column gap-1">

                                        <a href="{{ route('estado_civil.edit', $estadoCivil->id) }}" class="btn btn-sm btn-warning w-100 text-center d-inline-block">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <form action="{{ route('estado_civil.destroy', $estadoCivil->id) }}" method="POST" class="w-100">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger w-100 text-center d-inline-block" onclick="return confirm('¿Confirmas que quieres eliminar este estado civil?')">
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
