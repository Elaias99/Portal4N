<!-- Modal Exportar Excel -->
<div class="modal fade" id="modalExportarExcel" tabindex="-1" role="dialog" aria-labelledby="modalExportarExcelLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <form id="formExportarExcel" action="{{ route('empleados.exportExcel') }}" method="GET">
      <div class="modal-content">
        <!-- Header -->
        <div class="modal-header bg-light">
          <h5 class="modal-title font-weight-bold" id="modalExportarExcelLabel">Seleccionar columnas a exportar</h5>

          <button type="button"
                  class="btn btn-link text-dark position-absolute end-0 top-0 m-3 p-0 border-0"
                  data-dismiss="modal" aria-label="Cerrar"
                  style="font-size: 1.2rem;">
              <i class="fas fa-times"></i>
          </button>

          
        </div>

        <!-- Body -->
        <div class="modal-body">
          <!-- Switch seleccionar todo -->
          <div class="form-check mb-3 pb-2 border-bottom">
            <input class="form-check-input" type="checkbox" id="toggleSeleccionarTodo">
            <label class="form-check-label font-weight-bold text-primary" for="toggleSeleccionarTodo">
                Seleccionar todo
            </label>
           </div>


          <!-- Lista de columnas en 2 columnas -->
          <div class="container-fluid" style="max-height: 400px; overflow-y: auto;">
            <div class="row">
              <div class="col-md-6">
                <div class="form-check"><input class="form-check-input" type="checkbox" name="columnas[]" value="empresa" checked><label class="form-check-label">Empresa</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="columnas[]" value="nombre_completo" checked><label class="form-check-label">Nombre completo</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="columnas[]" value="rut" checked><label class="form-check-label">RUT</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="columnas[]" value="fecha_ingreso" checked><label class="form-check-label">Fecha de Ingreso</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="columnas[]" value="cargo" checked><label class="form-check-label">Cargo</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="columnas[]" value="turno" checked><label class="form-check-label">Turno</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="columnas[]" value="sistema_trabajo" checked><label class="form-check-label">Sistema de Trabajo</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="columnas[]" value="tipo_contrato" checked><label class="form-check-label">Tipo de Contrato</label></div>
  
                <div class="form-check"><input class="form-check-input" type="checkbox" name="columnas[]" value="situacion" checked><label class="form-check-label">Situación</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="columnas[]" value="casino" checked><label class="form-check-label">Casino</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="columnas[]" value="fecha_nacimiento" checked><label class="form-check-label">Fecha de Nacimiento</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="columnas[]" value="sueldo" checked><label class="form-check-label">Sueldo</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="columnas[]" value="estado_civil" checked><label class="form-check-label">Estado Civil</label></div>
              </div>
              <div class="col-md-6">

                <div class="form-check"><input class="form-check-input" type="checkbox" name="columnas[]" value="correo_personal" checked><label class="form-check-label">Correo Personal</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="columnas[]" value="direccion" checked><label class="form-check-label">Dirección</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="columnas[]" value="comuna" checked><label class="form-check-label">Comuna</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="columnas[]" value="afp" checked><label class="form-check-label">AFP</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="columnas[]" value="salud" checked><label class="form-check-label">Salud</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="columnas[]" value="banco" checked><label class="form-check-label">Banco</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="columnas[]" value="cuenta" checked><label class="form-check-label">Cuenta</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="columnas[]" value="tipo_cuenta" checked><label class="form-check-label">Tipo de Cuenta</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="columnas[]" value="telefono" checked><label class="form-check-label">Teléfono</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="columnas[]" value="contacto_emergencia_nombre" checked><label class="form-check-label">Nombre Contacto Emergencia</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="columnas[]" value="contacto_emergencia_telefono" checked><label class="form-check-label">Teléfono Contacto Emergencia</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="columnas[]" value="hijos" checked><label class="form-check-label">Hijos</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="columnas[]" value="dias_vacaciones" checked><label class="form-check-label">Días de Vacaciones</label></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer bg-light d-flex justify-content-end">
          <button type="submit" class="btn btn-success mr-2">Exportar</button>
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Script -->
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formExportarExcel');
    const checkboxes = form.querySelectorAll('input[name="columnas[]"]');
    const toggleAll = document.getElementById('toggleSeleccionarTodo');

    form.addEventListener('submit', function (e) {
      const algunoMarcado = Array.from(checkboxes).some(cb => cb.checked);
      if (!algunoMarcado) {
        e.preventDefault();
        alert('Por favor selecciona al menos una columna para exportar.');
      }
    });

    toggleAll.addEventListener('change', function () {
      const marcar = toggleAll.checked;
      checkboxes.forEach(cb => cb.checked = marcar);
    });

    checkboxes.forEach(cb => {
      cb.addEventListener('change', function () {
        const todosMarcados = Array.from(checkboxes).every(c => c.checked);
        toggleAll.checked = todosMarcados;
      });
    });
  });
</script>

<!-- jQuery (requerido por Bootstrap 4) -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>


<!-- Popper.js (necesario para tooltips, modals, dropdowns de Bootstrap) -->
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>

<!-- Bootstrap 4 JS -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

