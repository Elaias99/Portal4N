{{-- Formulario para crear o editar una región --}}
{{-- <div class="form-group">
    <label for="Nombre">{{ 'Nombre' }}</label>
    <input type="text" name="Nombre" id="Nombre" value="{{ isset($region->Nombre) ? $region->Nombre : old('Nombre') }}" class="form-control" required>
</div>
<div class="form-group">
    <label for="Numero">{{ 'Número' }}</label>
    <input type="number" name="Numero" id="Numero" value="{{ isset($region->Numero) ? $region->Numero : old('Numero') }}" class="form-control" required>
</div>
<button type="submit" class="btn btn-primary">{{ $modo }} Región</button>
<a href="{{ route('regions.index') }}" class="btn btn-secondary">Atrás</a> --}}



{{-- Formulario para crear o editar una región --}}
<div class="form-group">
    <label for="Nombre">{{ 'Nombre' }}</label>
    <input type="text" name="Nombre" id="Nombre" value="{{ old('Nombre', $region->Nombre ?? '') }}" class="form-control @error('Nombre') is-invalid @enderror" required>
    @error('Nombre')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="Numero">{{ 'Número' }}</label>
    <input type="number" name="Numero" id="Numero" value="{{ old('Numero', $region->Numero ?? '') }}" class="form-control @error('Numero') is-invalid @enderror" required>
    @error('Numero')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<button type="submit" class="btn btn-primary">{{ $modo }} Región</button>
<a href="{{ route('regions.index') }}" class="btn btn-secondary">Atrás</a>
