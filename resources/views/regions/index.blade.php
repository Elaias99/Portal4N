{{-- @extends('layouts.app')

@section('content')
<div class="container">
    <h1>Lista de Regiones</h1>
    <a href="{{ route('regions.create') }}" class="btn btn-primary">Crear Región</a>
    
    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    <table class="table table-bordered mt-3">
        <thead class="thead-light">
            <tr>
                {{-- <th>#</th> --}}
                {{-- <th>Nombre</th>
                <th>Número</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($regions as $region)
            <tr>
                {{-- <td>{{ $region->id }}</td> --}}
                {{-- <td>{{ $region->Nombre }}</td>
                <td>{{ $region->Numero }}</td>
                <td>
                    <a href="{{ route('regions.edit', $region->id) }}" class="btn btn-warning">Editar</a>
                    <form action="{{ route('regions.destroy', $region->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="return confirm('¿Borrar?');" class="btn btn-danger">Eliminar</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection --}} 







@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="text-primary">Listado de Regiones</h1>
        <a href="{{ route('regions.create') }}" class="btn btn-primary">Agregar Región</a>
    </div>
    
    <div class="card">
        <div class="card-header">
            <form action="{{ route('regions.index') }}" method="GET" class="form-inline">
                <input type="text" name="search" class="form-control mr-2" placeholder="Buscar región..." value="{{ request()->query('search') }}">
                <button type="submit" class="btn btn-secondary">Buscar</button>
            </form>
        </div>
        <div class="card-body">
            <table class="table table-hover">
                <thead class="thead-light">
                    <tr>
                        
                        <th>Nombre</th>
                        <th>Numero</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($regions as $region)
                        <tr>
                            <td>{{ $region->Nombre }}</td>
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
@endsection

@section('scripts')
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
@endsection


