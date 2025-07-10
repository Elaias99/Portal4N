<!-- Modal de Importación de Proveedores -->
<div class="modal fade" id="modalImportarExcelProveedores" tabindex="-1" role="dialog" aria-labelledby="modalImportarExcelProveedoresLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md" role="document">
    <form id="formImportarProveedores" action="{{ route('importar.proveedores') }}" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="modal-content shadow-sm border-0 rounded-lg">

        <!-- Header -->
        <div class="modal-header bg-white border-bottom d-flex align-items-center">
          <h5 class="modal-title text-dark mb-0">
            Importar Proveedores desde Excel
          </h5>

          <button type="button"
                  class="btn btn-light btn-sm rounded-circle shadow-sm"
                  data-dismiss="modal"
                  aria-label="Cerrar"
                  style="
                    position: absolute;
                    top: 16px;
                    right: 16px;
                    width: 32px;
                    height: 32px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10;
                  ">
            <span aria-hidden="true" class="text-dark" style="font-size: 1.2rem;">&times;</span>
          </button>

        </div>

        <!-- Body -->
        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
          {{-- Contenido visible --}}
          <div id="formularioCarga">
            <div class="alert alert-light border mb-3">
              <p class="mb-1 text-muted">
                Selecciona un archivo Excel válido para importar proveedores. Solo se permiten formatos <strong>.xlsx</strong> y <strong>.xls</strong>.
              </p>
            </div>

            <div class="form-group">
              <label for="archivoExcel" class="font-weight-bold">Archivo Excel</label>
              <div class="custom-file">
                <input type="file" class="custom-file-input" name="archivo" id="archivoExcel" accept=".xlsx,.xls" required>
                <label class="custom-file-label" for="archivoExcel">Seleccionar archivo</label>
              </div>

            </div>
          </div>

          {{-- Spinner de carga --}}
          <div id="mensajeProceso" class="d-none">
            <div class="text-center py-4">
              <div class="spinner-border text-success" role="status">
                <span class="sr-only">Cargando...</span>
              </div>
              <p class="mt-3 mb-0 font-weight-bold text-secondary">Procesando archivo, por favor espera...</p>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer bg-light border-top d-flex justify-content-between align-items-center flex-wrap">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
            Cancelar
          </button>
          <button type="submit" class="btn btn-success" id="btnEnviarImportar">
            <i class="fa fa-upload mr-1"></i> Importar
          </button>
        </div>

      </div>
    </form>
  </div>
</div>

<!-- Script para manejar el estado de carga -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formImportarProveedores');
    const archivo = document.getElementById('archivoExcel');
    const btn = document.getElementById('btnEnviarImportar');
    const spinner = document.getElementById('mensajeProceso');
    const contenido = document.getElementById('formularioCarga');

    if (form) {
        form.addEventListener('submit', function (e) {
            if (!archivo.files.length) {
                e.preventDefault();
                alert('Por favor selecciona un archivo Excel antes de importar.');
                return;
            }

            spinner.classList.remove('d-none');
            contenido.classList.add('d-none');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-2"></span> Importando...';
        });
    }
});
</script>

