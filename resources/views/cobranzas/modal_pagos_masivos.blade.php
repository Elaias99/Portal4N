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
                                    <th>Folio</th>
                                    <th>Razón Social</th>
                                    <th>RUT</th>
                                    <th class="text-end">Monto Total</th>
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

                    <div class="text-end">
                        <button type="submit" id="btn-registrar-pagos" class="btn btn-success btn-sm">
                            <i class="bi bi-check-circle"></i> Registrar Pagos Seleccionados
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

{{-- =========================
     SCRIPT (NUEVO)
========================= --}}
<script>
(() => {

    // Estado interno
    let documentosSeleccionados = {};

    // Helpers
    const $ = (sel, root = document) => root.querySelector(sel);
    const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

    function escapeHtml(str) {
        return String(str ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function fmtCLP(n) {
        const num = Number(n || 0);
        return '$' + num.toLocaleString('es-CL');
    }

    function upsertHiddenDocumento(id) {
        const cont = $('#contenedor-montos-hidden');
        if (!cont) return;

        // documentos[] (array)
        if (!cont.querySelector(`input[name="documentos[]"][value="${id}"]`)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'documentos[]';
            input.value = id;
            cont.appendChild(input);
        }
    }

    function upsertHiddenOperacion(id, valor) {
        const cont = $('#contenedor-montos-hidden');
        if (!cont) return;

        let input = cont.querySelector(`input[name="operaciones[${id}]"]`);
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = `operaciones[${id}]`;
            cont.appendChild(input);
        }
        input.value = valor;
    }

    function upsertHiddenMonto(id, valor) {
        const cont = $('#contenedor-montos-hidden');
        if (!cont) return;

        let input = cont.querySelector(`input[name="montos[${id}]"]`);
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = `montos[${id}]`;
            cont.appendChild(input);
        }
        input.value = valor;
    }

    function removeHiddenFor(id) {
        const cont = $('#contenedor-montos-hidden');
        if (!cont) return;

        cont.querySelector(`input[name="documentos[]"][value="${id}"]`)?.remove();
        cont.querySelector(`input[name="operaciones[${id}]"]`)?.remove();
        cont.querySelector(`input[name="montos[${id}]"]`)?.remove();
    }

    // Tomar selección desde la tabla principal
    function collectFromMainTable() {

        documentosSeleccionados = {};

        const seleccion = JSON.parse(localStorage.getItem('documentosSeleccionados')) || {};

        const checks = document.querySelectorAll('.check-documento');

        // Si no hay checkboxes visibles en la tabla, limpiar memoria
        if (checks.length === 0) {
            localStorage.removeItem('documentosSeleccionados');
            return;
        }

        Object.values(seleccion).forEach(doc => {

            documentosSeleccionados[doc.id] = {
                id: doc.id,
                folio: doc.folio,
                razon: doc.razon,
                rut: doc.rut || '',
                saldoInicial: Number(doc.saldo),
                montoTotal: Number(doc.total),
                operacion: 'pago',
                monto: Number(doc.saldo)
            };

        });

    }

    function renderResumen() {
        const tbody = $('#pm-body');
        const countEl = $('#pm-count');
        const alertaSin = $('#pm-sin-seleccion');
        const btn = $('#btn-registrar-pagos');
        const hidden = $('#contenedor-montos-hidden');

        if (!tbody || !countEl || !alertaSin || !btn || !hidden) return;

        tbody.innerHTML = '';
        hidden.innerHTML = '';

        const ids = Object.keys(documentosSeleccionados);
        countEl.textContent = String(ids.length);

        if (ids.length === 0) {
            alertaSin.classList.remove('d-none');
            btn.disabled = true;
            return;
        }

        alertaSin.classList.add('d-none');
        btn.disabled = false;

        ids.forEach(id => {
            const doc = documentosSeleccionados[id];

            // Hidden base
            upsertHiddenDocumento(id);
            upsertHiddenOperacion(id, doc.operacion);
            upsertHiddenMonto(id, doc.monto);

            const tr = document.createElement('tr');

            tr.innerHTML = `
                <td class="fw-semibold">${escapeHtml(doc.folio)}</td>
                <td class="text-start">${escapeHtml(doc.razon)}</td>
                <td>${escapeHtml(doc.rut)}</td>
                <td class="text-end">${fmtCLP(doc.montoTotal)}</td>

                <td>
                    <select class="form-select form-select-sm pm-op" data-id="${id}">
                        <option value="pago" selected>Pago total</option>
                        <option value="abono">Abono</option>
                    </select>
                </td>

                <td>
                    <input type="number"
                        class="form-control form-control-sm pm-monto"
                        data-id="${id}"
                        min="1"
                        max="${doc.saldoInicial}"
                        step="1"
                        value="${doc.monto}"
                        disabled
                    >
                </td>

                <td class="text-end">${fmtCLP(doc.saldoInicial)}</td>

                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger pm-quitar" data-id="${id}">
                        ✕
                    </button>
                </td>
            `;

            tbody.appendChild(tr);
        });
    }

    function quitarDocumento(id) {
        // eliminar interno
        delete documentosSeleccionados[id];
        removeHiddenFor(id);

        // desmarcar checkbox en tabla principal (si está visible)
        $$('.check-documento').forEach(cb => {
            const cbId = String(cb.dataset.id || cb.value);
            if (cbId === String(id)) cb.checked = false;
        });

        renderResumen();
    }

    // Events del modal
    document.addEventListener('DOMContentLoaded', () => {
        const modalEl = $('#modalPagosMasivos');
        const form = $('#form-pagos-masivos');
        const btn  = $('#btn-registrar-pagos');
        const fecha = $('#pm-fecha-pago');

        if (!modalEl) return;

        // Al abrir modal: cargar selección desde la tabla y renderizar
        modalEl.addEventListener('show.bs.modal', () => {
            // set fecha hoy por defecto si está vacío
            if (fecha && !fecha.value) {
                const today = new Date();
                const yyyy = today.getFullYear();
                const mm = String(today.getMonth() + 1).padStart(2, '0');
                const dd = String(today.getDate()).padStart(2, '0');
                fecha.value = `${yyyy}-${mm}-${dd}`;
            }

            collectFromMainTable();
            renderResumen();

            // reset mensaje success anterior si existe
            $('#msg-pagos-ok')?.remove();
            $('#msg-pagos-error')?.remove();

            if (btn) {
                btn.disabled = Object.keys(documentosSeleccionados).length === 0;
                btn.innerHTML = '<i class="bi bi-check-circle"></i> Registrar Pagos Seleccionados';
            }
        });

        // Delegación: cambio operación / monto / quitar
        modalEl.addEventListener('change', (e) => {
            const opSel = e.target.closest('.pm-op');
            if (opSel) {
                const id = opSel.dataset.id;
                const op = opSel.value;

                if (!documentosSeleccionados[id]) return;

                documentosSeleccionados[id].operacion = op;
                upsertHiddenOperacion(id, op);

                const inputMonto = modalEl.querySelector(`.pm-monto[data-id="${id}"]`);
                if (!inputMonto) return;

                if (op === 'pago') {
                    documentosSeleccionados[id].monto = documentosSeleccionados[id].saldoInicial;
                    inputMonto.value = documentosSeleccionados[id].saldoInicial;
                    inputMonto.disabled = true;
                    upsertHiddenMonto(id, documentosSeleccionados[id].saldoInicial);
                } else {
                    inputMonto.disabled = false;
                    const v = Number(inputMonto.value || 0);
                    documentosSeleccionados[id].monto = v;
                    upsertHiddenMonto(id, v);
                }
            }
        });

        modalEl.addEventListener('input', (e) => {
            const montoInp = e.target.closest('.pm-monto');
            if (!montoInp) return;

            const id = montoInp.dataset.id;
            if (!documentosSeleccionados[id]) return;

            let v = Number(montoInp.value || 0);

            // clamp básico por UX
            const max = Number(montoInp.max || 0);
            if (max > 0 && v > max) v = max;
            if (v < 1) v = 1;

            montoInp.value = v;

            documentosSeleccionados[id].monto = v;
            upsertHiddenMonto(id, v);
        });

        modalEl.addEventListener('click', (e) => {
            const btnQ = e.target.closest('.pm-quitar');
            if (btnQ) {
                quitarDocumento(btnQ.dataset.id);
                return;
            }
        });

        // Submit por fetch (igual que antes)
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();

                if (!btn) return;

                // Si no hay docs, no enviar
                if (Object.keys(documentosSeleccionados).length === 0) return;

                btn.disabled = true;
                btn.innerHTML = 'Procesando pagos...';

                const formData = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => {
                    if (!res.ok) throw new Error('Error registrando pagos');
                    return res.json();
                })
                .then(data => {
                    if (!data.ok) throw new Error('Respuesta inválida');

                    // limpiar selección guardada
                    localStorage.removeItem('documentosSeleccionados');

                    // descargar x empresa
                    if (Array.isArray(data.downloads)) {
                        data.downloads.forEach((item, index) => {
                            setTimeout(() => {
                                window.location.href = item.url;
                            }, index * 800);
                        });
                    }

                    // feedback
                    if (!$('#msg-pagos-ok')) {
                        const msg = document.createElement('div');
                        msg.id = 'msg-pagos-ok';
                        msg.className = 'alert alert-success mt-3';
                        msg.innerText = 'Pagos procesados correctamente. Se generaron los archivos por empresa.';
                        form.prepend(msg);
                    }

                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-check-circle"></i> Registrar Pagos Seleccionados';
                })
                .catch(err => {
                    $('#msg-pagos-ok')?.remove();

                    if (!$('#msg-pagos-error')) {
                        const msg = document.createElement('div');
                        msg.id = 'msg-pagos-error';
                        msg.className = 'alert alert-danger mt-3';
                        msg.innerText = err?.message || 'Error procesando pagos.';
                        form.prepend(msg);
                    }

                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-check-circle"></i> Registrar Pagos Seleccionados';
                });
            });
        }

        // Reload al cerrar (igual que antes)
        modalEl.addEventListener('hidden.bs.modal', () => {
            window.location.reload();
        });
    });

})();
</script>
