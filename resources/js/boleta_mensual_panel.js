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

    const formPagoProgramados = document.getElementById('form-pago-programados-hoy');
    const btnSubmitPagoProgramados = document.getElementById('btn-submit-pago-programados-hoy');

    const formEliminar = document.getElementById('form-eliminar-programados');
    const inputsEliminar = document.getElementById('inputs-eliminar-programados');

    let modal = null;
    let pagoProgramadoProcesado = false;
    let recargaPanelEjecutada = false;

    function mostrarLoaderPagina(timeout = 30000) {
        window.pageLoader?.show({ timeout });
    }

    const TEXTO_CONFIRMAR_PAGO = 'Confirmar pago';
    const TEXTO_PROCESANDO = 'Procesando...';
    const TEXTO_PAGO_REGISTRADO = 'Pago registrado';
    const TEXTO_CANCELAR = 'Cancelar';
    const TEXTO_CERRAR = 'Cerrar';

    if (modalEl) {
        modal = new bootstrap.Modal(modalEl);
    }

    function formatMonto(valor) {
        return '$' + Number(valor || 0).toLocaleString('es-CL');
    }

    function textoFolios(cantidad) {
        return `${cantidad} ${cantidad === 1 ? 'folio' : 'folios'}`;
    }

    function descargarExcels(downloads) {
        return new Promise((resolve) => {
            if (!Array.isArray(downloads) || downloads.length === 0) {
                resolve();
                return;
            }

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

            setTimeout(resolve, downloads.length * 800 + 300);
        });
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

    function getFeedbackPagoProgramados() {
        if (!modalEl) return null;

        let feedback = modalEl.querySelector('#panel-pago-programados-feedback');

        if (!feedback) {
            feedback = document.createElement('div');
            feedback.id = 'panel-pago-programados-feedback';
            feedback.className = 'alert d-none mb-3';
            feedback.setAttribute('role', 'alert');

            const form = modalEl.querySelector('#form-pago-programados-hoy');

            if (form) {
                form.prepend(feedback);
            }
        }

        return feedback;
    }

    function mostrarFeedbackPagoProgramados(tipo, mensaje) {
        const feedback = getFeedbackPagoProgramados();
        if (!feedback) return;

        feedback.className = `alert alert-${tipo} mb-3`;
        feedback.textContent = mensaje;
        feedback.classList.remove('d-none');
    }

    function limpiarFeedbackPagoProgramados() {
        const feedback = getFeedbackPagoProgramados();
        if (!feedback) return;

        feedback.className = 'alert d-none mb-3';
        feedback.textContent = '';
    }

    function setEstadoSubmitPagoProgramados(estado) {
        if (!btnSubmitPagoProgramados) return;

        if (estado === 'procesando') {
            btnSubmitPagoProgramados.disabled = true;
            btnSubmitPagoProgramados.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2"
                      role="status"
                      aria-hidden="true"></span>
                ${TEXTO_PROCESANDO}
            `;
            return;
        }

        if (estado === 'registrado') {
            btnSubmitPagoProgramados.disabled = true;
            btnSubmitPagoProgramados.textContent = TEXTO_PAGO_REGISTRADO;
            return;
        }

        btnSubmitPagoProgramados.disabled = false;
        btnSubmitPagoProgramados.textContent = TEXTO_CONFIRMAR_PAGO;
    }

    function resetEstadoModalPagoProgramados() {
        pagoProgramadoProcesado = false;
        recargaPanelEjecutada = false;

        limpiarFeedbackPagoProgramados();
        setEstadoSubmitPagoProgramados('normal');

        if (btnCancelarModal) {
            btnCancelarModal.disabled = false;
            btnCancelarModal.textContent = TEXTO_CANCELAR;
        }

        if (btnCerrarX) {
            btnCerrarX.disabled = false;
        }
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
        limpiarFeedbackPagoProgramados();
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

        resetEstadoModalPagoProgramados();

        resumenWrap.innerHTML = '';
        inputsWrap.innerHTML = '';

        seleccionados.forEach(chk => {
            const saldoFormateado = new Intl.NumberFormat('es-CL')
                .format(Number(chk.dataset.saldo || 0));

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${chk.dataset.folio || ''}</td>
                <td>${chk.dataset.emisor || ''}</td>
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




    function recargarPanelSiCorresponde() {
        if (!pagoProgramadoProcesado || recargaPanelEjecutada) {
            return false;
        }

        recargaPanelEjecutada = true;

        mostrarLoaderPagina();
        window.location.reload();

        return true;
    }

    function cerrarModalPagoProgramados(e = null) {
        if (pagoProgramadoProcesado) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }

            if (modal) {
                modal.hide();
            } else if (window.$) {
                $('#modalPagoProgramadosHoy').modal('hide');
            }

            setTimeout(() => {
                recargarPanelSiCorresponde();
            }, 250);

            return;
        }

        if (modal) {
            modal.hide();
        } else if (window.$) {
            $('#modalPagoProgramadosHoy').modal('hide');
        }
    }

    if (btnCerrarX) {
        btnCerrarX.addEventListener('click', cerrarModalPagoProgramados);
    }

    if (btnCancelarModal) {
        btnCancelarModal.addEventListener('click', cerrarModalPagoProgramados);
    }

    function manejarCierreModalPagoProgramados() {
        if (recargarPanelSiCorresponde()) {
            return;
        }

        limpiarModalPago();
        resetEstadoModalPagoProgramados();
    }

    if (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', manejarCierreModalPagoProgramados);
    }

    if (window.$) {
        $('#modalPagoProgramadosHoy').on('hidden.bs.modal', manejarCierreModalPagoProgramados);
    }









    if (formPagoProgramados) {
        formPagoProgramados.addEventListener('submit', async (e) => {
            e.preventDefault();

            limpiarFeedbackPagoProgramados();
            setEstadoSubmitPagoProgramados('procesando');

            if (btnCancelarModal) {
                btnCancelarModal.disabled = true;
            }

            if (btnCerrarX) {
                btnCerrarX.disabled = true;
            }

            try {
                const formData = new FormData(formPagoProgramados);

                const response = await fetch(formPagoProgramados.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json().catch(() => null);

                if (!response.ok) {
                    if (data?.message) {
                        throw new Error(data.message);
                    }

                    throw new Error('No fue posible registrar el pago.');
                }

                if (!data || data.ok !== true) {
                    throw new Error('Respuesta inválida del servidor.');
                }

                const totalPagado = formatMonto(data.total_pagado || 0);
                const cantidadDescargas = Array.isArray(data.downloads)
                    ? data.downloads.length
                    : 0;

                mostrarFeedbackPagoProgramados(
                    'info',
                    `${data.message || 'Pago registrado correctamente.'} Preparando ${cantidadDescargas} archivo(s) Excel...`
                );

                await descargarExcels(data.downloads);

                pagoProgramadoProcesado = true;

                setEstadoSubmitPagoProgramados('registrado');

                if (btnCancelarModal) {
                    btnCancelarModal.disabled = false;
                    btnCancelarModal.textContent = TEXTO_CERRAR;
                }

                if (btnCerrarX) {
                    btnCerrarX.disabled = false;
                }

                mostrarFeedbackPagoProgramados(
                    'success',
                    `${data.message || 'Pago registrado correctamente.'} Total pagado: ${totalPagado}. Excel generado(s): ${cantidadDescargas}.`
                );
            } catch (error) {
                console.error('[PANEL HONORARIOS] Error registrando pago:', error);

                setEstadoSubmitPagoProgramados('normal');

                if (btnCancelarModal) {
                    btnCancelarModal.disabled = false;
                    btnCancelarModal.textContent = TEXTO_CANCELAR;
                }

                if (btnCerrarX) {
                    btnCerrarX.disabled = false;
                }

                mostrarFeedbackPagoProgramados(
                    'danger',
                    error?.message || 'Ocurrió un error al registrar el pago.'
                );
            }
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