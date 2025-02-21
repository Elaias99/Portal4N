{{-- <div class="form-group">
    <label for="Nombre">Nombre</label>
    <input type="text" class="form-control" id="Nombre" name="Nombre" value="{{ old('Nombre', $afp->Nombre ?? '') }}" required>
</div>

<div class="form-group">
    <label for="tasa_cotizacion">Tasa de Cotización (%):</label>
    <input type="number" name="tasa_cotizacion" class="form-control" id="tasa_cotizacion" step="0.01" value="{{ old('tasa_cotizacion', $afp->tasaAfp->tasa_cotizacion ?? '') }}" required>
</div>

<div class="form-group">
    <label for="tasa_sis">Tasa SIS (%):</label>
    <input type="number" name="tasa_sis" class="form-control" id="tasa_sis" step="0.01" value="{{ old('tasa_sis', $afp->tasaAfp->tasa_sis ?? '') }}" required>
</div> --}}





<div class="form-group">
    <label for="Nombre">Nombre</label>
    <input type="text" class="form-control @error('Nombre') is-invalid @enderror" id="Nombre" name="Nombre" value="{{ old('Nombre', $afp->Nombre ?? '') }}" required>
    @error('Nombre')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="tasa_cotizacion">Tasa de Cotización (%):</label>
    <input type="number" name="tasa_cotizacion" class="form-control @error('tasa_cotizacion') is-invalid @enderror" id="tasa_cotizacion" step="0.01" value="{{ old('tasa_cotizacion', $afp->tasaAfp->tasa_cotizacion ?? '') }}" required>
    @error('tasa_cotizacion')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="tasa_sis">Tasa SIS (%):</label>
    <input type="number" name="tasa_sis" class="form-control @error('tasa_sis') is-invalid @enderror" id="tasa_sis" step="0.01" value="{{ old('tasa_sis', $afp->tasaAfp->tasa_sis ?? '') }}" required>
    @error('tasa_sis')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
