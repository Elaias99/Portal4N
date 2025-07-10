<div class="modal fade" id="modalImportarExcelCompras" tabindex="-1" role="dialog" aria-labelledby="modalImportarExcelComprasLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-bold" id="modalImportarExcelComprasLabel">
                    <i class="fa fa-file-import mr-2"></i> Importar Compras desde Excel
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                {{-- PASO 1: Selección --}}
                <div id="paso1">
                    <p class="mb-3">Selecciona un archivo Excel (.xlsx o .xls) para importar compras masivamente al sistema.</p>
                    <form id="formImportarCompras" action="{{ route('compras.importar') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="archivo_excel" class="form-control mb-3" accept=".xlsx,.xls" required>
                        <div class="text-right">
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-check mr-1"></i> Iniciar Importación
                            </button>
                        </div>
                    </form>
                </div>

                {{-- PASO 2: Cargando --}}
                <div id="paso2" class="text-center d-none">
                    <div class="spinner-border text-success mb-3" role="status"></div>
                    <p class="font-weight-bold">Procesando archivo...</p>
                    <p>Por favor, no cierres esta ventana.</p>
                </div>
            </div>
        </div>
    </div>
</div>
