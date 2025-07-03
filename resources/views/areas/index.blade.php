@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Áreas Registradas</h2>
        <a href="{{ route('areas.create') }}" class="btn btn-success">

            + Nueva Área
        </a>
    </div>

    

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @foreach($areas as $area)
        <div class="card shadow-sm mb-4 p-4">
            <h5 class="mb-3 text-primary">{{ $area->nombre }}</h5>

            @php
                $trabajadoresAsignados = $area->trabajadores->merge($area->trabajadoresSecundarios)->unique('id');
            @endphp

            <p class="fw-bold mb-1">Trabajadores asignados:</p>

            @if($trabajadoresAsignados->isEmpty())
                <p class="text-muted">No hay trabajadores asignados a esta área.</p>
            @else
                <ul class="list-unstyled ms-3">
                    @foreach($trabajadoresAsignados as $trabajador)
                        <li class="d-flex justify-content-between align-items-center mb-1">
                            <span>
                                • {{ $trabajador->Nombre }} {{ $trabajador->ApellidoPaterno }} {{ $trabajador->ApellidoMaterno }}
                            </span>

                            <form 
                                action="{{ $trabajador->area_id === $area->id 
                                    ? route('areas.quitar', [$area->id, $trabajador->id]) 
                                    : route('areas.quitarSecundaria', [$area->id, $trabajador->id]) }}" 
                                method="POST" class="ms-2">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger">Quitar</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif

            <hr>

            {{-- Formulario para agregar trabajador --}}
            <form action="{{ route('areas.asignar', $area->id) }}" method="POST" class="row g-2 mt-1 align-items-end">
                @csrf
                <div class="col-md-8">
                    <label for="trabajador_id_{{ $area->id }}" class="form-label">Agregar trabajador a esta área</label>
                    <select name="trabajador_id" id="trabajador_id_{{ $area->id }}" class="form-select" required>
                        <option value="">-- Seleccionar trabajador --</option>
                        @foreach($trabajadores as $trabajador)
                            @if(
                                $trabajador->area_id !== $area->id &&
                                !$trabajador->areasSecundarias->contains($area)
                            )
                                <option value="{{ $trabajador->id }}">
                                    {{ $trabajador->Nombre }} {{ $trabajador->ApellidoPaterno }} {{ $trabajador->ApellidoMaterno }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <button type="submit" class="btn btn-outline-primary w-100 fw-semibold shadow-sm">
                        Asignar a esta área
                    </button>
                </div>
            </form>
        </div>
    @endforeach
</div>
@endsection
