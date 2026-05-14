document.addEventListener('DOMContentLoaded', () => {
    const STORAGE_KEY = 'documentosSeleccionados';

    const btnProximoPago = document.getElementById('btn-proximo-pago-documentos');
    const modalEl = document.getElementById('modalProximoPagoCompras');
    const form = document.getElementById('form-proximo-pago-compras');
    const resumenWrap = document.getElementById('proximos-pagos-compras-seleccionados');
    const inputsWrap = document.getElementById('inputs-proximos-pagos-compras-seleccionados');
    const programadosWrap = document.getElementById('inputs-programados-eliminar-compras');
    const btnEliminar = document.getElementById('btn-eliminar-proximo-pago-compras');
    const btnCerrarX = document.getElementById('btn-cerrar-x-proximo-pago-compras');
    const btnCancelar = document.getElementById('btn-cancelar-proximo-pago-compras');
    const submitBtn = document.getElementById('btn-submit-proximo-pago-compras');

    const totalGeneralEl = document.getElementById('proximos-pagos-total-general');
    const totalesEmpresaEl = document.getElementById('proximos-pagos-totales-empresa');

    if (!btnProximoPago || !modalEl || !form || !resumenWrap || !inputsWrap) {
        return;
    }

    const modal = new bootstrap.Modal(modalEl);
    let proximoPagoProcesado = false;


    function mostrarLoaderPagina(timeout = 30000) {
        window.pageLoader?.show({ timeout });
    }

    function recargarPaginaConLoader(delay = 250) {
        setTimeout(() => {
            mostrarLoaderPagina();
            location.reload();
        }, delay);
    }








    function formatMonto(valor) {
        return '$' + Number(valor || 0).toLocaleString('es-CL');
    }

    function textoDocumentos(cantidad) {
        return `${cantidad} ${cantidad === 1 ? 'documento' : 'documentos'}`;
    }

    function setEstadoInicialModal() {
        proximoPagoProcesado = false;

        if (btnCancelar) {
            btnCancelar.textContent = 'Cancelar';
        }

        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Guardar próximo pago';
        }

        if (btnEliminar) {
            btnEliminar.disabled = false;
            btnEliminar.textContent = 'Eliminar próximos pagos';
        }
    }

    function setEstadoFinalModal() {
        proximoPagoProcesado = true;

        if (btnCancelar) {
            btnCancelar.textContent = 'Cerrar';
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Próximo pago guardado';
        }
    }

    function getSeleccionStorage() {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEY)) || {};
        } catch (e) {
            console.error('Error cargando documentos seleccionados para próximo pago', e);
            return {};
        }
    }

    function saveSeleccion(data) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
    }

    function clearSeleccion() {
        localStorage.removeItem(STORAGE_KEY);
    }

    function getSeleccionDesdeCheckboxes() {
        const seleccion = {};

        document.querySelectorAll('.check-documento:checked').forEach(cb => {
            const id = cb.dataset.id || cb.value;

            seleccion[id] = {
                id: id,
                folio: cb.dataset.folio || '',
                razon: cb.dataset.razon || '',
                rut: cb.dataset.rut || '',
                empresa: cb.dataset.empresa || 'Sin empresa',
                fechaDocto: cb.dataset.fechaDocto || '',
                fechaVencimiento: cb.dataset.fechaVencimiento || '',
                saldo: Number(cb.dataset.saldo || 0),
                total: Number(cb.dataset.total || 0),
                programadoId: cb.dataset.programadoId ? Number(cb.dataset.programadoId) : null,

                formaPago: cb.dataset.formaPago || '',
                omitidoBanco: cb.dataset.omitidoBanco === '1',
            };
        });

        return seleccion;
    }

    function getSeleccion() {
        const seleccionStorage = getSeleccionStorage();
        const seleccionCheckboxes = getSeleccionDesdeCheckboxes();

        const seleccion = { ...seleccionStorage };

        Object.entries(seleccionCheckboxes).forEach(([id, doc]) => {
            seleccion[id] = {
                ...(seleccion[id] || {}),
                ...doc,
            };
        });

        if (Object.keys(seleccion).length > 0) {
            saveSeleccion(seleccion);
        }

        return seleccion;
    }

    function quitarDocumento(id) {
        const seleccion = getSeleccionStorage();
        delete seleccion[id];
        saveSeleccion(seleccion);

        document.querySelectorAll('.check-documento').forEach(cb => {
            const cbId = cb.dataset.id || cb.value;
            if (String(cbId) === String(id)) {
                cb.checked = false;
            }
        });

        renderSeleccionados();
    }

    function getProgramadosSeleccionados(docs) {
        return docs
            .map(doc => Number(doc.programadoId || 0))
            .filter(id => id > 0);
    }

    function actualizarBotonEliminar(docs) {
        if (!btnEliminar) return;

        const programados = getProgramadosSeleccionados(docs);

        if (programados.length > 0) {
            btnEliminar.classList.remove('d-none');
        } else {
            btnEliminar.classList.add('d-none');
        }
    }

    function renderTotales(docs) {
        if (totalGeneralEl) {
            const totalGeneral = docs.reduce((acc, doc) => acc + Number(doc.saldo || 0), 0);
            const totalDocumentos = docs.length;

            totalGeneralEl.innerHTML = `
                ${formatMonto(totalGeneral)}
                <span class="small text-muted ms-1">(${textoDocumentos(totalDocumentos)})</span>
            `;
        }

        if (totalesEmpresaEl) {
            if (docs.length === 0) {
                totalesEmpresaEl.innerHTML = '';
                return;
            }

            const agrupados = docs.reduce((acc, doc) => {
                const empresa = doc.empresa || 'Sin empresa';

                if (!acc[empresa]) {
                    acc[empresa] = {
                        total: 0,
                        cantidad: 0,
                    };
                }

                acc[empresa].total += Number(doc.saldo || 0);
                acc[empresa].cantidad += 1;

                return acc;
            }, {});

            totalesEmpresaEl.innerHTML = Object.entries(agrupados)
                .map(([empresa, data]) => {
                    return `
                        <div>
                            ${empresa}: <strong>${formatMonto(data.total)}</strong>
                            <span class="small text-muted ms-1">(${textoDocumentos(data.cantidad)})</span>
                        </div>
                    `;
                })
                .join('');
        }
    }

    function renderSeleccionados() {
        const seleccion = getSeleccion();

        resumenWrap.innerHTML = '';
        inputsWrap.innerHTML = '';
        if (programadosWrap) {
            programadosWrap.innerHTML = '';
        }

        const docs = Object.values(seleccion);

        if (docs.length === 0) {
            resumenWrap.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">
                        No hay documentos seleccionados.
                    </td>
                </tr>
            `;

            renderTotales([]);
            actualizarBotonEliminar(docs);
            return docs;
        }

        docs.forEach(doc => {
            const row = document.createElement('tr');


            const avisoBanco = doc.omitidoBanco
            ? `
                <div class="small text-muted mt-1">
                    <span class="badge rounded-pill bg-light text-secondary border">
                        Portal Proveedor · no exporta banco
                    </span>
                </div>
            `
            : '';

            row.innerHTML = `
                <td class="text-start">${doc.folio ?? '-'}</td>
                <td class="text-start">
                    <div>${doc.razon ?? '-'}</div>
                    ${avisoBanco}
                </td>
                <td class="text-start">${doc.rut ?? '-'}</td>
                <td class="text-end">${formatMonto(doc.saldo || 0)}</td>
                <td class="text-center">
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger pp-quitar"
                        data-id="${doc.id}">
                        ✕
                    </button>
                </td>
            `;

            resumenWrap.appendChild(row);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'documentos[]';
            input.value = doc.id;
            inputsWrap.appendChild(input);

            if (programadosWrap && doc.programadoId) {
                const inputProgramado = document.createElement('input');
                inputProgramado.type = 'hidden';
                inputProgramado.name = 'programados[]';
                inputProgramado.value = doc.programadoId;
                programadosWrap.appendChild(inputProgramado);
            }
        });

        renderTotales(docs);
        actualizarBotonEliminar(docs);
        return docs;
    }

    btnProximoPago.addEventListener('click', () => {
        const docs = Object.values(getSeleccion());

        if (docs.length === 0) {
            alert('Debes seleccionar al menos un documento.');
            return;
        }

        setEstadoInicialModal();
        renderSeleccionados();
        modal.show();
    });

    modalEl.addEventListener('click', (e) => {
        const btnQuitar = e.target.closest('.pp-quitar');
        if (!btnQuitar) return;

        quitarDocumento(btnQuitar.dataset.id);
    });

    btnEliminar?.addEventListener('click', () => {
        const docs = Object.values(getSeleccion());
        const programados = getProgramadosSeleccionados(docs);

        if (programados.length === 0) {
            alert('No hay próximos pagos programados para eliminar.');
            return;
        }

        const ok = confirm('¿Seguro que deseas eliminar los próximos pagos seleccionados?');
        if (!ok) return;

        const formTmp = document.createElement('form');
        formTmp.method = 'POST';
        formTmp.action = btnEliminar.dataset.url;

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrf || '';
        formTmp.appendChild(csrfInput);

        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        formTmp.appendChild(methodInput);

        programados.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'programados[]';
            input.value = id;
            formTmp.appendChild(input);
        });

        clearSeleccion();
        document.body.appendChild(formTmp);
        formTmp.submit();
    });


    btnCerrarX?.addEventListener('click', () => {
        modal.hide();

        if (proximoPagoProcesado) {
            recargarPaginaConLoader();
        }
    });


    btnCancelar?.addEventListener('click', () => {
        modal.hide();

        if (proximoPagoProcesado) {
            recargarPaginaConLoader();
        }
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
        resumenWrap.innerHTML = '';
        inputsWrap.innerHTML = '';
        if (programadosWrap) {
            programadosWrap.innerHTML = '';
        }
        form.reset();

        renderTotales([]);

        if (!proximoPagoProcesado) {
            setEstadoInicialModal();
        }
    });

    form.addEventListener('submit', (e) => {
        e.preventDefault();

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Procesando...';
        }

        const formData = new FormData(form);

        fetch(form.action, {
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

            clearSeleccion();
            setEstadoFinalModal();
        })
        .catch(err => {
            alert(err?.message || 'Error procesando próximo pago');

            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Guardar próximo pago';
            }
        });
    });
});