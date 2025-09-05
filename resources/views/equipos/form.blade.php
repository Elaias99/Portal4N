<div class="mb-3">
    <label for="tipo" class="form-label">Tipo</label>
    <input type="text" class="form-control" name="tipo" value="{{ old('tipo', $equipo->tipo) }}">
</div>

<div class="mb-3">
    <label for="marca" class="form-label">Marca</label>
    <input type="text" class="form-control" name="marca" value="{{ old('marca', $equipo->marca) }}">
</div>

<div class="mb-3">
    <label for="modelo" class="form-label">Modelo</label>
    <input type="text" class="form-control" name="modelo" value="{{ old('modelo', $equipo->modelo) }}">
</div>

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
    <label for="direccion_ip" class="form-label">Dirección IP</label>
    <input type="text" class="form-control" name="direccion_ip" value="{{ old('direccion_ip', $equipo->direccion_ip) }}">
</div>

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

<div class="mb-3">
    <label for="funcion_principal" class="form-label">Función Principal</label>
    <textarea class="form-control" name="funcion_principal">{{ old('funcion_principal', $equipo->funcion_principal) }}</textarea>
</div>

<div class="mb-3">
    <label for="ubicacion" class="form-label">Ubicación</label>
    <input type="text" class="form-control" name="ubicacion" value="{{ old('ubicacion', $equipo->ubicacion) }}">
</div>

<div class="mb-3">
    <label for="usuario" class="form-label">Usuario</label>
    <input type="text" class="form-control" name="usuario" value="{{ old('usuario', $equipo->usuario) }}">
</div>

<div class="mb-3">
    <label for="usuario_asignado" class="form-label">Usuario Asignado</label>
    <input type="text" class="form-control" name="usuario_asignado" value="{{ old('usuario_asignado', $equipo->usuario_asignado) }}">
</div>

<div class="mb-3">
    <label for="contrasena" class="form-label">Contraseña</label>
    <input type="text" class="form-control" name="contrasena" value="{{ old('contrasena', $equipo->contrasena) }}">
</div>

<div class="mb-3">
    <label for="estado" class="form-label">Estado</label>
    <input type="text" class="form-control" name="estado" value="{{ old('estado', $equipo->estado) }}">
</div>

<div class="mb-3">
    <label for="observacion" class="form-label">Observación</label>
    <textarea class="form-control" name="observacion">{{ old('observacion', $equipo->observacion) }}</textarea>
</div>
