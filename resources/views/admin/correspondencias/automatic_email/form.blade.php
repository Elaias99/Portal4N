<div class="mb-3">
    <label class="form-label">Nombre</label>
    <input type="text" name="nombre" class="form-control" 
           value="{{ old('nombre', $email->nombre ?? '') }}" required>
</div>

<div class="mb-3">
    <label class="form-label">Asunto</label>
    <input type="text" name="asunto" class="form-control" 
           value="{{ old('asunto', $email->asunto ?? '') }}" required>
</div>

<div class="mb-3">
    <label class="form-label">Destinatarios</label>
    <textarea name="destinatarios" class="form-control" rows="2" required>
        {{ old('destinatarios', $email->destinatarios ?? '') }}
    </textarea>
    <small class="text-muted">Separar con comas: correo1@x.com, correo2@y.com</small>
</div>

<div class="mb-3">
    <label class="form-label">Cuerpo HTML</label>
    <textarea name="cuerpo_html" class="form-control" rows="8" required>
        {{ old('cuerpo_html', $email->cuerpo_html ?? '') }}
    </textarea>
</div>

<div class="mb-3">
    <label class="form-label">Frecuencia</label>
    <select name="tipo_frecuencia" class="form-select" required>
        <option value="diario"   {{ old('tipo_frecuencia', $email->tipo_frecuencia ?? '') == 'diario' ? 'selected' : '' }}>Diario</option>
        <option value="semanal"  {{ old('tipo_frecuencia', $email->tipo_frecuencia ?? '') == 'semanal' ? 'selected' : '' }}>Semanal</option>
        <option value="mensual"  {{ old('tipo_frecuencia', $email->tipo_frecuencia ?? '') == 'mensual' ? 'selected' : '' }}>Mensual</option>
    </select>
</div>

<div class="mb-3">
    <label class="form-label">Hora</label>
    <input type="time" name="hora_envio" value="{{ old('hora_envio', $email->hora_envio ?? '') }}" class="form-control">
</div>

<div class="mb-3">
    <label class="form-label">Días de la semana (si es semanal)</label>


    @php
        $dias = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            0 => 'Domingo',
        ];
    @endphp

    @foreach ($dias as $valor => $label)
        <label class="me-2">
            <input type="checkbox" name="dias_semana[]" value="{{ $valor }}"
                {{ in_array($valor, $seleccion ?? []) ? 'checked' : '' }}>
            {{ $label }}
        </label>
    @endforeach




</div>

<div class="mb-3">
    <label class="form-label">Activo</label>
    <select name="activo" class="form-select" required>
        <option value="1" {{ old('activo', $email->activo ?? '') == 1 ? 'selected' : '' }}>Sí</option>
        <option value="0" {{ old('activo', $email->activo ?? '') == 0 ? 'selected' : '' }}>No</option>
    </select>
</div>
