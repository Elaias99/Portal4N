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
        const totalesPorEmpresa = new Map();

        seleccionados.forEach(h => {
            const monto = Number(h.monto || 0);
            const empresa = h.empresa || 'Sin empresa';

            total += monto;

            if (!totalesPorEmpresa.has(empresa)) {
                totalesPorEmpresa.set(empresa, 0);
            }

            totalesPorEmpresa.set(
                empresa,
                totalesPorEmpresa.get(empresa) + monto
            );

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

        totalPagoWrap.textContent = '$' + total.toLocaleString('es-CL');

        let htmlTotalesEmpresa = '';
        totalesPorEmpresa.forEach((monto, empresa) => {
            htmlTotalesEmpresa += `
                <div>
                    <strong>${empresa}:</strong> $${monto.toLocaleString('es-CL')}
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

    // =========================
    // CHECKBOXES INDIVIDUALES
    // =========================
    document.addEventListener('change', (e) => {
        const chk = e.target.closest('.chk-honorario');
        if (!chk) return;

        const id = chk.value;

        if (chk.checked) {
            seleccionados.set(id, {
                id: id,
                empresa: chk.dataset.empresa,
                rut: chk.dataset.rut,
                emisor: chk.dataset.emisor,
                folio: chk.dataset.folio,
                fecha_emision: chk.dataset.fechaEmision,
                fecha_vencimiento: chk.dataset.fechaVencimiento,
                monto: chk.dataset.monto,
                saldo: chk.dataset.saldo,
            });
        } else {
            seleccionados.delete(id);
            checkAll.checked = false;
        }

        saveSeleccionados(seleccionados);
    });

    // =========================
    // CHECKBOX "SELECCIONAR TODOS"
    // =========================
    checkAll.addEventListener('change', () => {
        const checks = document.querySelectorAll('.chk-honorario:not(:disabled)');

        seleccionados.clear();

        checks.forEach(chk => {
            chk.checked = checkAll.checked;

            if (checkAll.checked) {
                const id = chk.value;

                seleccionados.set(id, {
                    id: id,
                    empresa: chk.dataset.empresa,
                    rut: chk.dataset.rut,
                    emisor: chk.dataset.emisor,
                    folio: chk.dataset.folio,
                    fecha_emision: chk.dataset.fechaEmision,
                    fecha_vencimiento: chk.dataset.fechaVencimiento,
                    monto: chk.dataset.monto,
                    saldo: chk.dataset.saldo,
                });
            }
        });

        saveSeleccionados(seleccionados);
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

        if (seleccionados.size === 0) {
            checkAll.checked = false;
            $('#modalPagoMasivo').modal('hide');
            return;
        }

        checkAll.checked = false;
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
            alert(err?.message || 'Error procesando pago masivo');

            submitBtn.disabled = false;
            submitBtn.textContent = TEXTO_CONFIRMAR;
            btnCancelar.textContent = TEXTO_CANCELAR;
        });
    });

});

document.addEventListener('DOMContentLoaded', () => {

    const btnProximoPago   = document.getElementById('btn-proximo-pago-seleccionados');
    const modalProximoPago = document.getElementById('modalProximoPago');
    const formProximoPago  = document.getElementById('form-proximo-pago');
    const inputsWrap       = document.getElementById('inputs-proximos-pagos-seleccionados');
    const resumenWrap      = document.getElementById('proximos-pagos-seleccionados');
    const submitBtn        = document.getElementById('btn-submit-proximo-pago');
    const btnCancelar      = document.getElementById('btn-cancelar-proximo-pago');

    if (
        !btnProximoPago ||
        !modalProximoPago ||
        !formProximoPago ||
        !inputsWrap ||
        !resumenWrap ||
        !submitBtn ||
        !btnCancelar
    ) {
        return;
    }

    const STORAGE_KEY = 'honorarios_seleccionados';
    const TEXTO_CANCELAR = 'Cancelar';
    const TEXTO_CERRAR = 'Cerrar';
    const TEXTO_GUARDAR = 'Guardar próximo pago';
    const TEXTO_PROCESANDO = 'Procesando...';
    const TEXTO_GUARDADO = 'Próximo pago guardado';

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

    function renderSeleccionados() {
        const seleccionados = loadSeleccionados();

        inputsWrap.innerHTML = '';
        resumenWrap.innerHTML = '';

        seleccionados.forEach(h => {
            const card = document.createElement('div');
            card.className = 'border rounded p-2 mb-2 bg-light';

            card.innerHTML = `
                <div><strong>Folio:</strong> ${h.folio}</div>
                <div><strong>RUT:</strong> ${h.rut}</div>
                <div><strong>Emisor:</strong> ${h.emisor}</div>
                <div><strong>Saldo:</strong> ${h.saldo}</div>
            `;

            resumenWrap.appendChild(card);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'honorarios[]';
            input.value = h.id;

            inputsWrap.appendChild(input);
        });

        return seleccionados;
    }

    function resetEstadoModalProximoPago() {
        formProximoPago.reset();
        inputsWrap.innerHTML = '';
        resumenWrap.innerHTML = '';

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

    $('#modalProximoPago').on('hidden.bs.modal', function () {
        inputsWrap.innerHTML = '';
        resumenWrap.innerHTML = '';
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
            alert(err?.message || 'Error procesando próximo pago');

            submitBtn.disabled = false;
            submitBtn.textContent = TEXTO_GUARDAR;

            btnCancelar.textContent = TEXTO_CANCELAR;
        });
    });
});