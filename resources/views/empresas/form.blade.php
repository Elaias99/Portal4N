{{-- Nombre --}}
<div class="form-group">
    <label for="Nombre">Nombre:</label>
    <input type="text" name="Nombre" class="form-control @error('Nombre') is-invalid @enderror" value="{{ old('Nombre', $empresa->Nombre ?? '') }}" required>
    @error('Nombre')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- RUT --}}
<div class="form-group">
    <label for="rut">RUT:</label>
    <input type="text" name="rut" class="form-control @error('rut') is-invalid @enderror" value="{{ old('rut', $empresa->rut ?? '') }}">
    @error('rut')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- Giro --}}
<div class="form-group">
    <label for="giro">Giro:</label>
    <input type="text" name="giro" class="form-control @error('giro') is-invalid @enderror" value="{{ old('giro', $empresa->giro ?? '') }}" required>
    @error('giro')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- Dirección --}}
<div class="form-group">
    <label for="direccion">Dirección:</label>
    <input type="text" name="direccion" class="form-control @error('direccion') is-invalid @enderror" value="{{ old('direccion', $empresa->direccion ?? '') }}" required>
    @error('direccion')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- Cuenta Corriente --}}
<div class="form-group">
    <label for="cta_corriente">Cuenta Corriente:</label>
    <input type="text" name="cta_corriente" class="form-control @error('cta_corriente') is-invalid @enderror" value="{{ old('cta_corriente', $empresa->cta_corriente ?? '') }}" required>
    @error('cta_corriente')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- Correo Formalizado --}}
<div class="form-group">
    <label for="mail_formalizado">Correo Formalizado:</label>
    <input type="email" name="mail_formalizado" class="form-control @error('mail_formalizado') is-invalid @enderror" value="{{ old('mail_formalizado', $empresa->mail_formalizado ?? '') }}" required>
    @error('mail_formalizado')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- Banco --}}
<div class="form-group">
    <label for="banco_id">Banco:</label>
    <select name="banco_id" class="form-control @error('banco_id') is-invalid @enderror">
        <option value="">Seleccione un banco</option>
        @foreach($bancos as $banco)
            <option value="{{ $banco->id }}" {{ old('banco_id', $empresa->banco_id ?? '') == $banco->id ? 'selected' : '' }}>
                {{ $banco->nombre }}
            </option>
        @endforeach
    </select>
    @error('banco_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- Comuna --}}
<div class="form-group">
    <label for="comuna_id">Comuna:</label>
    <select name="comuna_id" class="form-control @error('comuna_id') is-invalid @enderror">
        <option value="">Seleccione una comuna</option>
        @foreach($comunas as $comuna)
            <option value="{{ $comuna->id }}" {{ old('comuna_id', $empresa->comuna_id ?? '') == $comuna->id ? 'selected' : '' }}>
                {{ $comuna->Nombre }}
            </option>
        @endforeach
    </select>
    @error('comuna_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- Logo --}}
<div class="form-group">
    <label for="logo">Logo de la Empresa</label>
    <input type="file" name="logo" id="logo" class="form-control @error('logo') is-invalid @enderror">
    
    @if(isset($empresa) && $empresa->logo)
        <img src="{{ asset($empresa->logo) }}" alt="Logo de {{ $empresa->Nombre }}" style="max-height: 100px; margin-top: 10px;">
    @endif
    
    @error('logo')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>