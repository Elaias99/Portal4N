document.addEventListener('DOMContentLoaded', () => {
    const STORAGE_KEY = 'documentosSeleccionados';

    const btnProximoPago = document.getElementById('btn-proximo-pago-documentos');
    const modalEl = document.getElementById('modalProximoPagoCompras');
    const form = document.getElementById('form-proximo-pago-compras');
    const resumenWrap = document.getElementById('proximos-pagos-compras-seleccionados');
    const inputsWrap = document.getElementById('inputs-proximos-pagos-compras-seleccionados');
    const btnCerrarX = document.getElementById('btn-cerrar-x-proximo-pago-compras');
    const btnCancelar = document.getElementById('btn-cancelar-proximo-pago-compras');
    const submitBtn = document.getElementById('btn-submit-proximo-pago-compras');

    if (!btnProximoPago || !modalEl || !form || !resumenWrap || !inputsWrap) {
        return;
    }

    const modal = new bootstrap.Modal(modalEl);
    let proximoPagoProcesado = false;

    function setEstadoInicialModal() {
        proximoPagoProcesado = false;

        if (btnCancelar) {
            btnCancelar.textContent = 'Cancelar';
        }

        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Guardar próximo pago';
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
                saldo: Number(cb.dataset.saldo || 0),
                total: Number(cb.dataset.total || 0),
            };
        });

        return seleccion;
    }

    function getSeleccion() {
        let seleccion = getSeleccionStorage();

        if (Object.keys(seleccion).length === 0) {
            seleccion = getSeleccionDesdeCheckboxes();

            if (Object.keys(seleccion).length > 0) {
                saveSeleccion(seleccion);
            }
        }

        return seleccion;
    }

    function renderSeleccionados() {
        const seleccion = getSeleccion();

        resumenWrap.innerHTML = '';
        inputsWrap.innerHTML = '';

        const docs = Object.values(seleccion);

        docs.forEach(doc => {
            const card = document.createElement('div');
            card.className = 'border rounded p-2 mb-2 bg-light';

            card.innerHTML = `
                <div><strong>Folio:</strong> ${doc.folio ?? '-'}</div>
                <div><strong>Razón social:</strong> ${doc.razon ?? '-'}</div>
                <div><strong>RUT:</strong> ${doc.rut ?? '-'}</div>
                <div><strong>Saldo:</strong> ${Number(doc.saldo || 0).toLocaleString('es-CL')}</div>
            `;

            resumenWrap.appendChild(card);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'documentos[]';
            input.value = doc.id;

            inputsWrap.appendChild(input);
        });

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





    btnCerrarX?.addEventListener('click', () => {
        modal.hide();

        if (proximoPagoProcesado) {
            setTimeout(() => {
                location.reload();
            }, 250);
        }
    });

    btnCancelar?.addEventListener('click', () => {
        modal.hide();

        if (proximoPagoProcesado) {
            setTimeout(() => {
                location.reload();
            }, 250);
        }
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
        resumenWrap.innerHTML = '';
        inputsWrap.innerHTML = '';
        form.reset();

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