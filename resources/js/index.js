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
    $('#modalPagoMasivo').on('hidden.bs.modal', function () {

        bloqueBuscador.style.display = '';
        inputsWrap.innerHTML = '';
        bloqueResumen.innerHTML = '';

        // 👉 AQUÍ es donde corresponde limpiar y refrescar
        sessionStorage.removeItem('honorarios_seleccionados');
        location.reload();
    });


});

