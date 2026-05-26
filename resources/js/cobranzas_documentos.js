function toggleFechaEstado(select, id) {
    const inputFecha = document.getElementById('fecha-input-' + id);
    const hiddenFecha = document.getElementById('fecha-hidden-' + id);

    if (['Abono', 'Pago', 'Pronto pago', 'Cobranza judicial'].includes(select.value)) {
        if (inputFecha) {
            inputFecha.style.display = 'block';
        }
    } else {
        if (inputFecha) {
            inputFecha.style.display = 'none';
            inputFecha.value = '';
        }

        if (hiddenFecha) {
            hiddenFecha.value = '';
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // =====================================================
    // FUNCIONES COMPARTIDAS FACTORING CxC
    // =====================================================
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

    function obtenerNumeroInput(input) {
        if (!input || input.value === '') {
            return null;
        }

        const valor = Number(input.value);

        return Number.isFinite(valor) ? valor : null;
    }

    // =====================================================
    // FACTORING MANUAL CxC - MODAL DE ESTADO
    // =====================================================
    const formulariosFactoryIndividual = document.querySelectorAll(
        '.js-form-factory-individual[data-tiene-factory="0"]'
    );

    /**
     * Calcular vista previa de Factoring individual.
     *
     * Reglas vigentes:
     *
     * Diferencia de Precio =
     *     Monto documento - Monto Líquido - Monto No Anticipado
     *
     * Monto Líquido de la operación =
     *     Monto Líquido ingresado + Diferencia de Precio
     *
     * Monto a Recibir =
     *     Monto Líquido de la operación
     *     - Comisión Total
     *     - Diferencia de Precio
     */
    function actualizarCalculoFactoryIndividual(formulario) {
        if (!formulario || formulario.dataset.tieneFactory === '1') {
            return null;
        }

        const monto = Number(formulario.dataset.monto || 0);

        const inputSaldoLiquido = formulario.querySelector(
            '.js-factory-individual-saldo-liquido'
        );

        const inputMontoNoAnticipado = formulario.querySelector(
            '.js-factory-individual-monto-no-anticipado'
        );

        const inputComisionTotal = formulario.querySelector(
            '.js-factory-individual-comision-total'
        );

        const outputDiferenciaPrecio = formulario.querySelector(
            '.js-factory-individual-diferencia-precio'
        );

        const outputMontoARecibir = formulario.querySelector(
            '.js-factory-individual-monto-a-recibir'
        );

        if (
            !inputSaldoLiquido ||
            !inputMontoNoAnticipado ||
            !inputComisionTotal ||
            !outputDiferenciaPrecio ||
            !outputMontoARecibir
        ) {
            return null;
        }

        inputSaldoLiquido.setCustomValidity('');
        inputMontoNoAnticipado.setCustomValidity('');
        inputComisionTotal.setCustomValidity('');

        const saldoLiquido = obtenerNumeroInput(inputSaldoLiquido);
        const montoNoAnticipado = obtenerNumeroInput(inputMontoNoAnticipado);
        const comisionTotal = obtenerNumeroInput(inputComisionTotal);

        if (saldoLiquido === null || montoNoAnticipado === null) {
            outputDiferenciaPrecio.value = '—';
            outputMontoARecibir.value = '—';

            return {
                completo: false,
                valido: true,
                monto: monto,
                saldoLiquido: saldoLiquido,
                montoNoAnticipado: montoNoAnticipado,
                comisionTotal: comisionTotal,
                diferenciaPrecio: null,
                montoARecibir: null,
            };
        }

        const diferenciaPrecio = monto
            - saldoLiquido
            - montoNoAnticipado;

        outputDiferenciaPrecio.value = formatCLP(diferenciaPrecio);

        if (diferenciaPrecio < 0) {
            const mensaje =
                'La suma del Monto Líquido y el Monto No Anticipado no puede ser mayor al monto pendiente actual del documento.';

            inputSaldoLiquido.setCustomValidity(mensaje);
            inputMontoNoAnticipado.setCustomValidity(mensaje);

            outputMontoARecibir.value = '—';

            return {
                completo: true,
                valido: false,
                monto: monto,
                saldoLiquido: saldoLiquido,
                montoNoAnticipado: montoNoAnticipado,
                comisionTotal: comisionTotal,
                diferenciaPrecio: diferenciaPrecio,
                montoARecibir: null,
            };
        }

        if (comisionTotal === null) {
            outputMontoARecibir.value = '—';

            return {
                completo: false,
                valido: true,
                monto: monto,
                saldoLiquido: saldoLiquido,
                montoNoAnticipado: montoNoAnticipado,
                comisionTotal: comisionTotal,
                diferenciaPrecio: diferenciaPrecio,
                montoARecibir: null,
            };
        }

        const montoLiquidoOperacion = saldoLiquido
            + diferenciaPrecio;

        const montoARecibir = montoLiquidoOperacion
            - comisionTotal
            - diferenciaPrecio;

        outputMontoARecibir.value = formatCLP(montoARecibir);

        if (montoARecibir < 0) {
            inputComisionTotal.setCustomValidity(
                'La Comisión Total genera un Monto a Recibir negativo para la operación.'
            );

            return {
                completo: true,
                valido: false,
                monto: monto,
                saldoLiquido: saldoLiquido,
                montoNoAnticipado: montoNoAnticipado,
                comisionTotal: comisionTotal,
                diferenciaPrecio: diferenciaPrecio,
                montoARecibir: montoARecibir,
            };
        }

        return {
            completo: true,
            valido: true,
            monto: monto,
            saldoLiquido: saldoLiquido,
            montoNoAnticipado: montoNoAnticipado,
            comisionTotal: comisionTotal,
            diferenciaPrecio: diferenciaPrecio,
            montoARecibir: montoARecibir,
        };
    }

    function validarFactoryIndividual(formulario) {
        const calculo = actualizarCalculoFactoryIndividual(formulario);

        if (!calculo) {
            return true;
        }

        if (typeof formulario.reportValidity === 'function' && !formulario.reportValidity()) {
            return false;
        }

        return calculo.valido !== false;
    }

    formulariosFactoryIndividual.forEach(formulario => {
        actualizarCalculoFactoryIndividual(formulario);

        formulario.addEventListener('input', function (event) {
            const campoCalculo = event.target.closest(
                '.js-factory-individual-saldo-liquido, ' +
                '.js-factory-individual-monto-no-anticipado, ' +
                '.js-factory-individual-comision-total'
            );

            if (!campoCalculo) {
                return;
            }

            actualizarCalculoFactoryIndividual(this);
        });

        formulario.addEventListener('submit', function (event) {
            if (!validarFactoryIndividual(this)) {
                event.preventDefault();
            }
        });
    });

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

    const btnSubmitFactory = document.getElementById('btn-submit-factory-masivo');

    // =====================================================
    // RESUMEN CONSOLIDADO DE LA OPERACIÓN MASIVA
    // =====================================================
    const totalDocumentosFactory = document.getElementById('factory-masivo-total-documentos');
    const totalMontoFactory = document.getElementById('factory-masivo-total-general');
    const totalAnticipadoFactory = document.getElementById('factory-masivo-total-liquido');
    const totalDiferenciaPrecioFactory = document.getElementById('factory-masivo-total-diferencia-precio');
    const totalMontoLiquidoResumenFactory = document.getElementById('factory-masivo-total-monto-liquido-resumen');
    const totalPrecioCompraFactory = document.getElementById('factory-masivo-total-precio-compra');
    const totalComisionFactory = document.getElementById('factory-masivo-total-comision');
    const totalMontoARecibirFactory = document.getElementById('factory-masivo-total-monto-a-recibir');

    // =====================================================
    // DATOS GENERALES DE LA OPERACIÓN MASIVA
    // =====================================================
    const inputGlobalCesion = document.getElementById('factory-masivo-global-cesion');
    const selectGlobalBanco = document.getElementById('factory-masivo-global-banco');
    const wrapperGlobalBancoOtro = document.getElementById('factory-masivo-global-banco-otro-wrapper');
    const inputGlobalBancoOtro = document.getElementById('factory-masivo-global-banco-otro');
    const inputGlobalFechaFactory = document.getElementById('factory-masivo-global-fecha');
    const inputGlobalComisionTotal = document.getElementById('factory-masivo-global-comision-total');

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

    function addDocumentoFactory(checkbox) {
        const seleccion = getSeleccionFactory();
        const id = checkbox.dataset.id;
        const datosExistentes = seleccion[id] || {};

        seleccion[id] = {
            id: id,
            empresa: checkbox.dataset.empresa || '',
            folio: checkbox.dataset.folio || '',
            razon: checkbox.dataset.razon || '',
            saldo: Number(checkbox.dataset.saldo || 0),

            /*
            |--------------------------------------------------------------------------
            | Mantener valores personalizados de cada documento
            |--------------------------------------------------------------------------
            | Monto Líquido y Monto No Anticipado son individuales por fila.
            |--------------------------------------------------------------------------
            */
            saldoLiquido: datosExistentes.saldoLiquido ?? '',
            montoNoAnticipado: datosExistentes.montoNoAnticipado ?? '',
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

    function persistirMontosFila(row) {
        if (!row) {
            return;
        }

        const documentoId = row.dataset.documentoId;
        const seleccion = getSeleccionFactory();
        const documento = seleccion[documentoId];

        if (!documento) {
            return;
        }

        const inputSaldoLiquido = row.querySelector(
            '[name="documentos[' + documentoId + '][saldo_liquido]"]'
        );

        const inputMontoNoAnticipado = row.querySelector(
            '[name="documentos[' + documentoId + '][monto_no_anticipado]"]'
        );

        documento.saldoLiquido = inputSaldoLiquido
            ? inputSaldoLiquido.value
            : '';

        documento.montoNoAnticipado = inputMontoNoAnticipado
            ? inputMontoNoAnticipado.value
            : '';

        seleccion[documentoId] = documento;

        saveSeleccionFactory(seleccion);
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

        const checksMarcados = Array.from(checks)
            .filter(checkbox => checkbox.checked);

        checkAllFactory.checked = checksMarcados.length === checks.length;
        checkAllFactory.indeterminate =
            checksMarcados.length > 0 && checksMarcados.length < checks.length;
    }

    function resetTotalesFactoryMasivo() {
        if (inputGlobalComisionTotal) {
            inputGlobalComisionTotal.setCustomValidity('');
        }

        if (totalDocumentosFactory) {
            totalDocumentosFactory.textContent = '0';
        }

        if (totalMontoFactory) {
            totalMontoFactory.textContent = formatCLP(0);
        }

        if (totalAnticipadoFactory) {
            totalAnticipadoFactory.textContent = formatCLP(0);
        }

        if (totalDiferenciaPrecioFactory) {
            totalDiferenciaPrecioFactory.textContent = formatCLP(0);
        }

        if (totalMontoLiquidoResumenFactory) {
            totalMontoLiquidoResumenFactory.textContent = formatCLP(0);
        }

        if (totalPrecioCompraFactory) {
            totalPrecioCompraFactory.textContent = formatCLP(0);
        }

        if (totalComisionFactory) {
            totalComisionFactory.textContent = formatCLP(0);
        }

        if (totalMontoARecibirFactory) {
            totalMontoARecibirFactory.textContent = formatCLP(0);
            totalMontoARecibirFactory.classList.remove('text-danger', 'text-muted');
            totalMontoARecibirFactory.classList.add('text-success');
        }
    }

    function obtenerCalculoFila(row) {
        if (!row) {
            return null;
        }

        const documentoId = row.dataset.documentoId;
        const monto = Number(row.dataset.monto || 0);

        const inputSaldoLiquido = row.querySelector(
            '[name="documentos[' + documentoId + '][saldo_liquido]"]'
        );

        const inputMontoNoAnticipado = row.querySelector(
            '[name="documentos[' + documentoId + '][monto_no_anticipado]"]'
        );

        const saldoLiquido = obtenerNumeroInput(inputSaldoLiquido);
        const montoNoAnticipado = obtenerNumeroInput(inputMontoNoAnticipado);

        if (saldoLiquido === null || montoNoAnticipado === null) {
            return {
                documentoId: documentoId,
                monto: monto,
                saldoLiquido: saldoLiquido,
                montoNoAnticipado: montoNoAnticipado,
                diferenciaPrecio: null,
                inputSaldoLiquido: inputSaldoLiquido,
                inputMontoNoAnticipado: inputMontoNoAnticipado,
                completo: false,
                esInvalido: false,
            };
        }

        const diferenciaPrecio = monto - saldoLiquido - montoNoAnticipado;

        return {
            documentoId: documentoId,
            monto: monto,
            saldoLiquido: saldoLiquido,
            montoNoAnticipado: montoNoAnticipado,
            diferenciaPrecio: diferenciaPrecio,
            inputSaldoLiquido: inputSaldoLiquido,
            inputMontoNoAnticipado: inputMontoNoAnticipado,
            completo: true,
            esInvalido: diferenciaPrecio < 0,
        };
    }

    function actualizarDiferenciaPrecioFila(row) {
        const calculo = obtenerCalculoFila(row);

        if (!calculo) {
            return null;
        }

        const resultado = row.querySelector(
            '.js-factory-masivo-diferencia-precio[data-documento-id="' +
            calculo.documentoId +
            '"]'
        );

        if (calculo.inputSaldoLiquido) {
            calculo.inputSaldoLiquido.setCustomValidity('');
        }

        if (calculo.inputMontoNoAnticipado) {
            calculo.inputMontoNoAnticipado.setCustomValidity('');
        }

        if (!calculo.completo) {
            if (resultado) {
                resultado.textContent = '—';
                resultado.classList.remove('text-success', 'text-danger');
                resultado.classList.add('text-muted');
            }

            return calculo;
        }

        if (resultado) {
            resultado.textContent = formatCLP(calculo.diferenciaPrecio);
            resultado.classList.remove('text-muted');

            if (calculo.esInvalido) {
                resultado.classList.remove('text-success');
                resultado.classList.add('text-danger');
            } else {
                resultado.classList.remove('text-danger');
                resultado.classList.add('text-success');
            }
        }

        if (calculo.esInvalido) {
            const mensaje =
                'La suma del Monto Líquido y el Monto No Anticipado no puede ser mayor al Monto del documento.';

            if (calculo.inputSaldoLiquido) {
                calculo.inputSaldoLiquido.setCustomValidity(mensaje);
            }

            if (calculo.inputMontoNoAnticipado) {
                calculo.inputMontoNoAnticipado.setCustomValidity(mensaje);
            }
        }

        return calculo;
    }

    function actualizarTotalesFactoryMasivo() {
        const seleccion = getSeleccionFactory();
        const documentos = Object.values(seleccion);

        const cantidadDocumentos = documentos.length;

        const montoDocto = documentos.reduce((total, documento) => {
            return total + Number(documento.saldo || 0);
        }, 0);

        let montoAnticipado = 0;
        let diferenciaPrecioTotal = 0;
        let filasCompletas = documentos.length > 0;
        let existeFilaInvalida = false;

        document
            .querySelectorAll('#factory-masivo-documentos-seleccionados tr[data-documento-id]')
            .forEach(row => {
                const calculo = obtenerCalculoFila(row);

                if (!calculo || !calculo.completo) {
                    filasCompletas = false;
                    return;
                }

                montoAnticipado += calculo.saldoLiquido;
                diferenciaPrecioTotal += calculo.diferenciaPrecio;

                if (calculo.esInvalido) {
                    existeFilaInvalida = true;
                }
            });

        /*
        |--------------------------------------------------------------------------
        | Resumen consolidado de la operación
        |--------------------------------------------------------------------------
        | Monto Anticipado = suma de saldo_liquido por documento.
        | Monto Líquido    = Monto Anticipado + Diferencia de Precio.
        | Precio de Compra = Monto Líquido.
        |--------------------------------------------------------------------------
        */
        const montoLiquido = montoAnticipado + diferenciaPrecioTotal;
        const precioCompra = montoLiquido;

        const comisionTotal = obtenerNumeroInput(inputGlobalComisionTotal);

        /*
        |--------------------------------------------------------------------------
        | Fórmula aprobada de Monto a Recibir
        |--------------------------------------------------------------------------
        | Monto a Recibir =
        |     Monto Líquido - Comisión Total - Diferencia de Precio
        |--------------------------------------------------------------------------
        */
        const puedeCalcularMontoARecibir =
            filasCompletas &&
            !existeFilaInvalida &&
            comisionTotal !== null;

        const montoARecibir = puedeCalcularMontoARecibir
            ? montoLiquido - comisionTotal - diferenciaPrecioTotal
            : null;

        if (inputGlobalComisionTotal) {
            inputGlobalComisionTotal.setCustomValidity('');

            if (montoARecibir !== null && montoARecibir < 0) {
                inputGlobalComisionTotal.setCustomValidity(
                    'La Comisión Total genera un Monto a Recibir negativo para la operación.'
                );
            }
        }

        if (totalDocumentosFactory) {
            totalDocumentosFactory.textContent = String(cantidadDocumentos);
        }

        if (totalMontoFactory) {
            totalMontoFactory.textContent = formatCLP(montoDocto);
        }

        if (totalAnticipadoFactory) {
            totalAnticipadoFactory.textContent = formatCLP(montoAnticipado);
        }

        if (totalDiferenciaPrecioFactory) {
            totalDiferenciaPrecioFactory.textContent = formatCLP(diferenciaPrecioTotal);
        }

        if (totalMontoLiquidoResumenFactory) {
            totalMontoLiquidoResumenFactory.textContent = formatCLP(montoLiquido);
        }

        if (totalPrecioCompraFactory) {
            totalPrecioCompraFactory.textContent = formatCLP(precioCompra);
        }

        if (totalComisionFactory) {
            totalComisionFactory.textContent = comisionTotal !== null
                ? formatCLP(comisionTotal)
                : '—';
        }

        if (totalMontoARecibirFactory) {
            if (montoARecibir === null) {
                totalMontoARecibirFactory.textContent = '—';
                totalMontoARecibirFactory.classList.remove('text-success', 'text-danger');
                totalMontoARecibirFactory.classList.add('text-muted');
            } else {
                totalMontoARecibirFactory.textContent = formatCLP(montoARecibir);
                totalMontoARecibirFactory.classList.remove('text-muted');

                if (montoARecibir < 0) {
                    totalMontoARecibirFactory.classList.remove('text-success');
                    totalMontoARecibirFactory.classList.add('text-danger');
                } else {
                    totalMontoARecibirFactory.classList.remove('text-danger');
                    totalMontoARecibirFactory.classList.add('text-success');
                }
            }
        }

        return {
            cantidadDocumentos: cantidadDocumentos,
            montoDocto: montoDocto,
            montoAnticipado: montoAnticipado,
            diferenciaPrecioTotal: diferenciaPrecioTotal,
            montoLiquido: montoLiquido,
            precioCompra: precioCompra,
            comisionTotal: comisionTotal,
            montoARecibir: montoARecibir,
            filasCompletas: filasCompletas,
            existeFilaInvalida: existeFilaInvalida,
        };
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

            resetTotalesFactoryMasivo();

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

        documentos.forEach(documento => {
            const monto = Number(documento.saldo || 0);

            let rowHtml = templateFactory.innerHTML;

            rowHtml = rowHtml
                .replaceAll('__ID__', escapeHtml(documento.id))
                .replaceAll('__EMPRESA__', escapeHtml(documento.empresa))
                .replaceAll('__FOLIO__', escapeHtml(documento.folio))
                .replaceAll('__RAZON__', escapeHtml(documento.razon))
                .replaceAll('__SALDO__', String(monto))
                .replaceAll('__SALDO_FORMAT__', formatCLP(monto));

            tbodyFactory.insertAdjacentHTML('beforeend', rowHtml);

            const row = tbodyFactory.lastElementChild;

            if (!row) {
                return;
            }

            const inputSaldoLiquido = row.querySelector(
                '[name="documentos[' + documento.id + '][saldo_liquido]"]'
            );

            const inputMontoNoAnticipado = row.querySelector(
                '[name="documentos[' + documento.id + '][monto_no_anticipado]"]'
            );

            if (inputSaldoLiquido && documento.saldoLiquido !== '') {
                inputSaldoLiquido.value = documento.saldoLiquido;
            }

            if (inputMontoNoAnticipado && documento.montoNoAnticipado !== '') {
                inputMontoNoAnticipado.value = documento.montoNoAnticipado;
            }

            actualizarDiferenciaPrecioFila(row);
        });

        actualizarTotalesFactoryMasivo();
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

    function validarDatosGeneralesFactoryMasivo() {
        if (
            !inputGlobalCesion ||
            !selectGlobalBanco ||
            !inputGlobalFechaFactory ||
            !inputGlobalComisionTotal
        ) {
            return false;
        }

        toggleBancoOtroFactoryMasivoGlobal();

        if (!inputGlobalCesion.value.trim()) {
            inputGlobalCesion.reportValidity();
            return false;
        }

        if (!selectGlobalBanco.value) {
            selectGlobalBanco.reportValidity();
            return false;
        }

        if (
            selectGlobalBanco.value === '__otro__' &&
            inputGlobalBancoOtro &&
            !inputGlobalBancoOtro.value.trim()
        ) {
            inputGlobalBancoOtro.reportValidity();
            return false;
        }

        if (!inputGlobalFechaFactory.value) {
            inputGlobalFechaFactory.reportValidity();
            return false;
        }

        if (inputGlobalComisionTotal.value === '') {
            inputGlobalComisionTotal.reportValidity();
            return false;
        }

        const comisionTotal = Number(inputGlobalComisionTotal.value);

        if (!Number.isFinite(comisionTotal) || comisionTotal < 0) {
            inputGlobalComisionTotal.reportValidity();
            return false;
        }

        const resumen = actualizarTotalesFactoryMasivo();

        if (resumen.montoARecibir !== null && resumen.montoARecibir < 0) {
            inputGlobalComisionTotal.reportValidity();
            return false;
        }

        return true;
    }

    function validarFilasFactoryMasivo() {
        const foliosInvalidos = [];
        let primerInputInvalido = null;

        document
            .querySelectorAll('#factory-masivo-documentos-seleccionados tr[data-documento-id]')
            .forEach(row => {
                const calculo = actualizarDiferenciaPrecioFila(row);

                if (!calculo || !calculo.completo) {
                    return;
                }

                if (calculo.esInvalido) {
                    const seleccion = getSeleccionFactory();
                    const documento = seleccion[calculo.documentoId];

                    foliosInvalidos.push(documento?.folio || calculo.documentoId);

                    if (!primerInputInvalido) {
                        primerInputInvalido = calculo.inputMontoNoAnticipado;
                    }
                }
            });

        actualizarTotalesFactoryMasivo();

        if (foliosInvalidos.length > 0) {
            alert(
                'La Diferencia de Precio no puede ser negativa. Revisa los folios: ' +
                foliosInvalidos.join(', ')
            );

            if (
                primerInputInvalido &&
                typeof primerInputInvalido.reportValidity === 'function'
            ) {
                primerInputInvalido.reportValidity();
            }

            return false;
        }

        return true;
    }

    // =====================================================
    // RESTAURAR CHECKBOXES AL CARGAR O PAGINAR
    // =====================================================
    const seleccionInicial = getSeleccionFactory();

    document.querySelectorAll('.check-documento-factory').forEach(checkbox => {
        const id = checkbox.dataset.id;

        if (seleccionInicial[id]) {
            checkbox.checked = true;

            /*
            |--------------------------------------------------------------------------
            | Refrescar datos visibles sin perder montos digitados
            |--------------------------------------------------------------------------
            */
            addDocumentoFactory(checkbox);
        }

        checkbox.addEventListener('change', function () {
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
        document.querySelectorAll('.check-documento-factory').forEach(checkbox => {
            checkbox.checked = this.checked;

            if (this.checked) {
                addDocumentoFactory(checkbox);
            } else {
                removeDocumentoFactory(checkbox.dataset.id);
            }
        });

        actualizarCheckAllFactory();
    });

    // Renderizar modal masivo al abrir
    btnFactoryMasivo?.addEventListener('click', function () {
        renderFactoryMasivoModal();
    });

    modalFactory?.addEventListener('show.bs.modal', function () {
        renderFactoryMasivoModal();
    });

    // Quitar documento desde el modal masivo
    document.addEventListener('click', function (event) {
        const btnQuitar = event.target.closest('.js-factory-masivo-quitar');

        if (!btnQuitar) {
            return;
        }

        removeDocumentoFactory(btnQuitar.dataset.documentoId);
        renderFactoryMasivoModal();
    });

    // Recalcular diferencias y totales al editar montos masivos individuales
    document.addEventListener('input', function (event) {
        const inputMonto = event.target.closest(
            '.js-factory-masivo-saldo-liquido, .js-factory-masivo-monto-no-anticipado'
        );

        if (inputMonto) {
            const row = inputMonto.closest('tr[data-documento-id]');

            if (!row) {
                return;
            }

            persistirMontosFila(row);
            actualizarDiferenciaPrecioFila(row);
            actualizarTotalesFactoryMasivo();

            return;
        }

        // Recalcular Monto a Recibir masivo al modificar Comisión Total global
        if (event.target === inputGlobalComisionTotal) {
            actualizarTotalesFactoryMasivo();
        }
    });

    // Mostrar input "Otra entidad" en los campos generales masivos
    selectGlobalBanco?.addEventListener('change', function () {
        toggleBancoOtroFactoryMasivoGlobal();
    });

    toggleBancoOtroFactoryMasivoGlobal();

    /*
    |--------------------------------------------------------------------------
    | Enviar Factoring masivo sin sobrescribir ajustes individuales
    |--------------------------------------------------------------------------
    | Cesión, banco, fecha y comisión se envían como datos generales.
    | Cada fila envía exactamente su Monto Líquido y Monto No Anticipado.
    |--------------------------------------------------------------------------
    */
    formFactoryMasivo?.addEventListener('submit', function (event) {
        const seleccion = getSeleccionFactory();

        if (Object.keys(seleccion).length === 0) {
            event.preventDefault();
            alert('Debe seleccionar al menos un documento para registrar Factoring masivo.');
            return;
        }

        toggleBancoOtroFactoryMasivoGlobal();

        document
            .querySelectorAll('#factory-masivo-documentos-seleccionados tr[data-documento-id]')
            .forEach(row => {
                persistirMontosFila(row);
                actualizarDiferenciaPrecioFila(row);
            });

        actualizarTotalesFactoryMasivo();

        if (!validarFilasFactoryMasivo()) {
            event.preventDefault();
            return;
        }

        if (!validarDatosGeneralesFactoryMasivo()) {
            event.preventDefault();
            return;
        }

        if (typeof this.reportValidity === 'function' && !this.reportValidity()) {
            event.preventDefault();
            return;
        }

        localStorage.removeItem(STORAGE_KEY_FACTORY);
    });
});