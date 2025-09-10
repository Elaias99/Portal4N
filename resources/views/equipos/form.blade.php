<div class="mb-3">
    <label for="tipo" class="form-label">Tipo</label>
    <select class="form-select" name="tipo" id="tipo">
        <option value="">-- Selecciona un tipo --</option>
        <option value="Computador" {{ old('tipo', $equipo->tipo) == 'Computador' ? 'selected' : '' }}>Computador</option>
        <option value="Impresora" {{ old('tipo', $equipo->tipo) == 'Impresora' ? 'selected' : '' }}>Impresora</option>
        <option value="Otro" {{ old('tipo', $equipo->tipo) == 'Otro' ? 'selected' : '' }}>Otro</option>
    </select>
</div>

{{-- Campos comunes a todos los equipos --}}
<div class="mb-3">
    <label for="marca" class="form-label">Marca</label>
    <input type="text" class="form-control" name="marca" value="{{ old('marca', $equipo->marca) }}">
</div>

<div class="mb-3">
    <label for="modelo" class="form-label">Modelo</label>
    <input type="text" class="form-control" name="modelo" value="{{ old('modelo', $equipo->modelo) }}">
</div>








<div class="mb-3">
    <label for="ubicacion" class="form-label">Ubicación</label>
    <select class="form-select" name="ubicacion" id="ubicacion">
        <option value="">-- Selecciona Piso --</option>
        <option value="1er piso" {{ old('ubicacion', $equipo->ubicacion) == '1er piso' ? 'selected' : '' }}>1er Piso</option>
        <option value="2do piso" {{ old('ubicacion', $equipo->ubicacion) == '2do piso' ? 'selected' : '' }}>2do Piso</option>
        <option value="Otro" {{ old('ubicacion', $equipo->ubicacion) == 'Otro' ? 'selected' : '' }}>Otro</option>
    </select>
</div>

{{-- Campo extra si elige "Otro" --}}
<div class="mb-3" id="ubicacion_otro_container" style="display: none;">
    <label for="ubicacion_otro" class="form-label">Especificar otra ubicación</label>
    <input type="text" class="form-control" name="ubicacion_otro" value="{{ old('ubicacion_otro') }}">
</div>










<div class="mb-3">
    <label for="usuario_asignado" class="form-label">Usuario Asignado</label>
    <input type="text" class="form-control" name="usuario_asignado" value="{{ old('usuario_asignado', $equipo->usuario_asignado) }}">
</div>

{{-- Sección solo para Computadores --}}
<div class="tipo-campos" data-tipo="Computador">
    <div class="mb-3">
        <label for="procesador" class="form-label">Procesador</label>
        <input type="text" class="form-control" name="procesador" value="{{ old('procesador', $equipo->procesador) }}">
    </div>
    <div class="mb-3">
        <label for="ram" class="form-label">RAM</label>
        <input type="text" class="form-control" name="ram" value="{{ old('ram', $equipo->ram) }}">
    </div>
    <div class="mb-3">
        <label for="version_windows" class="form-label">Versión Windows</label>
        <input type="text" class="form-control" name="version_windows" value="{{ old('version_windows', $equipo->version_windows) }}">
    </div>
    <div class="mb-3">
        <label for="nombre_equipo" class="form-label">Nombre en Windows / Identificador</label>
        <input type="text" class="form-control" name="nombre_equipo" value="{{ old('nombre_equipo', $equipo->nombre_equipo) }}">
    </div>
    <div class="mb-3">
        <label for="contrasena" class="form-label">Contraseña</label>
        <input type="text" class="form-control" name="contrasena" value="{{ old('contrasena', $equipo->contrasena) }}">
    </div>
</div>

{{-- Sección solo para Impresoras --}}
<div class="tipo-campos" data-tipo="Impresora">
    <div class="mb-3">
        <label for="controlador" class="form-label">Controlador</label>
        <input type="text" class="form-control" name="controlador" value="{{ old('controlador', $equipo->controlador) }}">
    </div>
    <div class="mb-3">
        <label for="tipo_impresora" class="form-label">Tipo Impresora</label>
        <input type="text" class="form-control" name="tipo_impresora" value="{{ old('tipo_impresora', $equipo->tipo_impresora) }}">
    </div>
    <div class="mb-3">
        <label for="resolucion" class="form-label">Resolución</label>
        <input type="text" class="form-control" name="resolucion" value="{{ old('resolucion', $equipo->resolucion) }}">
    </div>
    <div class="mb-3">
        <label for="tamano_etiqueta" class="form-label">Tamaño Etiqueta</label>
        <input type="text" class="form-control" name="tamano_etiqueta" value="{{ old('tamano_etiqueta', $equipo->tamano_etiqueta) }}">
    </div>
</div>

{{-- Sección común a todos --}}
<div class="mb-3">
    <label for="estado" class="form-label">Estado</label>
    <input type="text" class="form-control" name="estado" value="{{ old('estado', $equipo->estado) }}">
</div>

<div class="mb-3">
    <label for="observacion" class="form-label">Observación</label>
    <textarea class="form-control" name="observacion">{{ old('observacion', $equipo->observacion) }}</textarea>
</div>

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    // --- Lógica para TIPO ---
    const selectTipo = document.getElementById("tipo");
    const bloques = document.querySelectorAll(".tipo-campos");

    function actualizarCamposTipo() {
        const valor = selectTipo.value;
        bloques.forEach(b => {
            b.style.display = (b.dataset.tipo === valor) ? "block" : "none";
        });
    }

    actualizarCamposTipo(); // ejecutar al cargar
    selectTipo.addEventListener("change", actualizarCamposTipo);

    // --- Lógica para UBICACIÓN ---
    const selectUbicacion = document.getElementById("ubicacion");
    const contenedorOtro = document.getElementById("ubicacion_otro_container");

    function toggleUbicacion() {
        contenedorOtro.style.display = (selectUbicacion.value === "Otro") ? "block" : "none";
    }

    toggleUbicacion(); // ejecutar al cargar
    selectUbicacion.addEventListener("change", toggleUbicacion);
});
</script>
@endpush

