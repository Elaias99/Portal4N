// ============================================================
// PAGO MASIVO
// ============================================================
document.addEventListener('DOMContentLoaded', () => {

    // =========================
    // ELEMENTOS BASE
    // =========================
    const checkAll            = document.getElementById('check-all-honorarios');
    const modal               = document.getElementById('modalPagoMasivo');
    const inputsWrap          = document.getElementById('inputs-honorarios-seleccionados');
    const bloqueBuscador      = document.getElementById('bloque-buscador');
    const bloqueResumen       = document.getElementById('honorarios-seleccionados');
    const btnPagar            = document.getElementById('btn-pagar-seleccionados');
    const totalPagoWrap       = document.getElementById('total-pago-masivo');
    const empresaTotalesWrap  = document.getElementById('empresa-totales-pago-masivo');
    const formPagoMasivo      = document.getElementById('form-pago-masivo');
    const submitBtn           = document.getElementById('btn-submit-pago-masivo');
    const btnCancelar         = document.getElementById('btn-cancelar-pago-masivo');

    if (
        !checkAll ||
        !modal ||
        !inputsWrap ||
        !bloqueBuscador ||
        !bloqueResumen ||
        !btnPagar ||
        !totalPagoWrap ||
        !empresaTotalesWrap ||
        !formPagoMasivo ||
        !submitBtn ||
        !btnCancelar
    ) {
        return;
    }

    // =========================
    // TEXTOS
    // =========================
    const TEXTO_CANCELAR = 'Cancelar';
    const TEXTO_CERRAR = 'Cerrar';
    const TEXTO_CONFIRMAR = 'Confirmar pago masivo';
    const TEXTO_PROCESANDO = 'Procesando...';
    const TEXTO_REGISTRADO = 'Pago registrado';

    // =========================
    // STORAGE (FUENTE DE VERDAD)
    // =========================
    const STORAGE_KEY = 'honorarios_seleccionados';

    function loadSeleccionados() {
        try {
            const raw = sessionStorage.getItem(STORAGE_KEY);
            if (!raw) return new Map();

            return new Map(Object.entries(JSON.parse(raw)));
        } catch (e) {
            console.error('Error cargando seleccionados', e);
            return new Map();
        }
    }

    function saveSeleccionados(map) {
        const obj = Object.fromEntries(map);
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(obj));
    }

    function clearSeleccionados() {
        sessionStorage.removeItem(STORAGE_KEY);
    }

    function resetEstadoModalPagoMasivo() {
        submitBtn.disabled = false;
        submitBtn.textContent = TEXTO_CONFIRMAR;
        btnCancelar.textContent = TEXTO_CANCELAR;
    }

    function textoFolios(cantidad) {
        return `${cantidad} ${cantidad === 1 ? 'folio' : 'folios'}`;
    }

    function getDataHonorarioFromCheckbox(chk) {
        return {
            id: chk.value,
            empresa: chk.dataset.empresa,
            rut: chk.dataset.rut,
            emisor: chk.dataset.emisor,
            folio: chk.dataset.folio,
            fecha_emision: chk.dataset.fechaEmision,
            fecha_vencimiento: chk.dataset.fechaVencimiento,
            monto: chk.dataset.monto,
            saldo: chk.dataset.saldo,
        };
    }

    function actualizarEstadoCheckAll() {
        const checks = Array.from(document.querySelectorAll('.chk-honorario:not(:disabled)'));

        if (checks.length === 0) {
            checkAll.checked = false;
            checkAll.indeterminate = false;
            return;
        }

        const seleccionadosVisibles = checks.filter(chk => seleccionados.has(chk.value)).length;

        checkAll.checked = seleccionadosVisibles === checks.length;
        checkAll.indeterminate = seleccionadosVisibles > 0 && seleccionadosVisibles < checks.length;
    }

    // =========================
    // ESTADO INTERNO (PERSISTENTE)
    // =========================
    const seleccionados = loadSeleccionados();

    // =========================
    // FUNCIÓN: RENDER DEL MODAL
    // =========================
    function renderPagoMasivoSeleccionados() {
        inputsWrap.innerHTML = '';
        bloqueResumen.innerHTML = '';
        empresaTotalesWrap.innerHTML = '';

        let total = 0;
        let totalFolios = 0;
        const totalesPorEmpresa = new Map();

        seleccionados.forEach(h => {
            const monto = Number(h.monto || 0);
            const empresa = h.empresa || 'Sin empresa';

            total += monto;
            totalFolios += 1;

            if (!totalesPorEmpresa.has(empresa)) {
                totalesPorEmpresa.set(empresa, {
                    monto: 0,
                    cantidad: 0,
                });
            }

            const actual = totalesPorEmpresa.get(empresa);
            actual.monto += monto;
            actual.cantidad += 1;

            const tr = document.createElement('tr');

            tr.innerHTML = `
                <td>${h.empresa || ''}</td>
                <td>${h.rut || ''}</td>
                <td>${h.emisor || ''}</td>
                <td>${h.folio || ''}</td>
                <td>${h.fecha_emision || ''}</td>
                <td>${h.fecha_vencimiento || ''}</td>
                <td class="text-end">${monto.toLocaleString('es-CL')}</td>
                <td class="text-center">
                    <button type="button"
                            class="btn btn-sm btn-outline-danger btn-quitar-honorario-modal"
                            data-id="${h.id}">
                        ×
                    </button>
                </td>
            `;

            bloqueResumen.appendChild(tr);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'honorarios[]';
            input.value = h.id;

            inputsWrap.appendChild(input);
        });

        totalPagoWrap.innerHTML = `
            $${total.toLocaleString('es-CL')}
            <span class="small text-muted ms-1">(${textoFolios(totalFolios)})</span>
        `;

        let htmlTotalesEmpresa = '';
        totalesPorEmpresa.forEach((data, empresa) => {
            htmlTotalesEmpresa += `
                <div>
                    <strong>${empresa}:</strong> $${data.monto.toLocaleString('es-CL')}
                    <span class="text-muted ms-1">(${textoFolios(data.cantidad)})</span>
                </div>
            `;
        });

        empresaTotalesWrap.innerHTML = htmlTotalesEmpresa;
    }

    // =========================
    // REHIDRATAR CHECKBOXES
    // =========================
    document.querySelectorAll('.chk-honorario').forEach(chk => {
        if (seleccionados.has(chk.value)) {
            chk.checked = true;
        }
    });

    actualizarEstadoCheckAll();

    // =========================
    // CHECKBOXES INDIVIDUALES
    // =========================
    document.addEventListener('change', (e) => {
        const chk = e.target.closest('.chk-honorario');
        if (!chk) return;

        const id = chk.value;

        if (chk.checked) {
            seleccionados.set(id, getDataHonorarioFromCheckbox(chk));
        } else {
            seleccionados.delete(id);
        }

        saveSeleccionados(seleccionados);
        actualizarEstadoCheckAll();
    });

    // =========================
    // CHECKBOX "SELECCIONAR TODOS"
    // =========================
    checkAll.addEventListener('change', () => {
        const checks = document.querySelectorAll('.chk-honorario:not(:disabled)');

        checks.forEach(chk => {
            chk.checked = checkAll.checked;

            const id = chk.value;

            if (checkAll.checked) {
                seleccionados.set(id, getDataHonorarioFromCheckbox(chk));
            } else {
                seleccionados.delete(id);
            }
        });

        saveSeleccionados(seleccionados);
        actualizarEstadoCheckAll();
    });

    // =========================
    // BOTÓN PAGAR (ABRE MODAL)
    // =========================
    btnPagar.addEventListener('click', () => {
        if (seleccionados.size === 0) {
            alert('Debes seleccionar al menos un honorario.');
            return;
        }

        $('#modalPagoMasivo').modal('show');
    });

    // =========================
    // AL ABRIR MODAL
    // =========================
    $('#modalPagoMasivo').on('show.bs.modal', function () {
        resetEstadoModalPagoMasivo();
        bloqueBuscador.style.display = 'none';
        renderPagoMasivoSeleccionados();
    });

    // =========================
    // QUITAR HONORARIO DESDE MODAL
    // =========================
    document.addEventListener('click', (e) => {
        const btnQuitar = e.target.closest('.btn-quitar-honorario-modal');
        if (!btnQuitar) return;

        const id = btnQuitar.dataset.id;
        if (!id) return;

        seleccionados.delete(id);
        saveSeleccionados(seleccionados);

        const chkTabla = document.querySelector(`.chk-honorario[value="${id}"]`);
        if (chkTabla) {
            chkTabla.checked = false;
        }

        actualizarEstadoCheckAll();

        if (seleccionados.size === 0) {
            $('#modalPagoMasivo').modal('hide');
            return;
        }

        renderPagoMasivoSeleccionados();
    });

    // =========================
    // AL CERRAR MODAL
    // =========================
    let pagoMasivoConfirmado = false;

    $('#modalPagoMasivo').on('hidden.bs.modal', function () {
        bloqueBuscador.style.display = '';
        inputsWrap.innerHTML = '';
        bloqueResumen.innerHTML = '';
        totalPagoWrap.textContent = '$0';
        empresaTotalesWrap.innerHTML = '';

        if (pagoMasivoConfirmado) {
            location.reload();
            return;
        }

        sessionStorage.removeItem('honorarios_seleccionados');
        location.reload();
    });

    // =========================
    // SUBMIT PAGO MASIVO
    // =========================
    formPagoMasivo.addEventListener('submit', (e) => {
        e.preventDefault();

        submitBtn.disabled = true;
        submitBtn.textContent = TEXTO_PROCESANDO;

        const formData = new FormData(formPagoMasivo);

        fetch(formPagoMasivo.action, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => {
            if (!res.ok) throw new Error('Error registrando pago masivo');
            return res.json();
        })
        .then(data => {
            if (!data || data.ok !== true) throw new Error('Respuesta inválida');

            if (Array.isArray(data.downloads)) {
                data.downloads.forEach((item, index) => {
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

            pagoMasivoConfirmado = true;
            clearSeleccionados();

            submitBtn.disabled = true;
            submitBtn.textContent = TEXTO_REGISTRADO;
            btnCancelar.textContent = TEXTO_CERRAR;
        })
        .catch(err => {
            console.error('[PAGO MASIVO] Error:', err);

            alert(err?.message || 'Error procesando pago masivo');

            submitBtn.disabled = false;
            submitBtn.textContent = TEXTO_CONFIRMAR;
            btnCancelar.textContent = TEXTO_CANCELAR;
        });
    });

});


// ============================================================
// PRÓXIMO PAGO
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    const btnProximoPago = document.getElementById('btn-proximo-pago-seleccionados');
    const modalProximoPago = document.getElementById('modalProximoPago');
    const formProximoPago = document.getElementById('form-proximo-pago');
    const inputsWrap = document.getElementById('inputs-proximos-pagos-seleccionados');
    const resumenWrap = document.getElementById('proximos-pagos-seleccionados');
    const submitBtn = document.getElementById('btn-submit-proximo-pago');
    const btnCancelar = document.getElementById('btn-cancelar-proximo-pago');
    const totalGeneralWrap = document.getElementById('honorarios-proximo-pago-total-general');
    const totalesEmpresaWrap = document.getElementById('honorarios-proximo-pago-totales-empresa');

    if (
        !btnProximoPago ||
        !modalProximoPago ||
        !formProximoPago ||
        !inputsWrap ||
        !resumenWrap ||
        !submitBtn ||
        !btnCancelar ||
        !totalGeneralWrap ||
        !totalesEmpresaWrap
    ) {
        return;
    }

    const STORAGE_KEY = 'honorarios_seleccionados';
    const TEXTO_CANCELAR = 'Cancelar';
    const TEXTO_CERRAR = 'Cerrar';
    const TEXTO_GUARDAR = 'Guardar próximo pago';
    const TEXTO_PROCESANDO = 'Procesando...';
    const TEXTO_GUARDADO = 'Próximo pago guardado';

    function formatMonto(valor) {
        return '$' + Number(valor || 0).toLocaleString('es-CL');
    }

    function textoFolios(cantidad) {
        return `${cantidad} ${cantidad === 1 ? 'folio' : 'folios'}`;
    }

    function clearSeleccionados() {
        sessionStorage.removeItem(STORAGE_KEY);
    }

    function loadSeleccionados() {
        try {
            const raw = sessionStorage.getItem(STORAGE_KEY);
            if (!raw) return new Map();

            return new Map(Object.entries(JSON.parse(raw)));
        } catch (e) {
            console.error('Error cargando seleccionados para próximo pago', e);
            return new Map();
        }
    }

    function saveSeleccionados(map) {
        const obj = Object.fromEntries(map);
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(obj));
    }

    function renderSeleccionados() {
        const seleccionados = loadSeleccionados();

        inputsWrap.innerHTML = '';
        resumenWrap.innerHTML = '';
        totalGeneralWrap.textContent = '$0';
        totalesEmpresaWrap.innerHTML = '';

        if (seleccionados.size === 0) {
            resumenWrap.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">
                        No hay honorarios seleccionados.
                    </td>
                </tr>
            `;
            return seleccionados;
        }

        let totalGeneral = 0;
        let totalFolios = 0;
        const totalesPorEmpresa = new Map();

        seleccionados.forEach(h => {
            const saldo = Number(h.saldo || 0);
            const empresa = h.empresa || 'Sin empresa';

            totalGeneral += saldo;
            totalFolios += 1;

            if (!totalesPorEmpresa.has(empresa)) {
                totalesPorEmpresa.set(empresa, {
                    monto: 0,
                    cantidad: 0,
                });
            }

            const actual = totalesPorEmpresa.get(empresa);
            actual.monto += saldo;
            actual.cantidad += 1;

            const tr = document.createElement('tr');

            tr.innerHTML = `
                <td>${h.folio || ''}</td>
                <td>${h.emisor || ''}</td>
                <td>${h.rut || ''}</td>
                <td class="text-end">${saldo.toLocaleString('es-CL')}</td>
                <td class="text-center">
                    <button type="button"
                            class="btn btn-sm btn-outline-danger btn-quitar-proximo-pago-modal"
                            data-id="${h.id}">
                        ×
                    </button>
                </td>
            `;

            resumenWrap.appendChild(tr);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'honorarios[]';
            input.value = h.id;

            inputsWrap.appendChild(input);
        });

        totalGeneralWrap.innerHTML = `
            ${formatMonto(totalGeneral)}
            <span class="small text-muted ms-1">(${textoFolios(totalFolios)})</span>
        `;

        let htmlTotalesEmpresa = '';
        totalesPorEmpresa.forEach((data, empresa) => {
            htmlTotalesEmpresa += `
                <div>
                    <strong>${empresa}:</strong> ${formatMonto(data.monto)}
                    <span class="text-muted ms-1">(${textoFolios(data.cantidad)})</span>
                </div>
            `;
        });

        totalesEmpresaWrap.innerHTML = htmlTotalesEmpresa;

        return seleccionados;
    }

    function resetEstadoModalProximoPago() {
        formProximoPago.reset();
        inputsWrap.innerHTML = '';
        resumenWrap.innerHTML = '';
        totalGeneralWrap.textContent = '$0';
        totalesEmpresaWrap.innerHTML = '';

        submitBtn.disabled = false;
        submitBtn.textContent = TEXTO_GUARDAR;

        btnCancelar.textContent = TEXTO_CANCELAR;
    }

    let proximoPagoProcesado = false;

    btnProximoPago.addEventListener('click', () => {
        const seleccionados = loadSeleccionados();

        if (seleccionados.size === 0) {
            alert('Debes seleccionar al menos un honorario.');
            return;
        }

        $('#modalProximoPago').modal('show');
    });

    $('#modalProximoPago').on('show.bs.modal', function () {
        proximoPagoProcesado = false;
        resetEstadoModalProximoPago();
        renderSeleccionados();
    });

    document.addEventListener('click', (e) => {
        const btnQuitar = e.target.closest('.btn-quitar-proximo-pago-modal');
        if (!btnQuitar) return;

        const id = btnQuitar.dataset.id;
        if (!id) return;

        const seleccionados = loadSeleccionados();
        seleccionados.delete(id);
        saveSeleccionados(seleccionados);

        const chkTabla = document.querySelector(`.chk-honorario[value="${id}"]`);
        if (chkTabla) {
            chkTabla.checked = false;
        }

        if (seleccionados.size === 0) {
            $('#modalProximoPago').modal('hide');
            return;
        }

        renderSeleccionados();
    });

    $('#modalProximoPago').on('hidden.bs.modal', function () {
        inputsWrap.innerHTML = '';
        resumenWrap.innerHTML = '';
        totalGeneralWrap.textContent = '$0';
        totalesEmpresaWrap.innerHTML = '';
        formProximoPago.reset();

        if (proximoPagoProcesado) {
            proximoPagoProcesado = false;
            location.reload();
            return;
        }

        resetEstadoModalProximoPago();
    });

    formProximoPago.addEventListener('submit', (e) => {
        e.preventDefault();

        submitBtn.disabled = true;
        submitBtn.textContent = TEXTO_PROCESANDO;

        const formData = new FormData(formProximoPago);

        fetch(formProximoPago.action, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => {
            if (!res.ok) throw new Error('Error registrando próximo pago');
            return res.json();
        })
        .then(data => {
            if (!data || data.ok !== true) {
                throw new Error('Respuesta inválida');
            }

            if (Array.isArray(data.downloads)) {
                data.downloads.forEach((item, index) => {
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

            clearSeleccionados();
            proximoPagoProcesado = true;

            submitBtn.disabled = true;
            submitBtn.textContent = TEXTO_GUARDADO;

            btnCancelar.textContent = TEXTO_CERRAR;
        })
        .catch(err => {
            console.error('[PRÓXIMO PAGO] Error:', err);

            alert(err?.message || 'Error procesando próximo pago');

            submitBtn.disabled = false;
            submitBtn.textContent = TEXTO_GUARDAR;

            btnCancelar.textContent = TEXTO_CANCELAR;
        });
    });
});