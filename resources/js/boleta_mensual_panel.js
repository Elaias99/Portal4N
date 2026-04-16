document.addEventListener('DOMContentLoaded', () => {
    const checkAllHoy = document.getElementById('check-all-programados-hoy');
    const checksHoy = document.querySelectorAll('.chk-programado-hoy');
    const btnPagarHoy = document.getElementById('btn-pagar-programados-hoy');
    const btnEliminarHoy = document.getElementById('btn-eliminar-programados-hoy');

    const checkAllAtrasados = document.getElementById('check-all-programados-atrasados');
    const checksAtrasados = document.querySelectorAll('.chk-programado-atrasado');
    const btnPagarAtrasados = document.getElementById('btn-pagar-programados-atrasados');
    const btnEliminarAtrasados = document.getElementById('btn-eliminar-programados-atrasados');

    const modalEl = document.getElementById('modalPagoProgramadosHoy');
    const resumenWrap = document.getElementById('programados-seleccionados');
    const inputsWrap = document.getElementById('inputs-programados-hoy');
    const inputFechaPago = document.getElementById('fecha_pago');

    const totalGeneralWrap = document.getElementById('panel-programados-total-general');
    const totalesEmpresaWrap = document.getElementById('panel-programados-totales-empresa');

    const btnCerrarX = document.getElementById('btn-cerrar-x-pago-programados-hoy');
    const btnCancelarModal = document.getElementById('btn-cancelar-pago-programados-hoy');

    const formEliminar = document.getElementById('form-eliminar-programados');
    const inputsEliminar = document.getElementById('inputs-eliminar-programados');

    let modal = null;

    if (modalEl) {
        modal = new bootstrap.Modal(modalEl);
    }

    function formatMonto(valor) {
        return '$' + Number(valor || 0).toLocaleString('es-CL');
    }

    function textoFolios(cantidad) {
        return `${cantidad} ${cantidad === 1 ? 'folio' : 'folios'}`;
    }

    function renderTotalesProgramados(seleccionados) {
        if (!totalGeneralWrap || !totalesEmpresaWrap) {
            return;
        }

        if (!seleccionados || seleccionados.length === 0) {
            totalGeneralWrap.textContent = '$0';
            totalesEmpresaWrap.innerHTML = '';
            return;
        }

        let totalGeneral = 0;
        let totalFolios = 0;
        const totalesPorEmpresa = new Map();

        seleccionados.forEach(chk => {
            const empresa = chk.dataset.empresa || 'Sin empresa';
            const saldo = Number(chk.dataset.saldo || 0);

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
        });

        totalGeneralWrap.innerHTML = `
            ${formatMonto(totalGeneral)}
            <span class="small text-muted ms-1">(${textoFolios(totalFolios)})</span>
        `;

        let html = '';
        totalesPorEmpresa.forEach((data, empresa) => {
            html += `
                <div>
                    <strong>${empresa}:</strong> ${formatMonto(data.monto)}
                    <span class="text-muted ms-1">(${textoFolios(data.cantidad)})</span>
                </div>
            `;
        });

        totalesEmpresaWrap.innerHTML = html;
    }

    function limpiarModalPago() {
        if (resumenWrap) {
            resumenWrap.innerHTML = '';
        }

        if (inputsWrap) {
            inputsWrap.innerHTML = '';
        }

        if (inputFechaPago) {
            inputFechaPago.value = '';
        }

        renderTotalesProgramados([]);
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

    function abrirModalPago(selector) {
        if (!modalEl || !resumenWrap || !inputsWrap || !modal) {
            return;
        }

        const seleccionados = Array.from(document.querySelectorAll(`${selector}:checked`));

        if (seleccionados.length === 0) {
            alert('Debes seleccionar al menos un honorario.');
            return;
        }

        resumenWrap.innerHTML = '';
        inputsWrap.innerHTML = '';

        seleccionados.forEach(chk => {
            const saldoFormateado = new Intl.NumberFormat('es-CL').format(Number(chk.dataset.saldo || 0));

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${chk.dataset.folio}</td>
                <td>${chk.dataset.emisor}</td>
                <td class="text-end">$${saldoFormateado}</td>
            `;

            resumenWrap.appendChild(row);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'honorarios[]';
            input.value = chk.value;

            inputsWrap.appendChild(input);
        });

        renderTotalesProgramados(seleccionados);
        modal.show();
    }

    if (btnPagarHoy) {
        btnPagarHoy.addEventListener('click', () => {
            abrirModalPago('.chk-programado-hoy');
        });
    }

    if (btnPagarAtrasados) {
        btnPagarAtrasados.addEventListener('click', () => {
            abrirModalPago('.chk-programado-atrasado');
        });
    }

    if (btnCerrarX && modal) {
        btnCerrarX.addEventListener('click', () => {
            modal.hide();
        });
    }

    if (btnCancelarModal && modal) {
        btnCancelarModal.addEventListener('click', () => {
            modal.hide();
        });
    }

    if (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', () => {
            limpiarModalPago();
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