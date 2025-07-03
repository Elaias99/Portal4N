@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">Áreas Registradas</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @foreach($areas as $area)
        <div class="card shadow-sm mb-4 p-4">
            <h5 class="mb-3 text-primary">{{ $area->nombre }}</h5>

            <p class="fw-bold mb-1">Trabajadores en esta área:</p>

            @if($area->trabajadores->isEmpty())
                <p class="text-muted">No hay trabajadores asignados a esta área.</p>
            @else
                <ul class="list-unstyled ms-3">
                    @foreach($area->trabajadores as $trabajador)
                        <li class="d-flex justify-content-between align-items-center mb-1">
                            <span>
                                • {{ $trabajador->Nombre }} {{ $trabajador->ApellidoPaterno }} {{ $trabajador->ApellidoMaterno }}
                            </span>
                            <form action="{{ route('areas.quitar', [$area->id, $trabajador->id]) }}" method="POST" class="ms-2">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger">Quitar</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif

            <hr>

            @if($trabajadores->whereNull('area_id')->count() > 0)
                <form action="{{ route('areas.asignar', $area->id) }}" method="POST" class="row g-2 mt-3 align-items-end">
                    @csrf
                    <div class="col-md-8">
                        <label for="trabajador_id_{{ $area->id }}" class="form-label">Asignar nuevo trabajador</label>
                        <select name="trabajador_id" id="trabajador_id_{{ $area->id }}" class="form-select" required>
                            <option value="">-- Seleccionar trabajador --</option>
                            @foreach($trabajadores->whereNull('area_id') as $trabajador)
                                <option value="{{ $trabajador->id }}">
                                    {{ $trabajador->Nombre }} {{ $trabajador->ApellidoPaterno }} {{ $trabajador->ApellidoMaterno }}
                                </option>
                            @endforeach
                        </select>
                        @error('trabajador_id')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <button type="submit" class="btn btn-success w-100 fw-semibold shadow-sm">
                            Asignar a {{ $area->nombre }}
                        </button>
                    </div>
                </form>
            @else
                <p class="text-muted mt-3">Todos los trabajadores ya están asignados a un área.</p>
            @endif
        </div>
    @endforeach




</div>
@endsection
