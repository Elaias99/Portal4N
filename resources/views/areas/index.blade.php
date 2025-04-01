@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Áreas Registradas</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- <a href="{{ route('areas.create') }}" class="btn btn-primary mb-3">Nueva Área</a> --}}

    <table class="table">
        {{-- <thead>
            <tr>
                <th>Nombre</th>
                <th>Acciones</th>
            </tr>
        </thead> --}}


        <tbody>
            @foreach($areas as $area)
                <div class="card shadow-sm mb-4 p-4">
                    {{-- <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0">{{ $area->nombre }}</h5>
                        <a href="{{ route('areas.edit', $area->id) }}" class="btn btn-sm btn-warning">Editar</a>
                    </div> --}}

                    <p class="fw-bold mb-1">Trabajadores en esta área:</p>

                    @if($area->trabajadores->isEmpty())
                        <p class="text-muted">No hay trabajadores asignados a esta área.</p>
                    @else
                        <ul class="list-unstyled ms-3">
                            @foreach($area->trabajadores as $trabajador)
                                <li>• {{ $trabajador->Nombre }} {{ $trabajador->ApellidoPaterno }} {{ $trabajador->ApellidoMaterno }}</li>
                            @endforeach
                        </ul>
                    @endif

                    <form action="{{ route('areas.asignar', $area->id) }}" method="POST" class="row g-2 mt-3 align-items-end">
                        @csrf
                        <div class="col-md-8">
                            <select name="trabajador_id" class="form-select" required>
                                <option value="">-- Seleccionar trabajador --</option>
                                @foreach($trabajadores->whereNull('area_id') as $trabajador)
                                    <option value="{{ $trabajador->id }}">
                                        {{ $trabajador->Nombre }} {{ $trabajador->ApellidoPaterno }} {{ $trabajador->ApellidoMaterno }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <br>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-success w-100 fw-semibold shadow-sm">
                                Asignar a {{ $area->nombre }}
                            </button>
                            
                        </div>
                    </form>
                </div>
            @endforeach

        </tbody>

       

        



    </table>
</div>
@endsection
