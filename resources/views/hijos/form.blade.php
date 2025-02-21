{{-- <div class="form-group">
    <label for="nombre">Nombre</label>
    <input type="text" name="nombre" id="nombre" class="form-control" value="{{ isset($hijo) ? $hijo->nombre : old('nombre') }}">
</div>

<div class="form-group">
    <label for="genero">Género</label>
    <select name="genero" id="genero" class="form-control">
        <option value="Masculino" {{ (isset($hijo) && $hijo->genero == 'Masculino') ? 'selected' : '' }}>Masculino</option>
        <option value="Femenino" {{ (isset($hijo) && $hijo->genero == 'Femenino') ? 'selected' : '' }}>Femenino</option>
    </select>
</div>

<div class="form-group">
    <label for="parentesco">Parentesco</label>
    <input type="text" name="parentesco" id="parentesco" class="form-control" value="{{ isset($hijo) ? $hijo->parentesco : old('parentesco') }}">
</div>

<div class="form-group">
    <label for="fecha_nacimiento">Fecha de Nacimiento</label>
    <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" class="form-control" value="{{ isset($hijo) ? $hijo->fecha_nacimiento : old('fecha_nacimiento') }}">
</div>

<div class="form-group">
    <label for="edad">Edad</label>
    <input type="number" name="edad" id="edad" class="form-control" value="{{ isset($hijo) ? $hijo->edad : old('edad') }}">
</div>

<div class="form-group">
    <label for="trabajador_id">Trabajador</label>
    <select name="trabajador_id" id="trabajador_id" class="form-control">
        @foreach ($trabajadores as $trabajador)
            <option value="{{ $trabajador->id }}" {{ (isset($hijo) && $hijo->trabajador_id == $trabajador->id) ? 'selected' : '' }}>{{ $trabajador->Nombre }}</option>
        @endforeach
    </select>
</div>

<button type="submit" class="btn btn-primary">{{ $modo }} Hijo</button>
<a href="{{ url('hijos') }}" class="btn btn-secondary">Atrás</a> --}}










<div id="hijos-container">
    <div class="hijo-form">



        <div class="form-group mt-3">
            <label for="trabajador_id">Trabajador</label>
            <select name="trabajador_id" id="trabajador_id" class="form-control">
                @foreach ($trabajadores as $trabajador)
                    <option value="{{ $trabajador->id }}" {{ (isset($hijo) && $hijo->trabajador_id == $trabajador->id) ? 'selected' : '' }}>{{ $trabajador->Nombre }}</option>
                @endforeach
            </select>
        </div>




        <div class="form-group">
            <label for="hijos[0][nombre]">Nombre</label>
            <input type="text" name="hijos[0][nombre]" id="hijos[0][nombre]" class="form-control" value="{{ isset($hijo) ? $hijo->nombre : old('hijos.0.nombre') }}">
        </div>

        <div class="form-group">
            <label for="hijos[0][genero]">Género</label>
            <select name="hijos[0][genero]" id="hijos[0][genero]" class="form-control">
                <option value="Masculino" {{ (isset($hijo) && $hijo->genero == 'Masculino') ? 'selected' : '' }}>Masculino</option>
                <option value="Femenino" {{ (isset($hijo) && $hijo->genero == 'Femenino') ? 'selected' : '' }}>Femenino</option>
            </select>
        </div>

        <div class="form-group">
            <label for="hijos[0][parentesco]">Parentesco</label>
            <input type="text" name="hijos[0][parentesco]" id="hijos[0][parentesco]" class="form-control" value="{{ isset($hijo) ? $hijo->parentesco : old('hijos.0.parentesco') }}">
        </div>

        <div class="form-group">
            <label for="hijos[0][fecha_nacimiento]">Fecha de Nacimiento</label>
            <input type="date" name="hijos[0][fecha_nacimiento]" id="hijos[0][fecha_nacimiento]" class="form-control" value="{{ isset($hijo) ? $hijo->fecha_nacimiento : old('hijos.0.fecha_nacimiento') }}">
        </div>

        <div class="form-group">
            <label for="hijos[0][edad]">Edad</label>
            <input type="number" name="hijos[0][edad]" id="hijos[0][edad]" class="form-control" value="{{ isset($hijo) ? $hijo->edad : old('hijos.0.edad') }}">
        </div>
    </div>
</div>

<button type="button" id="add-hijo" class="btn btn-success mt-3">Añadir Hijo</button>



<button type="submit" class="btn btn-primary">{{ $modo }} Hijo</button>
<a href="{{ url('hijos') }}" class="btn btn-secondary">Atrás</a>

<script>
    document.getElementById('add-hijo').addEventListener('click', function() {
        var container = document.getElementById('hijos-container');
        var index = container.getElementsByClassName('hijo-form').length;
        var template = `
            <div class="hijo-form">
                <div class="form-group">
                    <label for="hijos[${index}][nombre]">Nombre</label>
                    <input type="text" name="hijos[${index}][nombre]" id="hijos[${index}][nombre]" class="form-control">
                </div>

                <div class="form-group">
                    <label for="hijos[${index}][genero]">Género</label>
                    <select name="hijos[${index}][genero]" id="hijos[${index}][genero]" class="form-control">
                        <option value="Masculino">Masculino</option>
                        <option value="Femenino">Femenino</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="hijos[${index}][parentesco]">Parentesco</label>
                    <input type="text" name="hijos[${index}][parentesco]" id="hijos[${index}][parentesco]" class="form-control">
                </div>

                <div class="form-group">
                    <label for="hijos[${index}][fecha_nacimiento]">Fecha de Nacimiento</label>
                    <input type="date" name="hijos[${index}][fecha_nacimiento]" id="hijos[${index}][fecha_nacimiento]" class="form-control">
                </div>

                <div class="form-group">
                    <label for="hijos[${index}][edad]">Edad</label>
                    <input type="number" name="hijos[${index}][edad]" id="hijos[${index}][edad]" class="form-control">
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', template);
    });
</script>
