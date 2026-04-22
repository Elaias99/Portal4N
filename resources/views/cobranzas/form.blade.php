@php
    $formIdPrefix = $formIdPrefix ?? 'cobranza';
    $isModalFlow = $isModalFlow ?? false;
@endphp

@csrf

<div class="mb-3">
    <label for="{{ $formIdPrefix }}_rut_cliente" class="form-label">RUT Cliente</label>
    <input
        type="text"
        name="rut_cliente"
        id="{{ $formIdPrefix }}_rut_cliente"
        class="form-control @error('rut_cliente') is-invalid @enderror"
        value="{{ old('rut_cliente', $cobranza->rut_cliente ?? '') }}"
        required
    >
    @error('rut_cliente')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="{{ $formIdPrefix }}_razon_social" class="form-label">Razón Social</label>
    <input
        type="text"
        name="razon_social"
        id="{{ $formIdPrefix }}_razon_social"
        class="form-control @error('razon_social') is-invalid @enderror"
        value="{{ old('razon_social', $cobranza->razon_social ?? '') }}"
        required
    >
    @error('razon_social')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="{{ $formIdPrefix }}_servicio" class="form-label">Servicio</label>
    <input
        type="text"
        name="servicio"
        id="{{ $formIdPrefix }}_servicio"
        class="form-control @error('servicio') is-invalid @enderror"
        value="{{ old('servicio', $cobranza->servicio ?? '') }}"
        required
    >
    @error('servicio')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="{{ $formIdPrefix }}_creditos" class="form-label">Créditos (días)</label>
    <input
        type="number"
        name="creditos"
        id="{{ $formIdPrefix }}_creditos"
        min="0"
        class="form-control @error('creditos') is-invalid @enderror"
        value="{{ old('creditos', $cobranza->creditos ?? '') }}"
        required
    >
    @error('creditos')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="d-flex justify-content-end">
    @if($isModalFlow)
        <button type="button" class="btn btn-secondary me-2 js-cancel-cobranza-modal">Cancelar</button>
    @else
        <a href="{{ route('cobranzas.index') }}" class="btn btn-secondary me-2">Cancelar</a>
    @endif

    <button type="submit" class="btn btn-primary">{{ $btnText }}</button>
</div>