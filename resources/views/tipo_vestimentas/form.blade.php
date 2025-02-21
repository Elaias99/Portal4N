{{-- <div class="form-group">
    <label for="Nombre">Nombre</label>
    <input type="text" name="Nombre" class="form-control" value="{{ isset($tipoVestimenta) ? $tipoVestimenta->Nombre : '' }}">
</div> --}}





<div class="form-group">
    <label for="Nombre">Nombre</label>
    <input type="text" name="Nombre" class="form-control @error('Nombre') is-invalid @enderror" value="{{ old('Nombre', $tipoVestimenta->Nombre ?? '') }}" required>
    @error('Nombre')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
