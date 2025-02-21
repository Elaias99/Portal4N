{{-- <div class="form-group">
    <label for="Nombre">Nombre:</label>
    <input type="text" name="Nombre" class="form-control" value="{{ old('Nombre', $cargo->Nombre ?? '') }}" required>
</div> --}}





<div class="form-group">
    <label for="Nombre">Nombre:</label>
    <input type="text" name="Nombre" class="form-control" value="{{ old('Nombre', $cargo->Nombre ?? '') }}" required>
    
    @if ($errors->has('Nombre'))
        <span class="text-danger">{{ $errors->first('Nombre') }}</span>
    @endif
</div>
