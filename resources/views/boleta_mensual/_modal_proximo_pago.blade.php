<div class="modal fade"
     id="modalProximoPago"
     tabindex="-1"
     aria-labelledby="modalProximoPagoLabel"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="modalProximoPagoLabel">
                    Definir próximo pago
                </h5>
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Cerrar">
                </button>
            </div>

            <form method="POST"
                  id="form-proximo-pago"
                  action="{{ route('honorarios.mensual.proximo-pago.exportar') }}">
                @csrf

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">
                            Honorarios seleccionados
                        </label>

                        <div id="proximos-pagos-seleccionados"
                             class="border rounded p-2"
                             style="min-height: 120px;">
                        </div>
                    </div>

                    <div id="inputs-proximos-pagos-seleccionados"></div>

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
                            id="btn-cancelar-proximo-pago"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit"
                            id="btn-submit-proximo-pago"
                            class="btn btn-primary">
                        Guardar próximo pago
                    </button>
                </div>





            </form>

        </div>
    </div>
</div>