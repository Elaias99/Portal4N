<!-- Modal: Importar Proveedores -->
<div class="modal fade" id="modalImportarProveedores" tabindex="-1" aria-labelledby="modalImportarProveedoresLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
    <div class="modal-content shadow">
      <div class="modal-header bg-white border-bottom">
        <h5 class="modal-title" id="modalImportarProveedoresLabel">
          Guía para Importar Proveedores
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

      <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
        {{-- Intro --}}
        <div class="mb-4">
          <p class="text-muted mb-2">
            Este panel te permite descargar la plantilla de proveedores y consultar los valores válidos de las tablas relacionadas. Úsalo como guía antes de realizar una importación.
          </p>
        </div>

        {{-- Panel: Plantilla --}}
        <div id="panelArchivo">
          <div class="card mb-3">
            <div class="card-body">
              <p class="mb-2 text-muted">
                Puedes descargar una plantilla de ejemplo en formato Excel para importar correctamente tus proveedores.
              </p>
              <a href="{{ route('proveedores.plantilla') }}" class="btn btn-primary">
                <i class="fa fa-download me-1"></i> Descargar Plantilla de Proveedores
              </a>
            </div>
          </div>
        </div>

        {{-- Panel: Bancos --}}
        <div id="panelBancos" class="d-none">
          <div class="table-responsive">


            <h5 class="mt-3 mb-3 font-weight-bold text-dark">
                Bancos
            </h5>




            <table class="table table-sm table-hover table-bordered bg-white rounded shadow-sm">
                <thead class="thead-dark">
                    <tr>
                        <th style="width: 50px;" class="text-center">ID</th>
                        <th class="text-left">Nombre</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bancos as $banco)
                        <tr>
                            <td class="text-center align-middle">{{ $banco->id }}</td>
                            <td class="align-middle">{{ $banco->nombre }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

          </div>
          <a href="{{ route('exportar.bancos') }}" class="btn btn-sm btn-outline-success mt-3">
            <i class="fa fa-file-excel"></i> Exportar lista de bancos
          </a>
        </div>

        {{-- Panel: Tipos de Cuenta --}}
        <div id="panelCuentas" class="d-none">
          <div class="table-responsive">

            <h5 class="mt-3 mb-3 font-weight-bold text-dark">
                Tipo de Cuenta
            </h5>

            <table class="table table-sm table-hover table-bordered bg-white rounded shadow-sm">
                <thead class="thead-dark">
                    <tr>
                        <th style="width: 50px;" class="text-center">ID</th>
                        <th class="text-left">Nombre del Tipo de Cuenta</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tiposCuenta as $cuenta)
                        <tr>
                            <td class="text-center align-middle">{{ $cuenta->id }}</td>
                            <td class="align-middle">{{ $cuenta->nombre }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>




          </div>
          <a href="{{ route('exportar.tipo_cuentas') }}" class="btn btn-sm btn-outline-success mt-3">
            <i class="fa fa-file-excel"></i> Exportar tipos de cuenta
          </a>
        </div>

        {{-- Panel: Tipos de Documento --}}
        <div id="panelDocumentos" class="d-none">
          <div class="table-responsive">

            
            <h5 class="mt-3 mb-3 font-weight-bold text-dark">
                Tipo Documento
            </h5>




            <table class="table table-sm table-hover table-bordered bg-white rounded shadow-sm">
                <thead class="thead-dark">
                    <tr>
                        <th style="width: 50px;" class="text-center">ID</th>
                        <th class="text-left">Nombre del Tipo de Documento</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tiposDocumento as $documento)
                        <tr>
                            <td class="text-center align-middle">{{ $documento->id }}</td>
                            <td class="align-middle">{{ $documento->nombre }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>



          </div>
          <a href="{{ route('exportar.tipo_documentos') }}" class="btn btn-sm btn-outline-success mt-3">
            <i class="fa fa-file-excel"></i> Exportar tipos de documento
          </a>
        </div>
      </div>

      <div class="modal-footer bg-light d-flex justify-content-between align-items-center flex-wrap">
        {{-- Navegación izquierda --}}
        <div class="d-flex flex-wrap gap-2">
          <button onclick="mostrarPanel('panelArchivo')" class="btn btn-light border shadow-sm">
            <small>Plantilla de ejemplo</small>
          </button>
          <button onclick="mostrarPanel('panelBancos')" class="btn btn-light border shadow-sm">
            <small>Bancos</small>
          </button>
          <button onclick="mostrarPanel('panelCuentas')" class="btn btn-light border shadow-sm">
            <small>Tipo Cuenta</small>
          </button>
          <button onclick="mostrarPanel('panelDocumentos')" class="btn btn-light border shadow-sm">
            <small>Tipo Doc.</small>
          </button>
        </div>

        {{-- Botón cerrar --}}
        <div>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>

        </div>
      </div>
    </div>
  </div>
</div>

<script>
  function mostrarPanel(id) {
    const paneles = ['panelArchivo', 'panelBancos', 'panelCuentas', 'panelDocumentos'];
    paneles.forEach(pid => {
      document.getElementById(pid).classList.add('d-none');
    });
    document.getElementById(id).classList.remove('d-none');
  }
</script>
