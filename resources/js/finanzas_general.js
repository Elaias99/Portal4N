document.addEventListener('DOMContentLoaded', () => {
    const checkAllHoy = document.getElementById('check-all-compras-programadas-hoy');
    const checksHoy = document.querySelectorAll('.chk-compra-programada-hoy');
    const btnPagarHoy = document.getElementById('btn-pagar-compras-programadas-hoy');
    const btnEliminarHoy = document.getElementById('btn-eliminar-compras-programadas-hoy');

    const modalEl = document.getElementById('modalPagoComprasProgramadasHoy');
    const resumenWrap = document.getElementById('resumen-compras-programadas-hoy');
    const inputsWrap = document.getElementById('inputs-compras-programadas-hoy');

    const checkAllAtrasadas = document.getElementById('check-all-compras-programadas-atrasadas');
    const checksAtrasadas = document.querySelectorAll('.chk-compra-programada-atrasada');
    const btnEliminarAtrasadas = document.getElementById('btn-eliminar-compras-programadas-atrasadas');

    const formEliminar = document.getElementById('form-eliminar-programados-compras');
    const inputsEliminar = document.getElementById('inputs-eliminar-programados-compras');

    let modal = null;

    function mostrarLoaderPagina(timeout = 30000) {
        window.pageLoader?.show({ timeout });
    }

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

    if (checkAllAtrasadas) {
        checkAllAtrasadas.addEventListener('change', () => {
            checksAtrasadas.forEach(chk => {
                chk.checked = checkAllAtrasadas.checked;
            });
        });

        checksAtrasadas.forEach(chk => {
            chk.addEventListener('change', () => {
                if (!chk.checked) {
                    checkAllAtrasadas.checked = false;
                }
            });
        });
    }

    if (btnPagarHoy && modalEl && resumenWrap && inputsWrap && modal) {
        btnPagarHoy.addEventListener('click', () => {
            const seleccionados = Array.from(document.querySelectorAll('.chk-compra-programada-hoy:checked'));

            if (seleccionados.length === 0) {
                alert('Debes seleccionar al menos un documento.');
                return;
            }

            resumenWrap.innerHTML = '';
            inputsWrap.innerHTML = '';

            seleccionados.forEach(chk => {
                const card = document.createElement('div');
                card.className = 'border rounded p-2 mb-2 bg-light';

                card.innerHTML = `
                    <div><strong>Folio:</strong> ${chk.dataset.folio}</div>
                    <div><strong>Proveedor:</strong> ${chk.dataset.proveedor}</div>
                    <div><strong>RUT:</strong> ${chk.dataset.rut}</div>
                    <div><strong>Saldo:</strong> ${chk.dataset.saldo}</div>
                `;

                resumenWrap.appendChild(card);

                const inputDocumento = document.createElement('input');
                inputDocumento.type = 'hidden';
                inputDocumento.name = 'documentos[]';
                inputDocumento.value = chk.value;
                inputsWrap.appendChild(inputDocumento);

                const inputOperacion = document.createElement('input');
                inputOperacion.type = 'hidden';
                inputOperacion.name = `operaciones[${chk.value}]`;
                inputOperacion.value = 'pago';
                inputsWrap.appendChild(inputOperacion);
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

        mostrarLoaderPagina();
        formEliminar.submit();

    }

    if (btnEliminarHoy) {
        btnEliminarHoy.addEventListener('click', () => {
            eliminarProgramados(
                '.chk-compra-programada-hoy',
                '¿Deseas quitar la fecha de próximo pago de los documentos seleccionados?'
            );
        });
    }

    if (btnEliminarAtrasadas) {
        btnEliminarAtrasadas.addEventListener('click', () => {
            eliminarProgramados(
                '.chk-compra-programada-atrasada',
                '¿Deseas quitar la fecha de próximo pago de los documentos atrasados seleccionados?'
            );
        });
    }
});