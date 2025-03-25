@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Áreas Registradas</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <a href="{{ route('areas.create') }}" class="btn btn-primary mb-3">Nueva Área</a>

    <table class="table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Acciones</th>
            </tr>
        </thead>


        <tbody>
            @foreach($areas as $area)
                <tr>
                    <td>{{ $area->nombre }}</td>
                    <td>
                        <a href="{{ route('areas.edit', $area->id) }}" class="btn btn-sm btn-warning">Editar</a>
                    </td>
                </tr>
        
                <tr>
                    <td colspan="2">
                        <strong>Trabajadores en esta área:</strong>
                        @if($area->trabajadores->isEmpty())
                            <p class="text-muted">No hay trabajadores asignados a esta área.</p>
                        @else
                            <ul>
                                @foreach($area->trabajadores as $trabajador)
                                    <li>{{ $trabajador->Nombre }} {{ $trabajador->ApellidoPaterno }} {{ $trabajador->ApellidoMaterno }}</li>
                                @endforeach
                            </ul>
                        @endif

                        <form action="{{ route('areas.asignar', $area->id) }}" method="POST" class="mt-3">
                            @csrf
                            <div class="row g-2 align-items-center">
                                <div class="col-md-8">
                                    <select name="trabajador_id" class="form-select">
                                        <option value="">-- Seleccionar trabajador --</option>
                                        @foreach($trabajadores->whereNull('area_id') as $trabajador)
                                            <option value="{{ $trabajador->id }}">
                                                {{ $trabajador->Nombre }} {{ $trabajador->ApellidoPaterno }} {{ $trabajador->ApellidoMaterno }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-success">Asignar a {{ $area->nombre }}</button>
                                </div>
                            </div>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>

        <hr>


        



    </table>
</div>
@endsection
