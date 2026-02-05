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
    // ESTADO INTERNO
    // =========================
    const seleccionados = new Map(); // id => data

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
    });

    // =========================
    // BOTÓN PAGAR (ABRE MODAL)
    // =========================
    btnPagar.addEventListener('click', () => {

        if (seleccionados.size === 0) {
            alert('Debes seleccionar al menos un honorario.');
            return;
        }

        // Bootstrap 4 → jQuery
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

            // Visual
            const div = document.createElement('div');
            div.className = 'border rounded p-2 mb-2 bg-light';

            div.innerHTML = `
                <div><strong>Folio:</strong> ${h.folio}</div>
                <div><strong>RUT:</strong> ${h.rut}</div>
                <div><strong>Emisor:</strong> ${h.emisor}</div>
                <div><strong>Saldo:</strong> ${h.saldo}</div>
            `;

            bloqueResumen.appendChild(div);

            // Hidden input
            const input = document.createElement('input');
            input.type  = 'hidden';
            input.name  = 'honorarios[]';
            input.value = h.id;

            inputsWrap.appendChild(input);
        });
    });

    // =========================
    // AL CERRAR MODAL: LIMPIAR TODO
    // =========================
    $('#modalPagoMasivo').on('hidden.bs.modal', function () {

        bloqueBuscador.style.display = '';
        inputsWrap.innerHTML = '';
        bloqueResumen.innerHTML = '';
        seleccionados.clear();

        document.querySelectorAll('.chk-honorario').forEach(chk => {
            chk.checked = false;
        });

        checkAll.checked = false;
    });

});

// =========================
// SUBMIT PAGO MASIVO (AJAX)
// =========================
$('#form-pago-masivo').on('submit', function (e) {
    e.preventDefault();

    const form = $(this);

    $.ajax({
        url: form.attr('action'),
        method: 'POST',
        data: form.serialize(),
        dataType: 'json',

        success(response) {

            if (!response.success) {
                alert('No se pudo procesar el pago masivo.');
                return;
            }

            // 1️⃣ Cerrar modal
            $('#modalPagoMasivo').modal('hide');

            // 2️⃣ Refrescar vista para ver cambios
            setTimeout(() => {
                location.reload();
            }, 300);

            // 3️⃣ Descargar Excel
            if (response.download_url) {
                window.location.href = response.download_url;
            }
        },

        error(xhr) {
            console.error(xhr);
            alert('Ocurrió un error al procesar el pago masivo.');
        }
    });
});




