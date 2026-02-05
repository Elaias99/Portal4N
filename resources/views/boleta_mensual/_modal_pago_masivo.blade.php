<div class="modal fade"
     id="modalPagoMasivo"
     tabindex="-1"
     aria-labelledby="modalPagoMasivoLabel"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            {{-- =========================
                HEADER
            ========================== --}}
            <div class="modal-header">
                <h5 class="modal-title" id="modalPagoMasivoLabel">
                    Pago masivo de honorarios
                </h5>
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Cerrar">
                </button>
            </div>

            {{-- =========================
                FORM
            ========================== --}}
            <form method="POST"
                  id="form-pago-masivo"
                  action="{{ route('honorarios.mensual.pago.masivo.exportar') }}">
                @csrf

                <div class="modal-body">

                    {{-- =====================================================
                        BLOQUE A: BUSCADOR (modo antiguo)
                        👉 Se ocultará cuando venga desde la tabla
                    ====================================================== --}}
                    <div id="bloque-buscador">

                        <div class="mb-3">
                            <label class="form-label">Buscar honorarios</label>
                            <input type="text"
                                   id="buscador-honorarios"
                                   class="form-control"
                                   placeholder="Buscar por folio, RUT o emisor">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Resultados</label>

                            <div id="resultados-honorarios"
                                 class="border rounded p-2"
                                 style="max-height: 200px; overflow-y: auto;">
                            </div>
                        </div>

                        <hr>
                    </div>

                    {{-- =====================================================
                        BLOQUE B: RESUMEN (siempre visible)
                        👉 Desde tabla o desde buscador
                    ====================================================== --}}
                    <div id="bloque-resumen">

                        <div class="mb-3">
                            <label class="form-label">
                                Honorarios que quedarán como pagados
                            </label>

                            <div id="honorarios-seleccionados"
                                 class="border rounded p-2"
                                 style="min-height: 120px;">
                            </div>
                        </div>

                        {{-- Inputs hidden generados por JS --}}
                        <div id="inputs-honorarios-seleccionados"></div>

                        <hr>
                    </div>

                    {{-- =====================================================
                        BLOQUE C: CONFIRMACIÓN
                    ====================================================== --}}
                    <div id="bloque-confirmacion">

                        <div class="mb-3">
                            <label class="form-label">Fecha de pago</label>
                            <input type="date"
                                   name="fecha_pago"
                                   class="form-control"
                                   required>
                        </div>

                    </div>

                </div>

                {{-- =========================
                    FOOTER
                ========================== --}}
                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit"
                            class="btn btn-success">
                        Confirmar pago masivo
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>
