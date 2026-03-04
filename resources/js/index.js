document.addEventListener('DOMContentLoaded', () => {

    // =========================
    // ELEMENTOS BASE
    // =========================
    const checkAll        = document.getElementById('check-all-honorarios');
    const modal           = document.getElementById('modalPagoMasivo');
    const inputsWrap      = document.getElementById('inputs-honorarios-seleccionados');
    const bloqueBuscador  = document.getElementById('bloque-buscador');
    const bloqueResumen   = document.getElementById('honorarios-seleccionados');
    const btnPagar        = document.getElementById('btn-pagar-seleccionados');

    if (
        !checkAll ||
        !modal ||
        !inputsWrap ||
        !bloqueBuscador ||
        !bloqueResumen ||
        !btnPagar
    ) {
        return;
    }

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

    // =========================
    // ESTADO INTERNO (PERSISTENTE)
    // =========================
    const seleccionados = loadSeleccionados();

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
                folio: chk.dataset.folio,
                rut: chk.dataset.rut,
                emisor: chk.dataset.emisor,
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
                    folio: chk.dataset.folio,
                    rut: chk.dataset.rut,
                    emisor: chk.dataset.emisor,
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
    // AL ABRIR MODAL: PREPARAR RESUMEN
    // =========================
    $('#modalPagoMasivo').on('show.bs.modal', function () {

        inputsWrap.innerHTML = '';
        bloqueResumen.innerHTML = '';

        // Ocultar buscador (modo tabla)
        bloqueBuscador.style.display = 'none';

        seleccionados.forEach(h => {

            const div = document.createElement('div');
            div.className = 'border rounded p-2 mb-2 bg-light';

            div.innerHTML = `
                <div><strong>Folio:</strong> ${h.folio}</div>
                <div><strong>RUT:</strong> ${h.rut}</div>
                <div><strong>Emisor:</strong> ${h.emisor}</div>
                <div><strong>Saldo:</strong> ${h.saldo}</div>
            `;

            bloqueResumen.appendChild(div);

            const input = document.createElement('input');
            input.type  = 'hidden';
            input.name  = 'honorarios[]';
            input.value = h.id;

            inputsWrap.appendChild(input);
        });
    });

    // =========================
    // AL CERRAR MODAL (SIN CONFIRMAR)
    // =========================
    let pagoMasivoConfirmado = false;

    $('#modalPagoMasivo').on('hidden.bs.modal', function () {
    bloqueBuscador.style.display = '';
    inputsWrap.innerHTML = '';
    bloqueResumen.innerHTML = '';

    // solo recargar si NO fue confirmado (cerrado manual)
    if (!pagoMasivoConfirmado) {
        sessionStorage.removeItem('honorarios_seleccionados');
        location.reload();
    }

    // reset
    pagoMasivoConfirmado = false;
    });




    // =========================
    // SUBMIT PAGO MASIVO (AJAX + descargas múltiples)
    // =========================
    const formPagoMasivo = document.getElementById('form-pago-masivo');

    if (formPagoMasivo) {
    formPagoMasivo.addEventListener('submit', (e) => {
        e.preventDefault();

        const submitBtn = formPagoMasivo.querySelector('button[type="submit"]');
        if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Procesando...';
        }

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

            console.log('Respuesta pago masivo:', data);
            console.log('Cantidad de descargas:', data.downloads.length);

            // disparar descargas
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



            // limpiar selección y cerrar modal
            clearSeleccionados(); // usa tu helper existente
            $('#modalPagoMasivo').modal('hide');

            if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Confirmar pago masivo';
            }
        })
        .catch(err => {
            alert(err?.message || 'Error procesando pago masivo');

            if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Confirmar pago masivo';
            }
        });
    });
    }


});

