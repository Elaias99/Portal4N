<div class="modal fade" id="modalPagosMasivos" tabindex="-1" aria-labelledby="modalPagosMasivosLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 94vw;">
        <div class="modal-content border-0 shadow-sm rounded-3">

            {{-- === HEADER === --}}
            <div class="modal-header px-4 py-3 border-bottom">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="modalPagosMasivosLabel">
                        Pagos Masivos — Resumen de documentos seleccionados
                    </h5>
                    <div class="small text-muted mt-1">
                        Seleccionados: <span id="pm-count">0</span>
                    </div>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            {{-- === BODY === --}}
            <div class="modal-body px-4 py-4">

                {{-- Mensaje cuando no hay selección --}}
                <div id="pm-sin-seleccion" class="alert alert-warning d-none mb-4">
                    No hay documentos seleccionados en la tabla. Marca al menos uno y vuelve a abrir el pago masivo.
                </div>

                {{-- Form POST al controlador --}}
                <form id="form-pagos-masivos" action="{{ route('documentos.pagos.masivo') }}" method="POST">
                    @csrf

                    <div class="border rounded-3 mb-4 p-3">
                        <label class="form-label fw-semibold mb-3">
                            Documentos seleccionados
                        </label>

                        <div class="table-responsive rounded border">
                            <table class="table table-sm table-borderless mb-0 align-middle" style="white-space: nowrap;">
                                <thead>
                                    <tr>
                                        <th class="px-2 py-2 fw-semibold text-dark">Empresa</th>
                                        <th class="px-2 py-2 fw-semibold text-dark">RUT</th>
                                        <th class="px-2 py-2 fw-semibold text-dark">Emisión</th>
                                        <th class="px-2 py-2 fw-semibold text-dark">Folio</th>
                                        <th class="px-2 py-2 fw-semibold text-dark">Fecha Emisión</th>
                                        <th class="px-2 py-2 fw-semibold text-dark">Fecha Vencimiento</th>
                                        <th class="px-2 py-2 fw-semibold text-dark text-end">Monto</th>
                                        <th class="px-2 py-2 fw-semibold text-dark" style="min-width: 160px;">Operación</th>
                                        <th class="px-2 py-2 fw-semibold text-dark" style="min-width: 180px;">Monto a pagar</th>
                                        <th class="px-2 py-2 fw-semibold text-dark text-end">Saldo Pendiente</th>
                                        <th class="px-2 py-2 fw-semibold text-dark text-center" style="min-width: 70px;">Quitar</th>
                                    </tr>
                                </thead>

                                <tbody id="pm-body">
                                    {{-- filas por JS --}}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="contenedor-montos-hidden"></div>

                    <div class="border rounded-3 p-3">
                        {{-- Fecha de pago --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Fecha de pago</label>
                            <input type="date" name="fecha_pago" id="pm-fecha-pago" class="form-control" required>
                        </div>

                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mt-3">
                            <div>
                                <div class="fw-semibold mb-1">
                                    Total a pagar:
                                    <span id="pm-total-general">$0</span>
                                </div>

                                <div id="pm-totales-empresa" class="small text-muted"></div>
                            </div>

                            <div class="text-md-end d-flex gap-2">
                                <button
                                    type="button"
                                    id="btn-cerrar-pagos-masivos"
                                    class="btn btn-outline-secondary"
                                    data-bs-dismiss="modal">
                                    Cancelar
                                </button>

                                <button type="submit" id="btn-registrar-pagos" class="btn btn-success">
                                    <i class="bi bi-check-circle"></i> Registrar Pagos Seleccionados
                                </button>
                            </div>
                        </div>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>