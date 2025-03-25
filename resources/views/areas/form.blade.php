<div class="mb-3">
    <label for="nombre" class="form-label">Nombre del Área</label>
    <input type="text" name="nombre" id="nombre" class="form-control" value="{{ old('nombre', $area->nombre) }}">
    @error('nombre')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<button type="submit" class="btn btn-success">Guardar</button>
