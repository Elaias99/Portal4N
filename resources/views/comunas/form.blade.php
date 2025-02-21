{{-- <div class="form-group">
    <label for="Nombre">{{ 'Nombre' }}</label>
    <input type="text" name="Nombre" id="Nombre" value="{{ isset($comuna->Nombre) ? $comuna->Nombre : old('Nombre') }}" class="form-control" required>
</div>
<div class="form-group">
    <label for="region_id">{{ 'Región' }}</label>
    <select name="region_id" id="region_id" class="form-control" required>
        @foreach ($regions as $region)
            <option value="{{ $region->id }}" {{ isset($comuna->region_id) && $comuna->region_id == $region->id ? 'selected' : '' }}>{{ $region->Nombre }}</option>
        @endforeach
    </select>
</div> --}}







<div class="form-group">
    <label for="Nombre">{{ 'Nombre' }}</label>
    <input type="text" name="Nombre" id="Nombre" value="{{ old('Nombre', $comuna->Nombre ?? '') }}" class="form-control @error('Nombre') is-invalid @enderror" required>
    @error('Nombre')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="region_id">{{ 'Región' }}</label>
    <select name="region_id" id="region_id" class="form-control @error('region_id') is-invalid @enderror" required>
        @foreach ($regions as $region)
            <option value="{{ $region->id }}" {{ old('region_id', $comuna->region_id ?? '') == $region->id ? 'selected' : '' }}>{{ $region->Nombre }}</option>
        @endforeach
    </select>
    @error('region_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
