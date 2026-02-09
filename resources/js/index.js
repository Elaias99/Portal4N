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

// =========================
// SUBMIT PAGO MASIVO (AJAX)
// =========================
// $('#form-pago-masivo').on('submit', function (e) {
//     e.preventDefault();

//     const form = $(this);

//     $.ajax({
//         url: form.attr('action'),
//         method: 'POST',
//         data: form.serialize(),
//         dataType: 'json',

//         success(response) {

//             if (!response.success) {
//                 alert('No se pudo procesar el pago masivo.');
//                 return;
//             }

//             if (Array.isArray(response.downloads)) {

//                 response.downloads.forEach(file => {

//                     const link = document.createElement('a');
//                     link.href = file.download_url;
//                     link.download = '';
//                     document.body.appendChild(link);

//                     link.click();

//                     document.body.removeChild(link);
//                 });

//             }



//             // 2️⃣ Feedback visual (opcional pero recomendado)
//             const footer = document.querySelector('#modalPagoMasivo .modal-footer');

//             if (!document.getElementById('msg-pago-exitoso')) {
//                 const msg = document.createElement('div');
//                 msg.id = 'msg-pago-exitoso';
//                 msg.className = 'alert alert-success w-100 mt-2';
//                 msg.innerText = 'Pagos registrados correctamente. El archivo Excel fue generado.';
//                 footer.prepend(msg);
//             }

//             // ❌ NO cerrar modal
//             // ❌ NO limpiar sessionStorage aquí
//             // ❌ NO refrescar la vista aquí
//         }

//     });
// });
