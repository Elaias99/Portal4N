(() => {
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

    function hoyISO() {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
    }

    document.addEventListener('DOMContentLoaded', () => {
        const modalEl = $('#modalPagoComprasProgramadas');
        const form = $('#form-pago-compras-programadas');

        if (!modalEl || !form || typeof bootstrap === 'undefined' || !bootstrap.Modal) return;

        const modal = new bootstrap.Modal(modalEl);

        const btnPagarHoy = $('#btn-pagar-compras-programadas-hoy');
        const btnPagarAtrasadas = $('#btn-pagar-compras-programadas-atrasadas');

        const tbody = $('#pago-compras-programadas-seleccionados');
        const countEl = $('#pago-compras-programadas-count');
        const alertaSin = $('#pago-compras-programadas-sin-seleccion');
        const hiddenWrap = $('#inputs-pago-compras-programadas-seleccionados');
        const fechaPago = $('#fecha-pago-compras-programadas');
        const totalGeneralEl = $('#pago-compras-programadas-total-general');
        const totalesEmpresaEl = $('#pago-compras-programadas-totales-empresa');

        const btnSubmit = $('#btn-submit-pago-compras-programadas');
        const btnCancelar = $('#btn-cancelar-pago-compras-programadas');
        const btnCerrarX = $('#btn-cerrar-x-pago-compras-programadas');

        let documentosSeleccionados = {};
        let pagosProcesados = false;
        let procesando = false;
        let recargaPanelEjecutada = false;

        function limpiarMensajes() {
            $('#msg-pago-programado-ok')?.remove();
            $('#msg-pago-programado-error')?.remove();
        }

        function mostrarExito(mensaje) {
            limpiarMensajes();

            const msg = document.createElement('div');
            msg.id = 'msg-pago-programado-ok';
            msg.className = 'alert alert-success mt-3';
            msg.innerHTML = mensaje;

            form.prepend(msg);
        }

        function mostrarError(mensaje) {
            limpiarMensajes();

            const msg = document.createElement('div');
            msg.id = 'msg-pago-programado-error';
            msg.className = 'alert alert-danger mt-3';
            msg.textContent = mensaje;

            form.prepend(msg);
        }

        function setEstadoInicialModal() {
            pagosProcesados = false;
            procesando = false;
            recargaPanelEjecutada = false;
            limpiarMensajes();

            if (btnSubmit) {
                btnSubmit.disabled = Object.keys(documentosSeleccionados).length === 0;
                btnSubmit.textContent = 'Registrar pagos';
            }

            if (btnCancelar) {
                btnCancelar.disabled = false;
                btnCancelar.textContent = 'Cancelar';
            }

            if (btnCerrarX) {
                btnCerrarX.disabled = false;
            }
        }

        function setEstadoProcesando() {
            procesando = true;

            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.textContent = 'Procesando pagos...';
            }

            if (btnCancelar) {
                btnCancelar.disabled = true;
            }

            if (btnCerrarX) {
                btnCerrarX.disabled = true;
            }
        }

        function setEstadoFinalModal() {
            pagosProcesados = true;
            procesando = false;

            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.textContent = 'Pagos registrados';
            }

            if (btnCancelar) {
                btnCancelar.disabled = false;
                btnCancelar.textContent = 'Cerrar';
            }

            if (btnCerrarX) {
                btnCerrarX.disabled = false;
            }
        }

        function obtenerSeleccionadosDesdePanel() {
            const checks = [
                ...$$('.chk-compra-programada-hoy:checked'),
                ...$$('.chk-compra-programada-atrasada:checked'),
            ];

            const docs = {};

            checks.forEach(chk => {
                const row = chk.closest('tr');
                const empresa = row?.children?.[1]?.innerText?.trim() || '';
                const fechaProgramada = row?.children?.[4]?.innerText?.trim() || '';
                const id = String(chk.dataset.id || chk.value || '');

                if (!id) return;

                docs[id] = {
                    id,
                    programadoId: chk.dataset.programadoId || '',
                    empresa,
                    folio: chk.dataset.folio || '',
                    proveedor: chk.dataset.proveedor || '',
                    rut: chk.dataset.rut || '',
                    fechaProgramada,
                    saldo: Number(chk.dataset.saldo || 0),
                };
            });

            return docs;
        }

        function renderTotales() {
            if (!totalGeneralEl || !totalesEmpresaEl) return;

            const docs = Object.values(documentosSeleccionados);

            if (docs.length === 0) {
                totalGeneralEl.textContent = '$0';
                totalesEmpresaEl.innerHTML = '';
                return;
            }

            let totalGeneral = 0;
            const porEmpresa = {};

            docs.forEach(doc => {
                const empresa = doc.empresa || 'Sin empresa';
                const monto = Number(doc.saldo || 0);

                totalGeneral += monto;
                porEmpresa[empresa] = (porEmpresa[empresa] || 0) + monto;
            });

            totalGeneralEl.textContent = fmtCLP(totalGeneral);

            let html = '';
            Object.entries(porEmpresa).forEach(([empresa, monto]) => {
                html += `<div><strong>${escapeHtml(empresa)}:</strong> ${fmtCLP(monto)}</div>`;
            });

            totalesEmpresaEl.innerHTML = html;
        }

        function renderResumen() {
            if (!tbody || !countEl || !alertaSin || !hiddenWrap || !btnSubmit) return;

            tbody.innerHTML = '';
            hiddenWrap.innerHTML = '';

            const docs = Object.values(documentosSeleccionados);
            countEl.textContent = String(docs.length);

            if (docs.length === 0) {
                alertaSin.classList.remove('d-none');
                btnSubmit.disabled = true;
                renderTotales();
                return;
            }

            alertaSin.classList.add('d-none');

            docs.forEach(doc => {
                const tr = document.createElement('tr');

                tr.innerHTML = `
                    <td class="text-start">${escapeHtml(doc.empresa)}</td>
                    <td class="text-start fw-semibold">${escapeHtml(doc.folio)}</td>
                    <td class="text-start">${escapeHtml(doc.proveedor)}</td>
                    <td class="text-start">${escapeHtml(doc.rut)}</td>
                    <td class="text-start">${escapeHtml(doc.fechaProgramada)}</td>
                    <td class="text-end">${fmtCLP(doc.saldo)}</td>
                    <td class="text-center">
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-danger btn-quitar-doc-programado"
                            data-id="${doc.id}"
                            ${pagosProcesados || procesando ? 'disabled' : ''}
                        >
                            ✕
                        </button>
                    </td>
                `;

                tbody.appendChild(tr);

                const inputDocumento = document.createElement('input');
                inputDocumento.type = 'hidden';
                inputDocumento.name = 'documentos[]';
                inputDocumento.value = doc.id;
                hiddenWrap.appendChild(inputDocumento);
            });

            renderTotales();
        }

        function desmarcarCheckboxDocumento(id) {
            $$('.chk-compra-programada-hoy, .chk-compra-programada-atrasada').forEach(chk => {
                const chkId = String(chk.dataset.id || chk.value || '');
                if (chkId === String(id)) {
                    chk.checked = false;
                }
            });

            const checkAllHoy = $('#check-all-compras-programadas-hoy');
            const checksHoy = $$('.chk-compra-programada-hoy');
            if (checkAllHoy) {
                checkAllHoy.checked = checksHoy.length > 0 && checksHoy.every(chk => chk.checked);
            }

            const checkAllAtrasadas = $('#check-all-compras-programadas-atrasadas');
            const checksAtrasadas = $$('.chk-compra-programada-atrasada');
            if (checkAllAtrasadas) {
                checkAllAtrasadas.checked = checksAtrasadas.length > 0 && checksAtrasadas.every(chk => chk.checked);
            }
        }

        function quitarDocumento(id) {
            if (pagosProcesados || procesando) return;

            delete documentosSeleccionados[String(id)];
            desmarcarCheckboxDocumento(id);
            renderResumen();
        }

        function abrirModalConSeleccion() {
            documentosSeleccionados = obtenerSeleccionadosDesdePanel();

            if (Object.keys(documentosSeleccionados).length === 0) {
                alert('Debes seleccionar al menos un documento.');
                return;
            }

            if (fechaPago && !fechaPago.value) {
                fechaPago.value = hoyISO();
            }

            setEstadoInicialModal();
            renderResumen();
            modal.show();
        }

        function dispararDescargas(downloads) {
            if (!Array.isArray(downloads) || downloads.length === 0) return;

            downloads.forEach((item, index) => {
                setTimeout(() => {
                    const link = document.createElement('a');
                    link.href = item.url;
                    link.download = '';
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                }, index * 800);
            });
        }

        function recargarPanelSiCorresponde() {
            if (!pagosProcesados || recargaPanelEjecutada) {
                return false;
            }

            recargaPanelEjecutada = true;
            window.location.reload();

            return true;
        }

        function cerrarModal(e = null) {
            if (procesando) return;

            if (pagosProcesados && e) {
                e.preventDefault();
                e.stopPropagation();
            }

            modal.hide();

            if (pagosProcesados) {
                setTimeout(() => {
                    recargarPanelSiCorresponde();
                }, 250);
            }
        }

        if (btnPagarHoy) {
            btnPagarHoy.addEventListener('click', abrirModalConSeleccion);
        }

        if (btnPagarAtrasadas) {
            btnPagarAtrasadas.addEventListener('click', abrirModalConSeleccion);
        }

        if (btnCancelar) {
            btnCancelar.addEventListener('click', (e) => {
                cerrarModal(e);
            });
        }

        if (btnCerrarX) {
            btnCerrarX.addEventListener('click', (e) => {
                cerrarModal(e);
            });
        }

        modalEl.addEventListener('click', (e) => {
            const btnQuitar = e.target.closest('.btn-quitar-doc-programado');
            if (!btnQuitar) return;

            quitarDocumento(btnQuitar.dataset.id);
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (pagosProcesados || procesando) return;

            if (Object.keys(documentosSeleccionados).length === 0) {
                mostrarError('Debes seleccionar al menos un documento.');
                return;
            }

            setEstadoProcesando();
            limpiarMensajes();

            const formData = new FormData(form);

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                const data = await res.json();

                if (!res.ok || !data?.ok) {
                    throw new Error(data?.message || 'Error procesando pagos.');
                }

                dispararDescargas(data.downloads || []);

                const procesados = Number(data.procesados || 0);
                const omitidos = Number(data.omitidos || 0);

                mostrarExito(
                    `Pagos registrados correctamente. Procesados: <strong>${procesados}</strong>. Omitidos: <strong>${omitidos}</strong>. Se generaron los archivos por empresa.`
                );

                setEstadoFinalModal();
                renderResumen();
            } catch (error) {
                procesando = false;

                mostrarError(error?.message || 'Error procesando pagos.');

                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.textContent = 'Registrar pagos';
                }

                if (btnCancelar) {
                    btnCancelar.disabled = false;
                    btnCancelar.textContent = 'Cancelar';
                }

                if (btnCerrarX) {
                    btnCerrarX.disabled = false;
                }
            }
        });




        function limpiarModalAlCerrar() {
            tbody.innerHTML = '';
            hiddenWrap.innerHTML = '';
            totalesEmpresaEl.innerHTML = '';
            totalGeneralEl.textContent = '$0';
            countEl.textContent = '0';
            alertaSin.classList.add('d-none');

            limpiarMensajes();
            documentosSeleccionados = {};
            pagosProcesados = false;
            procesando = false;
            recargaPanelEjecutada = false;
        }

        modalEl.addEventListener('hidden.bs.modal', () => {
            if (recargarPanelSiCorresponde()) {
                return;
            }

            limpiarModalAlCerrar();
        });






    });
})();