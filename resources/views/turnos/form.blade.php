{{-- <div class="form-group">
    <label for="nombre">Nombre del Turno</label>
    <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $turno->nombre ?? '') }}" required>
</div> --}}





<div class="form-group">
    <label for="nombre">Nombre:</label>
    <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre', $turno->nombre ?? '') }}" required>
    @error('nombre')
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror
</div>
