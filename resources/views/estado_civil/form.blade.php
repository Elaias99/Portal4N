<!-- resources/views/estado_civil/form.blade.php -->
<div class="form-group">
    <label for="Nombre">Nombre</label>
    <input type="text" class="form-control @error('Nombre') is-invalid @enderror" id="Nombre" name="Nombre" value="{{ old('Nombre', $estadoCivil->Nombre ?? '') }}" required>
    @error('Nombre')
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror
</div>

<!-- Botones de acción -->
<div class="mt-4">
    {{-- <button type="submit" class="btn btn-primary">Guardar</button> --}}
    <a href="{{ route('estado_civil.index') }}" class="btn btn-secondary">Volver al índice</a>
</div>
