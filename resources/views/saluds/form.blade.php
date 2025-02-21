{{-- <div class="form-group">
    <label for="Nombre">Nombre:</label>
    <input type="text" name="Nombre" class="form-control" value="{{ old('Nombre', $salud->Nombre ?? '') }}" required>
</div> --}}





<div class="form-group">
    <label for="Nombre">Nombre:</label>
    <input type="text" name="Nombre" class="form-control @error('Nombre') is-invalid @enderror" value="{{ old('Nombre', $salud->Nombre ?? '') }}" required>
    @error('Nombre')
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror
</div>
