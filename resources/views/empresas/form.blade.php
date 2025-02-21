{{-- <div class="form-group">
    <label for="Nombre">Nombre:</label>
    <input type="text" name="Nombre" class="form-control" value="{{ old('Nombre', $empresa->Nombre ?? '') }}" required>
</div>





<div class="form-group">
    <label for="logo">Logo de la Empresa</label>
    <input type="file" name="logo" class="form-control">
    @if(isset($empresa) && $empresa->logo)
        <img src="{{ asset('storage/' . $empresa->logo) }}" alt="Logo de {{ $empresa->Nombre }}" style="max-height: 100px; margin-top: 10px;">
    @endif
</div> --}}



<div class="form-group">
    <label for="Nombre">Nombre:</label>
    <input type="text" name="Nombre" class="form-control @error('Nombre') is-invalid @enderror" value="{{ old('Nombre', $empresa->Nombre ?? '') }}" required>
    @error('Nombre')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="logo">Logo de la Empresa</label>
    <input type="file" name="logo" class="form-control @error('logo') is-invalid @enderror">
    @if(isset($empresa) && $empresa->logo)
        <img src="{{ asset('storage/' . $empresa->logo) }}" alt="Logo de {{ $empresa->Nombre }}" style="max-height: 100px; margin-top: 10px;">
    @endif
    @error('logo')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
