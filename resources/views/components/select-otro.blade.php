<div>
    <label for="{{ $name }}">{!! $label !!}</label>

    <select name="{{ $name }}" id="{{ $name }}" class="form-control" onchange="mostrarCampoOtro('{{ $name }}')">
        @foreach ($options as $option)
            <option value="{{ $option->id }}" {{ old($name, $selected ?? '') == $option->id ? 'selected' : '' }}>
                {{ $option->Nombre ?? $option->nombre }} <!-- Mostrar Nombre o nombre -->
            </option>
        @endforeach
        <option value="otro">Otro</option> <!-- Opción para agregar nuevo -->
    </select>

    <!-- Campo para ingresar el nuevo valor -->
    <div id="nuevo_{{ 
        $name == 'salud_id' ? 'salud' : 
        ($name == 'cargo_id' ? 'cargo' : 
        ($name == 'estado_civil_id' ? 'estado_civil' : 
        ($name == 'turno_id' ? 'turno' : 
        ($name == 'sistema_trabajo_id' ? 'sistema_trabajo' : 
        ($name == 'situacion_id' ? 'situacion' : 
        ($name == 'afp_id' ? 'afp' : $name)))))) 
    }}" style="display: none;">
        <label for="nuevo_{{ 
            $name == 'salud_id' ? 'salud' : 
            ($name == 'cargo_id' ? 'cargo' : 
            ($name == 'estado_civil_id' ? 'estado_civil' : 
            ($name == 'turno_id' ? 'turno' : 
            ($name == 'sistema_trabajo_id' ? 'sistema_trabajo' : 
            ($name == 'situacion_id' ? 'situacion' : 
            ($name == 'afp_id' ? 'afp' : $name)))))) 
        }}">Nuevo {{ $label }}</label>
        <input type="text" name="nuevo_{{ 
            $name == 'salud_id' ? 'salud' : 
            ($name == 'cargo_id' ? 'cargo' : 
            ($name == 'estado_civil_id' ? 'estado_civil' : 
            ($name == 'turno_id' ? 'turno' : 
            ($name == 'sistema_trabajo_id' ? 'sistema_trabajo' : 
            ($name == 'situacion_id' ? 'situacion' : 
            ($name == 'afp_id' ? 'afp' : $name)))))) 
        }}" id="nuevo_{{ 
            $name == 'salud_id' ? 'salud' : 
            ($name == 'cargo_id' ? 'cargo' : 
            ($name == 'estado_civil_id' ? 'estado_civil' : 
            ($name == 'turno_id' ? 'turno' : 
            ($name == 'sistema_trabajo_id' ? 'sistema_trabajo' : 
            ($name == 'situacion_id' ? 'situacion' : 
            ($name == 'afp_id' ? 'afp' : $name)))))) 
        }}" class="form-control" placeholder="Ingrese nuevo {{ $label }}">

        <!-- Campos adicionales para AFP -->
        @if ($name == 'afp_id')
            <div class="form-group">
                <label for="tasa_cotizacion">Tasa de Cotización (%):</label>
                <input type="number" name="tasa_cotizacion" class="form-control" id="tasa_cotizacion" step="0.01" placeholder="Ingrese la tasa de cotización">
            </div>

            <div class="form-group">
                <label for="tasa_sis">Tasa SIS (%):</label>
                <input type="number" name="tasa_sis" class="form-control" id="tasa_sis" step="0.01" placeholder="Ingrese la tasa SIS">
            </div>
        @endif
    </div>
</div>

<script>
    function mostrarCampoOtro(name) {
        var nuevoCampoId = 'nuevo_' + (
            name === 'salud_id' ? 'salud' : 
            (name === 'cargo_id' ? 'cargo' : 
            (name === 'estado_civil_id' ? 'estado_civil' : 
            (name === 'turno_id' ? 'turno' : 
            (name === 'sistema_trabajo_id' ? 'sistema_trabajo' : 
            (name === 'situacion_id' ? 'situacion' : 
            (name === 'afp_id' ? 'afp' : name))))))
        );
        var select = document.getElementById(name);
        var nuevoCampo = document.getElementById(nuevoCampoId);
        nuevoCampo.style.display = select.value === 'otro' ? 'block' : 'none';
    }
</script>





