<div class="modal fade" id="modalImportarComprasInfo" tabindex="-1" aria-labelledby="modalImportarComprasInfoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
    <div class="modal-content shadow">
      <div class="modal-header bg-white border-bottom">
        <h5 class="modal-title" id="modalImportarComprasInfoLabel">
          Guía para Importar Compras
        </h5>
        <button type="button"
                class="btn btn-light btn-sm rounded-circle shadow-sm"
                data-dismiss="modal"
                aria-label="Cerrar"
                style="position: absolute; top: 16px; right: 16px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; z-index: 10;">
          <span aria-hidden="true" class="text-dark" style="font-size: 1.2rem;">&times;</span>
        </button>
      </div>

      <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
        {{-- Intro --}}
        <div class="mb-4">
          <p class="text-muted mb-2">
            Este panel te permite descargar la plantilla de compras y consultar los valores válidos de las tablas relacionadas. Úsalo como guía antes de realizar una importación.
          </p>
        </div>

        {{-- Panel: Plantilla --}}
        <div id="panelArchivoCompras">
          <div class="card mb-3">
            <div class="card-body">
              <p class="mb-2 text-muted">
                Puedes descargar una plantilla de ejemplo en formato Excel para importar correctamente tus compras.
              </p>
              <a href="{{ route('compras.plantilla') }}" class="btn btn-primary">
                <i class="fa fa-download me-1"></i> Descargar Plantilla de Compras
              </a>
            </div>
          </div>
        </div>

        {{-- Panel: Bancos --}}
        <div id="panelBancos" class="d-none">
          <h5 class="mt-3 mb-3 font-weight-bold text-dark">Bancos</h5>
          <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered bg-white rounded shadow-sm">
              <thead class="thead-dark">
                <tr>
                  <th class="text-center" style="width: 50px;">ID</th>
                  <th>Nombre</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($bancos as $banco)
                  <tr>
                    <td class="text-center">{{ $banco->id }}</td>
                    <td>{{ $banco->nombre }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <a href="{{ route('exportar.bancos') }}" class="btn btn-sm btn-outline-success mt-3">
            <i class="fa fa-file-excel"></i> Exportar lista de bancos
          </a>
        </div>

        {{-- Panel: Tipo Cuenta --}}
        <div id="panelTipoCuenta" class="d-none">
          <h5 class="mt-3 mb-3 font-weight-bold text-dark">Tipo de Cuenta</h5>
          <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered bg-white rounded shadow-sm">
              <thead class="thead-dark">
                <tr>
                  <th class="text-center" style="width: 50px;">ID</th>
                  <th>Nombre</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($tipoCuentas as $cuenta)
                  <tr>
                    <td class="text-center">{{ $cuenta->id }}</td>
                    <td>{{ $cuenta->nombre }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <a href="{{ route('exportar.tipo_cuentas') }}" class="btn btn-sm btn-outline-success mt-3">
            <i class="fa fa-file-excel"></i> Exportar tipos de cuenta
          </a>
        </div>

        {{-- Panel: Tipo Documento --}}
        <div id="panelTipoDoc" class="d-none">
          <h5 class="mt-3 mb-3 font-weight-bold text-dark">Tipo de Documento</h5>
          <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered bg-white rounded shadow-sm">
              <thead class="thead-dark">
                <tr>
                  <th class="text-center" style="width: 50px;">ID</th>
                  <th>Nombre</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($tiposDocumento as $doc)
                  <tr>
                    <td class="text-center">{{ $doc->id }}</td>
                    <td>{{ $doc->nombre }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <a href="{{ route('exportar.tipo_documentos') }}" class="btn btn-sm btn-outline-success mt-3">
            <i class="fa fa-file-excel"></i> Exportar tipos de documento
          </a>
        </div>

        {{-- Panel: Forma de Pago --}}
        <div id="panelFormaPago" class="d-none">
          <h5 class="mt-3 mb-3 font-weight-bold text-dark">Forma de Pago</h5>
          <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered bg-white rounded shadow-sm">
              <thead class="thead-dark">
                <tr>
                  <th class="text-center">ID</th>
                  <th>Nombre</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($formasPago as $forma)
                  <tr>
                    <td class="text-center">{{ $forma->id }}</td>
                    <td>{{ $forma->nombre }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <a href="{{ route('exportar.forma_pago') }}" class="btn btn-sm btn-outline-success mt-3">
            <i class="fa fa-file-excel"></i> Exportar Formas de pago
          </a>
        </div>

        {{-- Panel: Plazo de Pago --}}
        <div id="panelPlazoPago" class="d-none">
          <h5 class="mt-3 mb-3 font-weight-bold text-dark">Plazo de Pago</h5>
          <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered bg-white rounded shadow-sm">
              <thead class="thead-dark">
                <tr>
                  <th class="text-center">ID</th>
                  <th>Nombre</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($plazosPago as $plazo)
                  <tr>
                    <td class="text-center">{{ $plazo->id }}</td>
                    <td>{{ $plazo->nombre }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <a href="{{ route('exportar.plazo_pago') }}" class="btn btn-sm btn-outline-success mt-3">
            <i class="fa fa-file-excel"></i> Exportar Plazos de pago
          </a>


        </div>

        {{-- Panel: Empresas --}}
        <div id="panelEmpresas" class="d-none">
          <h5 class="mt-3 mb-3 font-weight-bold text-dark">Empresas</h5>
          <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered bg-white rounded shadow-sm">
              <thead class="thead-dark">
                <tr>
                  <th class="text-center">ID</th>
                  <th>Nombre</th>
                  <th>Rut</th>
                  <th>Giro</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($empresas as $empresa)
                  <tr>
                    <td class="text-center">{{ $empresa->id }}</td>
                    <td>{{ $empresa->Nombre }}</td>
                    <td>{{ $empresa->rut }}</td>
                    <td>{{ $empresa->giro }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <a href="{{ route('exportar.empresas') }}" class="btn btn-sm btn-outline-success mt-3">
            <i class="fa fa-file-excel"></i> Exportar Empresas
          </a>




        </div>

        {{-- Panel: Centros de Costo --}}
        <div id="panelCentrosCostos" class="d-none">
          <h5 class="mt-3 mb-3 font-weight-bold text-dark">Centros de Costo</h5>
          <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered bg-white rounded shadow-sm">
              <thead class="thead-dark">
                <tr>
                  <th class="text-center">ID</th>
                  <th>Nombre</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($centrosCostos as $centro)
                  <tr>
                    <td class="text-center">{{ $centro->id }}</td>
                    <td>{{ $centro->nombre }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          
          <a href="{{ route('exportar.centro_costo') }}" class="btn btn-sm btn-outline-success mt-3">
            <i class="fa fa-file-excel"></i> Exportar Centros de Costo
          </a>
        </div>



        {{-- Panel: Consideraciones --}}
        <div id="panelConsideraciones" class="d-none">
          <h5 class="mt-3 mb-3 font-weight-bold text-dark">Consideraciones de Importación</h5>

          <div class="border rounded p-4 bg-white text-dark" style="font-size: 15px; line-height: 1.8;">
            <p class="mb-3" style="font-weight: 500;">Campos clave que debes revisar:</p>
            <ul class="pl-3 mb-0" style="list-style-type: disc;">
              <li><strong>Status:</strong> debe ser exactamente <code>Pagado</code> o <code>Pendiente</code>. Cualquier otro valor será ignorado.</li>

              <li><strong>Usuario:</strong> debe coincidir con el nombre exacto registrado en el sistema.<br>
                <span class="text-muted" style="font-size: 14px;">Ejemplo: <code>Luis</code>, <code>Hans</code></span>
              </li>

              <li><strong>Número de documento:</strong> no debe estar vacío ni ser igual a <code>0</code>.</li>

              <li><strong>Fecha documento:</strong> debe usar el formato <code>dd/mm/aaaa</code><br>
                <span class="text-muted" style="font-size: 14px;">Ejemplo: <code>10/07/2025</code></span>
              </li>

              <li><strong>Pago Total:</strong> puede contener comas o puntos como separadores, pero debe ser un número válido.<br>
                <span class="text-muted" style="font-size: 14px;">
                  Válidos: <code>1234.56</code>, <code>1,234.50</code>, <code>5000</code><br>
                  Inválidos: <code>mil pesos</code>, <code>uno</code>, <code>abc123</code>
                </span>
              </li>
            </ul>
          </div>
        </div>








        


      </div>

      <div class="modal-footer bg-light d-flex justify-content-between align-items-center flex-wrap">
        <div class="d-flex flex-wrap gap-2">
          <button onclick="mostrarPanelCompras('panelArchivoCompras')" class="btn btn-light border shadow-sm"><small>Plantilla</small></button>
          <button onclick="mostrarPanelCompras('panelBancos')" class="btn btn-light border shadow-sm"><small>Bancos</small></button>
          <button onclick="mostrarPanelCompras('panelTipoCuenta')" class="btn btn-light border shadow-sm"><small>Tipo Cuenta</small></button>
          <button onclick="mostrarPanelCompras('panelTipoDoc')" class="btn btn-light border shadow-sm"><small>Tipo Doc.</small></button>
          <button onclick="mostrarPanelCompras('panelFormaPago')" class="btn btn-light border shadow-sm"><small>Forma Pago</small></button>
          <button onclick="mostrarPanelCompras('panelPlazoPago')" class="btn btn-light border shadow-sm"><small>Plazo Pago</small></button>
          <button onclick="mostrarPanelCompras('panelEmpresas')" class="btn btn-light border shadow-sm"><small>Empresas</small></button>
          <button onclick="mostrarPanelCompras('panelCentrosCostos')" class="btn btn-light border shadow-sm"><small>Centros Costo</small></button>

          <button onclick="mostrarPanelCompras('panelConsideraciones')" class="btn btn-light border shadow-sm"><small>Consideraciones</small></button>

        </div>
        <div>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  function mostrarPanelCompras(id) {
    const paneles = [
      'panelArchivoCompras',
      'panelBancos',
      'panelTipoCuenta',
      'panelTipoDoc',
      'panelFormaPago',
      'panelPlazoPago',
      'panelEmpresas',
      'panelCentrosCostos',
      'panelConsideraciones',
    ];
    paneles.forEach(pid => {
      const el = document.getElementById(pid);
      if (el) el.classList.add('d-none');
    });
    const activo = document.getElementById(id);
    if (activo) activo.classList.remove('d-none');
  }
</script>
