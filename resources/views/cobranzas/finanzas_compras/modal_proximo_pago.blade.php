<div class="modal fade"
     id="modalProximoPagoCompras"
     tabindex="-1"
     aria-labelledby="modalProximoPagoComprasLabel"
     aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 92vw;">
        <div class="modal-content border-0 shadow-sm rounded-3">

            <div class="modal-header px-4 py-3 border-bottom">
                <h5 class="modal-title fw-bold mb-0" id="modalProximoPagoComprasLabel">
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

                <div class="modal-body px-4 py-4">

                    <div class="border rounded-3 mb-4 p-3">
                        <label class="form-label fw-semibold mb-3">
                            Documentos seleccionados
                        </label>

                        <div class="table-responsive rounded border">
                            <table class="table table-sm table-borderless mb-0 align-middle"
                                   style="white-space: nowrap;">
                                <thead>
                                    <tr>
                                        <th class="px-2 py-2 fw-semibold text-dark">Folio</th>
                                        <th class="px-2 py-2 fw-semibold text-dark">Razón social</th>
                                        <th class="px-2 py-2 fw-semibold text-dark">RUT</th>
                                        <th class="px-2 py-2 fw-semibold text-dark text-end">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody id="proximos-pagos-compras-seleccionados">
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="inputs-proximos-pagos-compras-seleccionados"></div>

                    <div class="border rounded-3 p-3">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Fecha programada</label>
                            <input type="date"
                                   name="fecha_programada"
                                   class="form-control"
                                   required>
                        </div>

                        <div class="mb-0">
                            <label class="form-label fw-semibold">Observación</label>
                            <textarea name="observacion"
                                      class="form-control"
                                      rows="3"
                                      placeholder="Opcional"></textarea>
                        </div>
                    </div>

                </div>

                <div class="modal-footer px-4 py-3 border-top">
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