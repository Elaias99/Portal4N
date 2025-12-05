@csrf

{{-- ========================================================= --}}
{{-- DATOS BASE --}}
{{-- ========================================================= --}}

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
    <label for="servicio" class="form-label">Servicio / Detalle</label>
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

{{-- ========================================================= --}}
{{-- NUEVOS CAMPOS (DEL EXCEL) --}}
{{-- ========================================================= --}}

<div class="mb-3">
    <label class="form-label">Tipo</label>
    <input type="text" name="tipo"
           class="form-control @error('tipo') is-invalid @enderror"
           value="{{ old('tipo', $cobranzaCompra->tipo ?? '') }}" required>
    @error('tipo')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">Facturación</label>
    <input type="text" name="facturacion"
           class="form-control @error('facturacion') is-invalid @enderror"
           value="{{ old('facturacion', $cobranzaCompra->facturacion ?? '') }}" required>
    @error('facturacion')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">Forma de Pago</label>
    <input type="text" name="forma_pago"
           class="form-control @error('forma_pago') is-invalid @enderror"
           value="{{ old('forma_pago', $cobranzaCompra->forma_pago ?? '') }}" required>
    @error('forma_pago')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">Zona</label>
    <input type="text" name="zona"
           class="form-control @error('zona') is-invalid @enderror"
           value="{{ old('zona', $cobranzaCompra->zona ?? '') }}" required>
    @error('zona')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">Importancia</label>
    <input type="text" name="importancia"
           class="form-control @error('importancia') is-invalid @enderror"
           value="{{ old('importancia', $cobranzaCompra->importancia ?? '') }}" required>
    @error('importancia')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">Responsable</label>
    <input type="text" name="responsable"
           class="form-control @error('responsable') is-invalid @enderror"
           value="{{ old('responsable', $cobranzaCompra->responsable ?? '') }}" required>
    @error('responsable')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- ========================================================= --}}
{{-- DATOS BANCARIOS --}}
{{-- ========================================================= --}}

<div class="mb-3">
    <label class="form-label">Nombre Cuenta</label>
    <input type="text" name="nombre_cuenta"
           class="form-control @error('nombre_cuenta') is-invalid @enderror"
           value="{{ old('nombre_cuenta', $cobranzaCompra->nombre_cuenta ?? '') }}" required>
    @error('nombre_cuenta')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">RUT Cuenta</label>
    <input type="text" name="rut_cuenta"
           class="form-control @error('rut_cuenta') is-invalid @enderror"
           value="{{ old('rut_cuenta', $cobranzaCompra->rut_cuenta ?? '') }}" required>
    @error('rut_cuenta')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">Número Cuenta</label>
    <input type="text" name="numero_cuenta"
           class="form-control @error('numero_cuenta') is-invalid @enderror"
           value="{{ old('numero_cuenta', $cobranzaCompra->numero_cuenta ?? '') }}" required>
    @error('numero_cuenta')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- Banco --}}
<div class="mb-3">
    <label class="form-label">Banco</label>
    <select name="banco_id"
            class="form-select @error('banco_id') is-invalid @enderror" required>

        <option value="">Seleccione un banco</option>

        @foreach($bancos as $banco)
            <option value="{{ $banco->id }}"
                {{ old('banco_id', $cobranzaCompra->banco_id ?? '') == $banco->id ? 'selected' : '' }}>
                {{ $banco->nombre }}
            </option>
        @endforeach
    </select>

    @error('banco_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- Tipo Cuenta --}}
<div class="mb-3">
    <label class="form-label">Tipo de Cuenta</label>
    <select name="tipo_cuenta_id"
            class="form-select @error('tipo_cuenta_id') is-invalid @enderror" required>

        <option value="">Seleccione un tipo de cuenta</option>

        @foreach($tipoCuentas as $tipo)
            <option value="{{ $tipo->id }}"
                {{ old('tipo_cuenta_id', $cobranzaCompra->tipo_cuenta_id ?? '') == $tipo->id ? 'selected' : '' }}>
                {{ $tipo->nombre }}
            </option>
        @endforeach
    </select>

    @error('tipo_cuenta_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- ========================================================= --}}
{{-- BOTONES --}}
{{-- ========================================================= --}}

<div class="d-flex justify-content-end mt-3">
    <a href="{{ route('cobranzas-compras.index') }}" class="btn btn-secondary me-2">Cancelar</a>
    <button type="submit" class="btn btn-primary">{{ $btnText }}</button>
</div>
