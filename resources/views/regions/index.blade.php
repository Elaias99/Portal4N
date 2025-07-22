@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="text-center mb-4">Listado de Regiones</h1>
        <a href="{{ route('regions.create') }}" class="btn btn-primary">Agregar Región</a>
    </div>
        
    <div class="row">

        <div class="col-lg-2">
            @include('layouts.filtros', [
                'titulo' => 'Filtrar Region',
                'action' => route('regions.index'),
                'campos' => '
                    <input type="text" name="search" class="form-control" placeholder="Buscar región..." value="' . request('search') . '">
                '
            ])
        </div>


        <div class="col-lg-10">
            <div class="card-body">
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
                                
                                <td>
                                    <a href="{{ route('regions.edit', $region->id) }}" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <form action="{{ route('regions.destroy', $region->id) }}" method="POST" style="display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Seguro que deseas eliminar esta región?');">
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

@section('scripts')
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
@endsection


