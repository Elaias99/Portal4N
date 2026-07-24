@csrf

<div class="mb-3">
    <label for="Nombre" class="form-label">
        Nombre
    </label>

    <input
        type="text"
        name="Nombre"
        id="Nombre"
        class="form-control @error('Nombre') is-invalid @enderror"
        value="{{ old('Nombre', $almacenamientoBodega->Nombre ?? '') }}"
        required
    >

    @error('Nombre')
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror
</div>

<div class="mb-3">
    <label for="precio" class="form-label">
        Precio
    </label>

    <input
        type="text"
        name="precio"
        id="precio"
        class="form-control @error('precio') is-invalid @enderror"
        value="{{ old('precio', $almacenamientoBodega->precio ?? '') }}"
        required
    >

    @error('precio')
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror
</div>

<div class="mb-3">
    <label for="cantidad" class="form-label">
        Cantidad
    </label>

    <input
        type="text"
        name="cantidad"
        id="cantidad"
        class="form-control @error('cantidad') is-invalid @enderror"
        value="{{ old('cantidad', $almacenamientoBodega->cantidad ?? '') }}"
        required
    >

    @error('cantidad')
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror
</div>

<div class="mb-3">
    <label for="descripcion" class="form-label">
        Descripción
    </label>

    <textarea
        name="descripcion"
        id="descripcion"
        rows="4"
        class="form-control @error('descripcion') is-invalid @enderror"
        required
    >{{ old('descripcion', $almacenamientoBodega->descripcion ?? '') }}</textarea>

    @error('descripcion')
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        {{ $btnText }}
    </button>

    <a href="{{ route('almacenamiento.index') }}" class="btn btn-secondary">
        Cancelar
    </a>
</div>