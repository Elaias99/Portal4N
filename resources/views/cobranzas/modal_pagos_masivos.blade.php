<div class="modal fade" id="modalPagosMasivos" tabindex="-1" aria-labelledby="modalPagosMasivosLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            {{-- === HEADER === --}}
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold" id="modalPagosMasivosLabel">
                        Pagos Masivos — Resumen de documentos seleccionados
                    </h5>
                    <div class="small text-muted">
                        Seleccionados: <span id="pm-count">0</span>
                    </div>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            {{-- === BODY === --}}
            <div class="modal-body">

                {{-- Mensaje cuando no hay selección --}}
                <div id="pm-sin-seleccion" class="alert alert-warning d-none mb-3">
                    No hay documentos seleccionados en la tabla. Marca al menos uno y vuelve a abrir el pago masivo.
                </div>

                {{-- Form POST al controlador --}}
                <form id="form-pagos-masivos" action="{{ route('documentos.pagos.masivo') }}" method="POST">
                    @csrf

                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Empresa</th>
                                    <th>RUT</th>
                                    <th>Emisión</th>
                                    <th>Folio</th>
                                    <th>Fecha Emisión</th>
                                    <th>Fecha Vencimiento</th>
                                    <th class="text-end">Monto</th>
                                    <th style="width:160px;">Operación</th>
                                    <th style="width:180px;">Monto a pagar</th>
                                    <th class="text-end">Saldo Pendiente</th>
                                    <th style="width:70px;" class="text-center">Quitar</th>
                                </tr>
                            </thead>

                            <tbody id="pm-body">
                                {{-- filas por JS --}}
                            </tbody>
                        </table>
                    </div>



                    {{-- Fecha de pago --}}
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">Fecha de pago</label>
                        <input type="date" name="fecha_pago" id="pm-fecha-pago" class="form-control form-control-sm" required>
                    </div>

                    <div id="contenedor-montos-hidden"></div>

                    <div class="d-flex justify-content-between align-items-end mt-3">
                        <div>
                            <div class="fw-semibold mb-1">
                                Total a pagar:
                                <span id="pm-total-general">$0</span>
                            </div>

                            <div id="pm-totales-empresa" class="small text-muted"></div>
                        </div>

                        <div class="text-end d-flex gap-2">
                            <button
                                type="button"
                                id="btn-cerrar-pagos-masivos"
                                class="btn btn-outline-secondary btn-sm"
                                data-bs-dismiss="modal">
                                Cancelar
                            </button>

                            <button type="submit" id="btn-registrar-pagos" class="btn btn-success btn-sm">
                                <i class="bi bi-check-circle"></i> Registrar Pagos Seleccionados
                            </button>
                        </div>
                    </div>



                </form>

            </div>
        </div>
    </div>
</div>