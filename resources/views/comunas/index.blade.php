{{-- @extends('layouts.app')

@section('content')
<div class="container">
    <h1>Lista de Comunas por Región</h1>

    <a href="{{ route('comunas.create') }}" class="btn btn-primary mb-3" style="background-color: #007bff; border-color: #007bff; font-size: 16px; padding: 10px 20px; border-radius: 5px;">
        <i class="fas fa-plus"></i> Crear Comuna
    </a>
    
    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    <div class="accordion" id="accordionRegions">
        @foreach($regions as $region)
            <div class="accordion-item">


                <h2 class="accordion-header" id="heading{{ $region->id }}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $region->id }}" aria-expanded="false" aria-controls="collapse{{ $region->id }}">
                        {{ $region->Nombre }} ({{ $region->Numero }})
                    </button>
                </h2>

                
                <div id="collapse{{ $region->id }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $region->id }}" data-bs-parent="#accordionRegions">
                    <div class="accordion-body">
                        <table class="table table-light table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Nombre Comuna</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($region->comunas as $comuna)
                                    <tr>
                                        <td>{{ $comuna->Nombre }}</td>
                                        <td>
                                            <a class="btn btn-warning" href="{{ route('comunas.edit', $comuna->id) }}">
                                                <i class="fas fa-edit"></i>Editar
                                            </a>
                                            <form action="{{ route('comunas.destroy', $comuna->id) }}" method="POST" style="display:inline">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-danger" type="submit" onclick="return confirm('¿Seguro que deseas eliminar esta Comuna?')">
                                                    <i class="fas fa-trash-alt"></i>Eliminar
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
        @endforeach
    </div>
</div>
@endsection --}}





@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Lista de Comunas por Región</h1>
    <a href="{{ route('comunas.create') }}" class="btn btn-primary mb-3" style="background-color: #007bff; border-color: #007bff; font-size: 16px; padding: 10px 20px; border-radius: 5px;">
        <i class="fas fa-plus"></i> Crear Comuna
    </a>
    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    <div class="accordion" id="accordionRegions">
        @foreach($regions as $region)
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading{{ $region->id }}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $region->id }}" aria-expanded="false" aria-controls="collapse{{ $region->id }}">
                        {{ $region->Nombre }} ({{ $region->Numero }})
                    </button>
                </h2>
                <div id="collapse{{ $region->id }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $region->id }}" data-bs-parent="#accordionRegions">
                    <div class="accordion-body">
                        <table class="table table-light table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Nombre Comuna</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($region->comunas as $comuna)
                                    <tr>
                                        <td>{{ $comuna->Nombre }}</td>
                                        <td>
                                            <a class="btn btn-warning" href="{{ route('comunas.edit', $comuna->id) }}">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                            <form action="{{ route('comunas.destroy', $comuna->id) }}" method="POST" style="display:inline">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-danger" type="submit" onclick="return confirm('¿Seguro que deseas eliminar esta Comuna?')">
                                                    <i class="fas fa-trash-alt"></i> Eliminar
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
        @endforeach
    </div>
</div>
@endsection

