{{-- <div class="form-group">
    <label for="nombre">Nombre del Sistema de Trabajo</label>
    <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $sistemaTrabajo->nombre ?? '') }}" class="form-control" required>
</div> --}}





<div class="form-group">
    <label for="nombre">Nombre:</label>
    <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre', $sistemaTrabajo->nombre ?? '') }}" required>
    @error('nombre')
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror
</div>
