@csrf

<div class="mb-3">
    <label for="rut_cliente" class="form-label">RUT Cliente</label>
    <input type="text" name="rut_cliente" id="rut_cliente" 
           class="form-control @error('rut_cliente') is-invalid @enderror" 
           value="{{ old('rut_cliente', $cobranzaCompra->rut_cliente ?? '') }}" required>
    @error('rut_cliente')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="razon_social" class="form-label">Razón Social</label>
    <input type="text" name="razon_social" id="razon_social" 
           class="form-control @error('razon_social') is-invalid @enderror" 
           value="{{ old('razon_social', $cobranzaCompra->razon_social ?? '') }}" required>
    @error('razon_social')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="servicio" class="form-label">Servicio</label>
    <input type="text" name="servicio" id="servicio" 
           class="form-control @error('servicio') is-invalid @enderror" 
           value="{{ old('servicio', $cobranzaCompra->servicio ?? '') }}" required>
    @error('servicio')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="creditos" class="form-label">Créditos (días)</label>
    <input type="number" name="creditos" id="creditos" min="0"
           class="form-control @error('creditos') is-invalid @enderror" 
           value="{{ old('creditos', $cobranzaCompra->creditos ?? '') }}" required>
    @error('creditos')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="d-flex justify-content-end">
    <a href="{{ route('cobranzas-compras.index') }}" class="btn btn-secondary me-2">Cancelar</a>
    <button type="submit" class="btn btn-primary">{{ $btnText }}</button>
</div>
