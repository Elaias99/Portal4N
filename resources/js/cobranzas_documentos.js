function toggleFechaEstado(select, id) {
    const inputFecha = document.getElementById('fecha-input-' + id);
    const hiddenFecha = document.getElementById('fecha-hidden-' + id);

    if (['Abono', 'Pago', 'Pronto pago', 'Cobranza judicial'].includes(select.value)) {
        if (inputFecha) inputFecha.style.display = 'block';
    } else {
        if (inputFecha) {
            inputFecha.style.display = 'none';
            inputFecha.value = '';
        }
        if (hiddenFecha) hiddenFecha.value = '';
    }
}



document.addEventListener('DOMContentLoaded', () => {
    // =====================================================
    // FACTORY MASIVO CxC - SELECCIÓN DE DOCUMENTOS
    // =====================================================
    const STORAGE_KEY_FACTORY = 'documentosFactorySeleccionadosCxC';

    const checkAllFactory = document.getElementById('check-all-documentos-factory');
    const modalFactory = document.getElementById('modalFactoryMasivo');
    const btnFactoryMasivo = document.getElementById('btn-factory-masivo-documentos');

    const formFactoryMasivo = document.getElementById('form-factory-masivo');
    const tbodyFactory = document.getElementById('factory-masivo-documentos-seleccionados');
    const templateFactory = document.getElementById('factory-masivo-row-template');
    const alertaSinSeleccion = document.getElementById('factory-masivo-sin-seleccion');
    const totalGeneralFactory = document.getElementById('factory-masivo-total-general');
    const btnSubmitFactory = document.getElementById('btn-submit-factory-masivo');

    function getSeleccionFactory() {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEY_FACTORY)) || {};
        } catch (error) {
            return {};
        }
    }

    function saveSeleccionFactory(data) {
        localStorage.setItem(STORAGE_KEY_FACTORY, JSON.stringify(data));
    }

    function addDocumentoFactory(cb) {
        const seleccion = getSeleccionFactory();
        const id = cb.dataset.id;

        seleccion[id] = {
            id: id,
            empresa: cb.dataset.empresa || '',
            folio: cb.dataset.folio || '',
            razon: cb.dataset.razon || '',
            rut: cb.dataset.rut || '',
            fechaDocto: cb.dataset.fechaDocto || '',
            fechaVencimiento: cb.dataset.fechaVencimiento || '',
            saldo: Number(cb.dataset.saldo || 0),
            total: Number(cb.dataset.total || 0),
        };

        saveSeleccionFactory(seleccion);
    }

    function removeDocumentoFactory(id) {
        const seleccion = getSeleccionFactory();

        delete seleccion[id];

        saveSeleccionFactory(seleccion);

        const checkbox = document.querySelector(
            '.check-documento-factory[data-id="' + id + '"]'
        );

        if (checkbox) {
            checkbox.checked = false;
        }

        actualizarCheckAllFactory();
    }

    function actualizarCheckAllFactory() {
        if (!checkAllFactory) {
            return;
        }

        const checks = document.querySelectorAll('.check-documento-factory');

        if (checks.length === 0) {
            checkAllFactory.checked = false;
            checkAllFactory.indeterminate = false;
            return;
        }

        const checksMarcados = Array.from(checks).filter(cb => cb.checked);

        checkAllFactory.checked = checksMarcados.length === checks.length;
        checkAllFactory.indeterminate =
            checksMarcados.length > 0 && checksMarcados.length < checks.length;
    }

    function formatCLP(value) {
        return Number(value || 0).toLocaleString('es-CL', {
            style: 'currency',
            currency: 'CLP',
            maximumFractionDigits: 0,
        });
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function renderFactoryMasivoModal() {
        if (!tbodyFactory || !templateFactory) {
            return;
        }

        const seleccion = getSeleccionFactory();
        const documentos = Object.values(seleccion);

        tbodyFactory.innerHTML = '';

        let total = 0;

        if (documentos.length === 0) {
            if (alertaSinSeleccion) {
                alertaSinSeleccion.style.display = 'block';
            }

            if (totalGeneralFactory) {
                totalGeneralFactory.textContent = formatCLP(0);
            }

            if (btnSubmitFactory) {
                btnSubmitFactory.disabled = true;
            }

            return;
        }

        if (alertaSinSeleccion) {
            alertaSinSeleccion.style.display = 'none';
        }

        if (btnSubmitFactory) {
            btnSubmitFactory.disabled = false;
        }

        documentos.forEach(doc => {
            const saldo = Number(doc.saldo || 0);
            total += saldo;

            let rowHtml = templateFactory.innerHTML;

            rowHtml = rowHtml
                .replaceAll('__ID__', escapeHtml(doc.id))
                .replaceAll('__EMPRESA__', escapeHtml(doc.empresa))
                .replaceAll('__FOLIO__', escapeHtml(doc.folio))
                .replaceAll('__RAZON__', escapeHtml(doc.razon))
                .replaceAll('__RUT__', escapeHtml(doc.rut))
                .replaceAll('__SALDO_FORMAT__', formatCLP(saldo));

            tbodyFactory.insertAdjacentHTML('beforeend', rowHtml);
        });

        if (totalGeneralFactory) {
            totalGeneralFactory.textContent = formatCLP(total);
        }
    }

    function toggleBancoOtroFactoryMasivo(select) {
        const documentoId = select.dataset.documentoId;

        const wrapper = document.querySelector(
            '.js-factory-masivo-banco-otro-wrapper[data-documento-id="' + documentoId + '"]'
        );

        const input = document.querySelector(
            '.js-factory-masivo-banco-otro[data-documento-id="' + documentoId + '"]'
        );

        if (!wrapper || !input) {
            return;
        }

        if (select.value === '__otro__') {
            wrapper.style.display = 'block';
            input.required = true;
        } else {
            wrapper.style.display = 'none';
            input.required = false;
            input.value = '';
        }
    }

    // Restaurar checkboxes al cargar/paginar
    const seleccionInicial = getSeleccionFactory();

    document.querySelectorAll('.check-documento-factory').forEach(cb => {
        const id = cb.dataset.id;

        if (seleccionInicial[id]) {
            cb.checked = true;
        }

        cb.addEventListener('change', function () {
            if (this.checked) {
                addDocumentoFactory(this);
            } else {
                removeDocumentoFactory(this.dataset.id);
            }

            actualizarCheckAllFactory();
        });
    });

    actualizarCheckAllFactory();

    // Seleccionar todos los documentos visibles disponibles
    checkAllFactory?.addEventListener('change', function () {
        document.querySelectorAll('.check-documento-factory').forEach(cb => {
            cb.checked = this.checked;

            if (this.checked) {
                addDocumentoFactory(cb);
            } else {
                removeDocumentoFactory(cb.dataset.id);
            }
        });

        actualizarCheckAllFactory();
    });

    // Renderizar modal al abrir
    btnFactoryMasivo?.addEventListener('click', function () {
        renderFactoryMasivoModal();
    });

    modalFactory?.addEventListener('show.bs.modal', function () {
        renderFactoryMasivoModal();
    });

    // Quitar documento desde el modal
    document.addEventListener('click', function (event) {
        const btnQuitar = event.target.closest('.js-factory-masivo-quitar');

        if (!btnQuitar) {
            return;
        }

        removeDocumentoFactory(btnQuitar.dataset.documentoId);
        renderFactoryMasivoModal();
    });

    // Mostrar input "Otro" por fila
    document.addEventListener('change', function (event) {
        const selectBanco = event.target.closest('.js-factory-masivo-banco');

        if (!selectBanco) {
            return;
        }

        toggleBancoOtroFactoryMasivo(selectBanco);
    });

    // Evitar enviar si no hay selección
    formFactoryMasivo?.addEventListener('submit', function (event) {
        const seleccion = getSeleccionFactory();

        if (Object.keys(seleccion).length === 0) {
            event.preventDefault();
            alert('Debe seleccionar al menos un documento para registrar Factory masivo.');
            return;
        }

        if (typeof this.reportValidity === 'function' && !this.reportValidity()) {
            event.preventDefault();
        }
    });
});