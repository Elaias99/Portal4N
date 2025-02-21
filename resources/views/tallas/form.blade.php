<div class="form-group">
    <label for="trabajador_id">Trabajador</label>
    <select name="trabajador_id" class="form-control">
        @foreach($trabajadores as $trabajador)
            <option value="{{ $trabajador->id }}" {{ isset($talla) && $talla->trabajador_id == $trabajador->id ? 'selected' : '' }}>{{ $trabajador->Nombre }}</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="tipo_vestimenta_id">Tipo de Vestimenta</label>
    <select name="tipo_vestimenta_id" class="form-control">
        @foreach($tiposVestimenta as $tipoVestimenta)
            <option value="{{ $tipoVestimenta->id }}" {{ isset($talla) && $talla->tipo_vestimenta_id == $tipoVestimenta->id ? 'selected' : '' }}>{{ $tipoVestimenta->Nombre }}</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="talla">Talla</label>
    <input type="text" name="talla" class="form-control" value="{{ isset($talla) ? $talla->talla : '' }}">
</div>


<a href="{{ url('tallas') }}" class="btn btn-secondary">Atrás</a>






