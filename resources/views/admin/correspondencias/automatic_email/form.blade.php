{{-- ===============================
    INFORMACIÓN GENERAL
=============================== --}}
<div class="card mb-4">
    <div class="card-body">

        <h5 class="mb-3">Información del correo</h5>

        <div class="mb-3">
            <label class="form-label">Nombre del correo</label>
            <input type="text"
                   name="nombre"
                   class="form-control"
                   value="{{ old('nombre', $email->nombre ?? '') }}"
                   required>
            <small class="text-muted">
                Identificador interno para reconocer este envío automático.
            </small>
        </div>

        <div class="mb-3">
            <label class="form-label">Asunto del correo</label>
            <input type="text"
                   name="asunto"
                   class="form-control"
                   value="{{ old('asunto', $email->asunto ?? '') }}"
                   required>
        </div>

    </div>
</div>

{{-- ===============================
    DESTINATARIOS Y CONTENIDO
=============================== --}}
<div class="card mb-4">
    <div class="card-body">

        <h5 class="mb-3">Contenido del correo</h5>

        <div class="mb-3">
            <label class="form-label">Destinatarios</label>
            <textarea name="destinatarios"
                      class="form-control"
                      rows="2"
                      required>{{ old('destinatarios', $email->destinatarios ?? '') }}</textarea>

            <small class="text-muted">
                Ingrese uno o más correos separados por comas.<br>
                Ejemplo: correo1@empresa.cl, correo2@empresa.cl
            </small>
        </div>

        <div class="mb-3">
            <label class="form-label">Cuerpo del mensaje</label>
            <textarea name="cuerpo_html"
                      class="form-control"
                      rows="6"
                      required>{{ old('cuerpo_html', $email->cuerpo_html ?? '') }}</textarea>

            <small class="text-muted">
                Puede usar HTML simple. Este será el contenido del correo enviado.
            </small>
        </div>

    </div>
</div>

{{-- ===============================
    PROGRAMACIÓN DEL ENVÍO
=============================== --}}
<div class="card mb-4">
    <div class="card-body">

        <h5 class="mb-3">Programación del envío</h5>

        <div class="mb-3">
            <label class="form-label">Frecuencia</label>
            <select name="tipo_frecuencia"
                    id="tipo_frecuencia"
                    class="form-select"
                    required>
                <option value="diario" {{ old('tipo_frecuencia', $email->tipo_frecuencia ?? '') == 'diario' ? 'selected' : '' }}>
                    Diario (todos los días)
                </option>
                <option value="semanal" {{ old('tipo_frecuencia', $email->tipo_frecuencia ?? '') == 'semanal' ? 'selected' : '' }}>
                    Semanal (días específicos)
                </option>
                <option value="mensual" {{ old('tipo_frecuencia', $email->tipo_frecuencia ?? '') == 'mensual' ? 'selected' : '' }}>
                    Mensual (primer día del mes)
                </option>
            </select>

            <small class="text-muted">
                Seleccione cada cuánto se enviará este correo.
            </small>
        </div>

        <div class="mb-3">
            <label class="form-label">Hora de envío</label>
            <input type="time"
                   name="hora_envio"
                   class="form-control"
                   value="{{ old('hora_envio', $email->hora_envio ?? '') }}">

            <small class="text-muted">
                Hora exacta en la que se enviará el correo.
            </small>
        </div>

        {{-- DÍAS DE LA SEMANA (SOLO SEMANAL) --}}
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

            $seleccion = old('dias_semana', $email->dias_semana ?? []);
        @endphp

        <div class="mb-3" id="bloque-dias">
            <label class="form-label">Días de la semana</label>

            <div class="d-flex flex-wrap gap-3">
                @foreach ($dias as $valor => $label)
                    <label>
                        <input type="checkbox"
                               name="dias_semana[]"
                               value="{{ $valor }}"
                               {{ in_array($valor, $seleccion) ? 'checked' : '' }}>
                        {{ $label }}
                    </label>
                @endforeach
            </div>

            <small class="text-muted">
                Seleccione los días en los que se enviará el correo.
                <br>
                (Solo aplica si la frecuencia es semanal)
            </small>
        </div>

        {{-- MENSAJE EXPLICATIVO MENSUAL --}}
        <div class="alert alert-secondary d-none" id="info-mensual">
            Este correo se enviará automáticamente el primer día de cada mes,
            a la hora indicada.
        </div>

    </div>
</div>

{{-- ===============================
    ESTADO
=============================== --}}
<div class="card mb-4">
    <div class="card-body">

        <h5 class="mb-3">Estado</h5>

        <div class="mb-3">
            <label class="form-label">Correo activo</label>
            <select name="activo" class="form-select" required>
                <option value="1" {{ old('activo', $email->activo ?? '') == 1 ? 'selected' : '' }}>
                    Sí, enviar automáticamente
                </option>
                <option value="0" {{ old('activo', $email->activo ?? '') == 0 ? 'selected' : '' }}>
                    No, mantener desactivado
                </option>
            </select>
        </div>

    </div>
</div>

{{-- ===============================
    SCRIPT UX
=============================== --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const frecuencia = document.getElementById('tipo_frecuencia');
    const bloqueDias = document.getElementById('bloque-dias');
    const infoMensual = document.getElementById('info-mensual');

    function actualizarVista() {
        bloqueDias.style.display = frecuencia.value === 'semanal' ? 'block' : 'none';
        infoMensual.classList.toggle('d-none', frecuencia.value !== 'mensual');
    }

    frecuencia.addEventListener('change', actualizarVista);
    actualizarVista();
});
</script>
