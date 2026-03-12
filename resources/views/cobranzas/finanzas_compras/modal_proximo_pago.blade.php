<div class="modal fade"
     id="modalProximoPagoCompras"
     tabindex="-1"
     aria-labelledby="modalProximoPagoComprasLabel"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalProximoPagoComprasLabel">
                    Definir próximo pago
                </h5>

                <button type="button"
                        class="btn-close"
                        id="btn-cerrar-x-proximo-pago-compras"
                        aria-label="Cerrar">
                </button>
            </div>

            <form method="POST"
                  id="form-proximo-pago-compras"
                  action="{{ route('finanzas_compras.proximo_pago.exportar') }}">
                @csrf

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Documentos seleccionados
                        </label>

                        <div id="proximos-pagos-compras-seleccionados"
                             class="border rounded p-2 bg-light"
                             style="min-height: 120px;">
                        </div>
                    </div>

                    <div id="inputs-proximos-pagos-compras-seleccionados"></div>

                    <hr>

                    <div class="mb-3">
                        <label class="form-label">Fecha programada</label>
                        <input type="date"
                               name="fecha_programada"
                               class="form-control"
                               required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Observación</label>
                        <textarea name="observacion"
                                  class="form-control"
                                  rows="3"
                                  placeholder="Opcional"></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-outline-secondary"
                            id="btn-cancelar-proximo-pago-compras">
                        Cancelar
                    </button>

                    <button type="submit"
                            id="btn-submit-proximo-pago-compras"
                            class="btn btn-primary">
                        Guardar próximo pago
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>