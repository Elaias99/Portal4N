<div class="modal fade" id="modalExportarProveedores" tabindex="-1" aria-labelledby="modalExportarProveedores" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content shadow">

                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title" id="modalExportarProveedores">
                    Exportar listado Proveedores
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


                <form action="{{ route('proveedores.exportar') }}" method="GET">
                    <div class="modal-body">
                        <p class="text-muted mb-3">Selecciona qué campos adicionales deseas exportar:</p>

                        <div class="table-responsive">
                            <table class="table align-middle text-center mb-3" style="border-collapse: separate; border-spacing: 0 8px;">
                                <thead>
                                    <tr>
                                        <th style="font-weight: 500; color: #495057; background-color: #f8f9fa; border: none;">
                                            <input type="checkbox" id="toggle-all" class="form-check-input me-1" title="Seleccionar todos los campos">
                                            <span style="font-size: 0.8rem;">Todos</span>
                                        </th>

                                        @foreach($camposOpcionales as $clave => $etiqueta)
                                            <th style="font-weight: 500; color: #495057; background-color: #f8f9fa; border: none;">
                                                {{ $etiqueta }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr>
                                        <td style="background-color: #f8f9fa; border: none;"></td> {{-- Celda vacía bajo el checkbox maestro --}}
                                        @foreach($camposOpcionales as $clave => $etiqueta)
                                            <td style="background-color: #f8f9fa; border: none;">
                                                <input class="form-check-input shadow-sm opcion-checkbox" type="checkbox" name="opciones[]" value="{{ $clave }}" id="col_{{ $clave }}" checked>
                                            </td>
                                        @endforeach
                                    </tr>
                                </tbody>

                            </table>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fa fa-download me-1"></i> Exportar Excel
                            </button>
                        </div>
                    </div>
                </form>







            </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggleAll = document.getElementById('toggle-all');
        const checkboxes = document.querySelectorAll('.opcion-checkbox');

        toggleAll.addEventListener('change', function () {
            checkboxes.forEach(cb => cb.checked = toggleAll.checked);
        });
    });
</script>
