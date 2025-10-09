@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Editar Cruce</h5>
            <a href="{{ route('cruces.index', $documento->id) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>

        <div class="card-body">
            <form action="{{ route('cruces.update', $cruce->id) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Monto --}}
                <div class="mb-3">
                    <label for="monto" class="form-label small text-muted">Monto</label>
                    <input type="number" name="monto" id="monto"
                           class="form-control form-control-sm @error('monto') is-invalid @enderror"
                           value="{{ old('monto', $cruce->monto) }}" min="1" required>

                    @error('monto')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong class="text-danger">{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                {{-- Fecha del Cruce --}}
                <div class="mb-3">
                    <label for="fecha_cruce" class="form-label small text-muted">Fecha del Cruce</label>
                    <input type="date" name="fecha_cruce" id="fecha_cruce"
                           class="form-control form-control-sm @error('fecha_cruce') is-invalid @enderror"
                           value="{{ old('fecha_cruce', \Carbon\Carbon::parse($cruce->fecha_cruce)->format('Y-m-d')) }}" required>

                    @error('fecha_cruce')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong class="text-danger">{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('cruces.index', $documento->id) }}" class="btn btn-outline-secondary btn-sm">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-save"></i> Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
