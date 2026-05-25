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
    // FACTORING MASIVO CxC - SELECCIÓN DE DOCUMENTOS
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

    // Campos generales del modal masivo
    const inputGlobalCesion = document.getElementById('factory-masivo-global-cesion');
    const selectGlobalBanco = document.getElementById('factory-masivo-global-banco');
    const wrapperGlobalBancoOtro = document.getElementById('factory-masivo-global-banco-otro-wrapper');
    const inputGlobalBancoOtro = document.getElementById('factory-masivo-global-banco-otro');
    const inputGlobalRutFactory = document.getElementById('factory-masivo-global-rut');
    const inputGlobalSaldoLiquido = document.getElementById('factory-masivo-global-saldo-liquido');
    const btnAplicarDatosFactoryMasivo = document.getElementById('btn-aplicar-datos-factory-masivo');

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

    function resetTotalFactoryMasivo() {
        if (totalGeneralFactory) {
            totalGeneralFactory.textContent = formatCLP(0);
        }
    }

    function actualizarTotalFactoryMasivo() {
        const seleccion = getSeleccionFactory();
        const documentos = Object.values(seleccion);

        let totalSaldoPendiente = 0;

        documentos.forEach(doc => {
            totalSaldoPendiente += Number(doc.saldo || 0);
        });

        if (totalGeneralFactory) {
            totalGeneralFactory.textContent = formatCLP(totalSaldoPendiente);
        }
    }

    function renderFactoryMasivoModal() {
        if (!tbodyFactory || !templateFactory) {
            return;
        }

        const seleccion = getSeleccionFactory();
        const documentos = Object.values(seleccion);

        tbodyFactory.innerHTML = '';

        if (documentos.length === 0) {
            if (alertaSinSeleccion) {
                alertaSinSeleccion.style.display = 'block';
            }

            resetTotalFactoryMasivo();

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

        actualizarTotalFactoryMasivo();
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

    function toggleBancoOtroFactoryMasivoGlobal() {
        if (!selectGlobalBanco || !wrapperGlobalBancoOtro || !inputGlobalBancoOtro) {
            return;
        }

        if (selectGlobalBanco.value === '__otro__') {
            wrapperGlobalBancoOtro.style.display = 'block';
            inputGlobalBancoOtro.required = true;
        } else {
            wrapperGlobalBancoOtro.style.display = 'none';
            inputGlobalBancoOtro.required = false;
            inputGlobalBancoOtro.value = '';
        }
    }

    function aplicarDatosGlobalesFactoryMasivo() {
        if (
            !inputGlobalCesion ||
            !selectGlobalBanco ||
            !inputGlobalRutFactory ||
            !inputGlobalSaldoLiquido
        ) {
            return false;
        }

        toggleBancoOtroFactoryMasivoGlobal();

        const cesion = inputGlobalCesion.value.trim();
        const bancoId = selectGlobalBanco.value;
        const bancoOtro = inputGlobalBancoOtro ? inputGlobalBancoOtro.value.trim() : '';
        const rutFactory = inputGlobalRutFactory.value.trim();
        const saldoLiquido = Number(inputGlobalSaldoLiquido.value || 0);

        if (!cesion) {
            inputGlobalCesion.reportValidity();
            return false;
        }

        if (!bancoId) {
            selectGlobalBanco.reportValidity();
            return false;
        }

        if (bancoId === '__otro__' && !bancoOtro) {
            inputGlobalBancoOtro.reportValidity();
            return false;
        }

        if (!rutFactory) {
            inputGlobalRutFactory.reportValidity();
            return false;
        }

        if (inputGlobalSaldoLiquido.value === '') {
            inputGlobalSaldoLiquido.reportValidity();
            return false;
        }

        if (saldoLiquido < 0) {
            inputGlobalSaldoLiquido.reportValidity();
            return false;
        }

        const seleccion = getSeleccionFactory();
        const documentos = Object.values(seleccion);

        const documentosConSaldoMenor = documentos.filter(doc => {
            return saldoLiquido > Number(doc.saldo || 0);
        });

        if (documentosConSaldoMenor.length > 0) {
            const folios = documentosConSaldoMenor
                .map(doc => doc.folio)
                .filter(Boolean)
                .join(', ');

            alert(
                'El saldo líquido no puede ser mayor al saldo pendiente de los documentos seleccionados. ' +
                'Revisa los folios: ' + folios
            );

            return false;
        }

        document
            .querySelectorAll('#factory-masivo-documentos-seleccionados tr[data-documento-id]')
            .forEach(row => {
                const documentoId = row.dataset.documentoId;

                const inputCesionFila = row.querySelector(
                    '[name="documentos[' + documentoId + '][cesion]"]'
                );

                const selectBancoFila = row.querySelector(
                    '[name="documentos[' + documentoId + '][banco_id]"]'
                );

                const inputBancoOtroFila = row.querySelector(
                    '[name="documentos[' + documentoId + '][banco_otro]"]'
                );

                const inputRutFila = row.querySelector(
                    '[name="documentos[' + documentoId + '][rut_factory]"]'
                );

                const inputSaldoLiquidoFila = row.querySelector(
                    '[name="documentos[' + documentoId + '][saldo_liquido]"]'
                );

                if (inputCesionFila) {
                    inputCesionFila.value = cesion;
                }

                if (selectBancoFila) {
                    selectBancoFila.value = bancoId;
                    toggleBancoOtroFactoryMasivo(selectBancoFila);
                }

                if (inputBancoOtroFila) {
                    inputBancoOtroFila.value = bancoId === '__otro__' ? bancoOtro : '';
                }

                if (inputRutFila) {
                    inputRutFila.value = rutFactory;
                }

                if (inputSaldoLiquidoFila) {
                    inputSaldoLiquidoFila.value = saldoLiquido;
                }
            });

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Validar valores finales por fila
    |--------------------------------------------------------------------------
    | Esta validación usa los montos que finalmente quedaron en cada documento,
    | incluyendo ajustes manuales posteriores al botón "Aplicar a todos".
    |--------------------------------------------------------------------------
    */
    function validarFilasFactoryMasivo() {
        const seleccion = getSeleccionFactory();
        const documentosConMontoInvalido = [];

        document
            .querySelectorAll('#factory-masivo-documentos-seleccionados tr[data-documento-id]')
            .forEach(row => {
                const documentoId = row.dataset.documentoId;
                const documento = seleccion[documentoId];

                const inputSaldoLiquido = row.querySelector(
                    '[name="documentos[' + documentoId + '][saldo_liquido]"]'
                );

                if (!documento || !inputSaldoLiquido || inputSaldoLiquido.value === '') {
                    return;
                }

                const saldoPendiente = Number(documento.saldo || 0);
                const saldoLiquido = Number(inputSaldoLiquido.value || 0);

                if (saldoLiquido > saldoPendiente) {
                    documentosConMontoInvalido.push(documento.folio || documentoId);
                }
            });

        if (documentosConMontoInvalido.length > 0) {
            alert(
                'El monto aplicado por Factoring no puede ser mayor al saldo pendiente actual. ' +
                'Revisa los folios: ' + documentosConMontoInvalido.join(', ')
            );

            return false;
        }

        return true;
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

    // Mostrar input "Otro" en los campos generales
    selectGlobalBanco?.addEventListener('change', function () {
        toggleBancoOtroFactoryMasivoGlobal();
    });

    // Aplicar datos generales a todas las filas seleccionadas
    btnAplicarDatosFactoryMasivo?.addEventListener('click', function () {
        const aplicado = aplicarDatosGlobalesFactoryMasivo();

        if (aplicado) {
            alert('Datos de Factoring aplicados a todos los documentos seleccionados.');
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Enviar Factoring masivo sin sobrescribir ajustes individuales
    |--------------------------------------------------------------------------
    | Los datos generales solo se copian al presionar "Aplicar a todos".
    | Al enviar, se validan los valores finales de cada fila tal como el
    | usuario los dejó.
    |--------------------------------------------------------------------------
    */
    formFactoryMasivo?.addEventListener('submit', function (event) {
        const seleccion = getSeleccionFactory();

        if (Object.keys(seleccion).length === 0) {
            event.preventDefault();
            alert('Debe seleccionar al menos un documento para registrar Factoring masivo.');
            return;
        }

        actualizarTotalFactoryMasivo();

        if (typeof this.reportValidity === 'function' && !this.reportValidity()) {
            event.preventDefault();
            return;
        }

        if (!validarFilasFactoryMasivo()) {
            event.preventDefault();
            return;
        }

        localStorage.removeItem(STORAGE_KEY_FACTORY);
    });
});