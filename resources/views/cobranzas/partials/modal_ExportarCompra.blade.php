<div class="modal fade" id="modalExportarCompra" tabindex="-1" aria-labelledby="modalExportarCompraLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title fw-bold" id="modalExportarCompraLabel">
          <i class="bi bi-file-earmark-excel me-2"></i>Opciones de Exportación
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <p class="text-muted small mb-4">
          Selecciona el tipo de exportación que deseas realizar.  
          Los filtros aplicados actualmente serán respetados.
        </p>

        {{-- Exportar solo la página actual --}}
        <form method="GET" action="{{ route('finanzas_compras.export') }}">
            @foreach (request()->query() as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <button type="submit" class="btn btn-outline-success w-100 mb-3">
                <i class="bi bi-file-earmark-arrow-down me-1"></i>
                Exportar página actual
            </button>
        </form>

        {{-- Exportar todos los registros filtrados --}}
        <form method="GET" action="{{ route('finanzas_compras.exportAll') }}">
            @foreach (request()->query() as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <button type="submit" class="btn btn-success w-100">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>
                Exportar todos los registros filtrados
            </button>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i> Cerrar
        </button>
      </div>
    </div>
  </div>
</div>
