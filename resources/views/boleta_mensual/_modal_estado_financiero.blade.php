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
                  action="{{ route('honorarios.mensual.estado.store') }}">
                @csrf

                <input type="hidden" name="honorario_id" id="modal-honorario-id">

                <div class="modal-body">

                    {{-- INFO --}}
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

                    {{-- NUEVO ESTADO --}}
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

                    {{-- =========================
                         ABONO
                    ========================== --}}
                    <div id="modal-campo-abono" class="d-none">

                        <div class="mb-3">
                            <label class="form-label">Monto</label>
                            <input type="number"
                                   name="monto"
                                   class="form-control"
                                   min="1">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Fecha abono</label>
                            <input type="date"
                                   name="fecha_abono"
                                   class="form-control">
                        </div>

                    </div>

                    {{-- =========================
                         CRUCE
                    ========================== --}}
                    <div id="modal-campo-cruce" class="d-none">

                        <div class="mb-3">
                            <label class="form-label">Monto</label>
                            <input type="number"
                                   name="monto"
                                   class="form-control"
                                   min="1">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Proveedor</label>
                            <select name="cobranza_compra_id" class="form-select">
                                <option value="">Seleccione proveedor</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Fecha cruce</label>
                            <input type="date"
                                   name="fecha_cruce"
                                   class="form-control">
                        </div>

                    </div>

                    {{-- =========================
                        PAGO
                    ========================== --}}
                    <div id="modal-campo-pago" class="d-none">

                        <div class="mb-3">
                            <label class="form-label">Fecha pago</label>
                            <input type="date"
                                name="fecha_pago"
                                class="form-control">
                        </div>

                    </div>



                    {{-- =========================
                        PRONTO PAGO
                    ========================== --}}
                    <div id="modal-campo-pronto-pago" class="d-none">

                        <div class="mb-3">
                            <label class="form-label">Fecha pronto pago</label>
                            <input type="date"
                                name="fecha_pronto_pago"
                                class="form-control">
                        </div>

                    </div>



                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="btn btn-primary">
                        Guardar cambios
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>
