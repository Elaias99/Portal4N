<div class="modal fade"
     id="modalEstadoHonorario"
     tabindex="-1"
     aria-labelledby="modalEstadoHonorarioLabel"
     aria-hidden="true">

    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Actualizar estado financiero</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST"
                  action="{{ route('honorarios.mensual.estado.store') }}"
                  id="modal-form-estado">
                @csrf

                <input type="hidden" name="honorario_id" id="modal-honorario-id">

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Emisor</label>
                        <input type="text" id="modal-emisor" class="form-control" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Estado actual</label>
                        <input type="text" id="modal-estado-actual" class="form-control" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Saldo pendiente</label>
                        <input type="text" id="modal-saldo" class="form-control fw-bold" disabled>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label class="form-label">Nuevo estado</label>
                        <select id="modal-nuevo-estado"
                                name="estado_financiero"
                                class="form-select"
                                required>
                            <option value="">Seleccione</option>
                            <option value="Abono">Abono</option>
                            <option value="Cruce">Cruce</option>
                            <option value="Pago">Pago</option>
                            <option value="Pronto pago">Pronto pago</option>
                        </select>
                    </div>

                    {{-- ABONO / CRUCE --}}
                    <div id="modal-campos-monto" class="d-none">

                        <div class="mb-3">
                            <label class="form-label">Monto</label>
                            <input type="number"
                                   name="monto"
                                   class="form-control"
                                   min="1">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Fecha</label>
                            <input type="date"
                                   name="fecha"
                                   class="form-control">
                        </div>

                    </div>

                    {{-- PROVEEDOR --}}
                    <div id="modal-campo-cobranza" class="d-none mb-3">
                        <label class="form-label">Proveedor</label>
                        <select name="cobranza_compra_id" class="form-select">
                            <option value="">Seleccione proveedor</option>
                        </select>
                    </div>

                    {{-- PAGO --}}
                    <div id="modal-campo-fecha-pago" class="d-none mb-3">
                        <label class="form-label">Fecha</label>
                        <input type="date"
                               name="fecha_pago"
                               class="form-control">
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Guardar cambios
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>
