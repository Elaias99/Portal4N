<!-- resources/views/estado_civil/index.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container">

    {{-- Encabezado responsivo --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="text-center mb-4">Estados Civiles</h1>
        <a href="{{ route('estado_civil.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Agregar Estado Civil
        </a>
    </div>

    <div class="row">
        {{-- Filtros --}}
        <div class="col-lg-2">
            @include('layouts.filtros', [
                'titulo' => 'Filtrar Nombre',
                'action' => route('estado_civil.index'),
                'campos' => '
                    <input type="text" name="search" class="form-control" placeholder="Buscar..." value="' . request('search') . '">
                '
            ])
        </div>

        {{-- Tabla de resultados --}}
        <div class="col-lg-10">
            <div class="card-body">


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


                                <td>
                                    
                                    <a href="{{ route('estado_civil.edit', $estadoCivil->id) }}" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>


                                    <form action="{{ route('estado_civil.destroy', $estadoCivil->id) }}" method="POST" style="display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Confirmas que quieres eliminar este estado civil?')">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    </form>
                                    
                                </td>



                            </tr>
                        @endforeach
                    </tbody>
                </table>


                
            </div>
        </div>
    </div>

</div>
@endsection
