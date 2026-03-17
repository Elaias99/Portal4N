document.addEventListener('DOMContentLoaded', () => {
    const checkAllHoy = document.getElementById('check-all-programados-hoy');
    const checksHoy = document.querySelectorAll('.chk-programado-hoy');
    const btnPagarHoy = document.getElementById('btn-pagar-programados-hoy');
    const btnEliminarHoy = document.getElementById('btn-eliminar-programados-hoy');

    const modalEl = document.getElementById('modalPagoProgramadosHoy');
    const resumenWrap = document.getElementById('resumen-programados-hoy');
    const inputsWrap = document.getElementById('inputs-programados-hoy');

    const checkAllAtrasados = document.getElementById('check-all-programados-atrasados');
    const checksAtrasados = document.querySelectorAll('.chk-programado-atrasado');
    const btnEliminarAtrasados = document.getElementById('btn-eliminar-programados-atrasados');

    const formEliminar = document.getElementById('form-eliminar-programados');
    const inputsEliminar = document.getElementById('inputs-eliminar-programados');

    let modal = null;

    if (modalEl) {
        modal = new bootstrap.Modal(modalEl);
    }

    if (checkAllHoy) {
        checkAllHoy.addEventListener('change', () => {
            checksHoy.forEach(chk => {
                chk.checked = checkAllHoy.checked;
            });
        });

        checksHoy.forEach(chk => {
            chk.addEventListener('change', () => {
                if (!chk.checked) {
                    checkAllHoy.checked = false;
                }
            });
        });
    }

    if (checkAllAtrasados) {
        checkAllAtrasados.addEventListener('change', () => {
            checksAtrasados.forEach(chk => {
                chk.checked = checkAllAtrasados.checked;
            });
        });

        checksAtrasados.forEach(chk => {
            chk.addEventListener('change', () => {
                if (!chk.checked) {
                    checkAllAtrasados.checked = false;
                }
            });
        });
    }

    if (btnPagarHoy && modalEl && resumenWrap && inputsWrap && modal) {
        btnPagarHoy.addEventListener('click', () => {
            const seleccionados = Array.from(document.querySelectorAll('.chk-programado-hoy:checked'));

            if (seleccionados.length === 0) {
                alert('Debes seleccionar al menos un honorario.');
                return;
            }

            resumenWrap.innerHTML = '';
            inputsWrap.innerHTML = '';

            seleccionados.forEach(chk => {
                const card = document.createElement('div');
                card.className = 'border rounded p-2 mb-2 bg-light';

                card.innerHTML = `
                    <div><strong>Folio:</strong> ${chk.dataset.folio}</div>
                    <div><strong>Emisor:</strong> ${chk.dataset.emisor}</div>
                    <div><strong>Saldo:</strong> ${chk.dataset.saldo}</div>
                `;

                resumenWrap.appendChild(card);

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'honorarios[]';
                input.value = chk.value;

                inputsWrap.appendChild(input);
            });

            modal.show();
        });

        modalEl.addEventListener('hidden.bs.modal', () => {
            resumenWrap.innerHTML = '';
            inputsWrap.innerHTML = '';
        });
    }

    function eliminarProgramados(selector, mensajeConfirmacion) {
        if (!formEliminar || !inputsEliminar) {
            return;
        }

        const seleccionados = Array.from(document.querySelectorAll(`${selector}:checked`));

        if (seleccionados.length === 0) {
            alert('Debes seleccionar al menos un registro programado.');
            return;
        }

        if (!confirm(mensajeConfirmacion)) {
            return;
        }

        inputsEliminar.innerHTML = '';

        const ids = [...new Set(
            seleccionados
                .map(chk => chk.dataset.programadoId)
                .filter(id => id)
        )];

        ids.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'programados[]';
            input.value = id;
            inputsEliminar.appendChild(input);
        });

        formEliminar.submit();
    }

    if (btnEliminarHoy) {
        btnEliminarHoy.addEventListener('click', () => {
            eliminarProgramados(
                '.chk-programado-hoy',
                '¿Deseas quitar la fecha de próximo pago de los honorarios seleccionados?'
            );
        });
    }

    if (btnEliminarAtrasados) {
        btnEliminarAtrasados.addEventListener('click', () => {
            eliminarProgramados(
                '.chk-programado-atrasado',
                '¿Deseas quitar la fecha de próximo pago de los honorarios atrasados seleccionados?'
            );
        });
    }
});