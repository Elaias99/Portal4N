@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Editar Abono</h5>
            <a href="{{ route('abonos.index', $documento->id) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>

        <div class="card-body">
            <form action="{{ route('abonos.update', $abono->id) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Monto --}}
                <div class="mb-3">
                    <label for="monto" class="form-label small text-muted">Monto</label>
                    <input type="number" name="monto" id="monto"
                           class="form-control form-control-sm @error('monto') is-invalid @enderror"
                           value="{{ old('monto', $abono->monto) }}" min="1" required>

                    @error('monto')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong class="text-danger">{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                {{-- Fecha del Abono --}}
                <div class="mb-3">
                    <label for="fecha_abono" class="form-label small text-muted">Fecha del Abono</label>
                    <input type="date" name="fecha_abono" id="fecha_abono"
                           class="form-control form-control-sm @error('fecha_abono') is-invalid @enderror"
                           value="{{ old('fecha_abono', \Carbon\Carbon::parse($abono->fecha_abono)->format('Y-m-d')) }}" required>

                    @error('fecha_abono')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong class="text-danger">{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('abonos.index', $documento->id) }}" class="btn btn-outline-secondary btn-sm">
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
