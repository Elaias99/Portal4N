/**
 * resources/js/suscripciones/generacion-mensual.js
 *
 * Maneja la interacción frontend del formulario:
 * resources/views/suscripciones/comisiones_mensuales/create.blade.php
 */

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('form-generacion-mensual');

    if (!form) {
        return;
    }

    const config = window.suscripcionesGeneracionMensual || {};

    const comisionesIniciales = Array.isArray(config.comisionesIniciales)
        ? config.comisionesIniciales
        : [];

    const ajustesIniciales = Array.isArray(config.ajustesIniciales)
        ? config.ajustesIniciales
        : [];

    const conceptosPagoVariable = Array.isArray(config.conceptosPagoVariable)
        ? config.conceptosPagoVariable
        : [];

    let comisiones = [];
    let ajustesMensuales = [];

    const dom = {
        cantidadVariable: {
            asignacionSelect: document.getElementById('cantidad_mensual_asignacion_id'),
            cantidadInput: document.getElementById('cantidad_mensual_cantidad'),
            totalInput: document.getElementById('total_variable_estimado'),
            advertencia: document.getElementById('cantidad_variable_advertencia'),
        },

        comision: {
            proveedorSelect: document.getElementById('comision_proveedor_id'),
            transportistaSelect: document.getElementById('comision_transportista_id'),
            punto1Input: document.getElementById('comision_punto_1'),
            origenGastoInput: document.getElementById('comision_origen_gasto'),
            punto2Input: document.getElementById('comision_punto_2'),
            servicioInput: document.getElementById('comision_servicio'),
            costoInput: document.getElementById('comision_costo'),
            totalInput: document.getElementById('comision_total_estimado'),
            observacionInput: document.getElementById('comision_observacion'),
            agregarBtn: document.getElementById('btn-agregar-comision'),
            hiddenContainer: document.getElementById('comisiones-hidden-container'),
            resumenBody: document.getElementById('comisiones-resumen-body'),
            cantidadTexto: document.getElementById('comisiones-cantidad'),
            totalTexto: document.getElementById('comisiones-total'),
        },

        ajuste: {
            tipoSelect: document.getElementById('ajuste_tipo_ajuste'),
            tipoDescripcion: document.getElementById('ajuste_tipo_descripcion'),
            guiaOperativa: document.getElementById('ajuste_guia_operativa'),

            advertenciaWrapper: document.getElementById('ajuste_advertencia_asignacion_wrapper'),
            advertenciaAsignacion: document.getElementById('ajuste_advertencia_asignacion'),
            asignacionAyuda: document.getElementById('ajuste_asignacion_ayuda'),

            bloqueAsignacion: document.getElementById('bloque-ajuste-asignacion'),
            bloqueProveedor: document.getElementById('bloque-ajuste-proveedor'),
            bloqueTransportista: document.getElementById('bloque-ajuste-transportista'),
            bloqueConceptoPagoVariable: document.getElementById('bloque-ajuste-concepto-pago-variable'),
            bloqueConceptoPagoVariableManual: document.getElementById('bloque-ajuste-concepto-pago-variable-manual'),
            bloqueProveedorFacturacion: document.getElementById('bloque-ajuste-proveedor-facturacion'),
            bloqueTransportistaOverride: document.getElementById('bloque-ajuste-transportista-override'),

            asignacionSelect: document.getElementById('ajuste_suscripcion_asignacion_id'),
            proveedorSelect: document.getElementById('ajuste_suscripcion_proveedor_id'),
            transportistaSelect: document.getElementById('ajuste_suscripcion_transportista_id'),
            conceptoPagoVariableSelect: document.getElementById('ajuste_concepto_pago_variable_id'),
            conceptoPagoVariableManualInput: document.getElementById('ajuste_concepto_pago_variable_manual'),
            proveedorFacturacionSelect: document.getElementById('ajuste_suscripcion_proveedor_facturacion_id'),
            transportistaOverrideSelect: document.getElementById('ajuste_suscripcion_transportista_override_id'),

            punto1Input: document.getElementById('ajuste_punto_1'),
            origenGastoInput: document.getElementById('ajuste_origen_gasto'),
            punto2Input: document.getElementById('ajuste_punto_2'),
            codigoInput: document.getElementById('ajuste_codigo'),
            servicioInput: document.getElementById('ajuste_servicio'),
            grupoPrefacturaInput: document.getElementById('ajuste_grupo_prefactura'),
            costoInput: document.getElementById('ajuste_costo'),
            qCalendarioInput: document.getElementById('ajuste_q_calendario'),
            qInasistenciaInput: document.getElementById('ajuste_q_inasistencia'),
            cantidadInput: document.getElementById('ajuste_cantidad'),
            totalManualInput: document.getElementById('ajuste_total'),

            tipoDocumentoInput: document.getElementById('ajuste_tipo_documento'),
            detalleDocumentoInput: document.getElementById('ajuste_detalle_documento'),
            detalleImpuestoInput: document.getElementById('ajuste_detalle_impuesto'),
            finalInput: document.getElementById('ajuste_final'),

            totalEstimadoInput: document.getElementById('ajuste_total_estimado'),
            observacionInput: document.getElementById('ajuste_observacion'),
            agregarBtn: document.getElementById('btn-agregar-ajuste'),

            hiddenContainer: document.getElementById('ajustes-hidden-container'),
            resumenBody: document.getElementById('ajustes-resumen-body'),
            cantidadTexto: document.getElementById('ajustes-cantidad'),
            totalTexto: document.getElementById('ajustes-total'),
        },
    };

    function formatearCLP(valor) {
        return '$' + new Intl.NumberFormat('es-CL').format(valor || 0);
    }

    function limpiarTexto(valor) {
        return String(valor || '').replace(/\s+/g, ' ').trim();
    }

    function escaparHtml(valor) {
        return String(valor || '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function selectedOption(select) {
        return select?.options[select.selectedIndex] || null;
    }

    function optionLabel(select) {
        const option = selectedOption(select);

        if (!option) {
            return '—';
        }

        return limpiarTexto(option.dataset.label || option.text || '—');
    }

    function labelPorValor(select, valor) {
        const option = Array.from(select?.options || []).find(function (item) {
            return String(item.value) === String(valor);
        });

        if (!option) {
            return '—';
        }

        return limpiarTexto(option.dataset.label || option.text || '—');
    }

    function normalizarTipo(valor) {
        return limpiarTexto(valor).toUpperCase().replaceAll(' ', '_').replaceAll('-', '_');
    }

    function normalizarCodigo(valor) {
        return limpiarTexto(valor).toUpperCase();
    }

    function slugCodigo(valor) {
        const texto = limpiarTexto(valor)
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toUpperCase()
            .replace(/[^A-Z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');

        return texto || 'PAGO_VARIABLE';
    }

    function agregarHidden(nombre, valor, container) {
        if (!container) {
            return;
        }

        const input = document.createElement('input');

        input.type = 'hidden';
        input.name = nombre;
        input.value = valor ?? '';

        container.appendChild(input);
    }

    function inicializarAcordeonesSuscripciones() {
        document.querySelectorAll('[data-suscripcion-toggle]').forEach(function (button) {
            if (button.dataset.dropdownInicializado === '1') {
                return;
            }

            button.dataset.dropdownInicializado = '1';

            button.addEventListener('click', function () {
                const targetSelector = button.getAttribute('data-suscripcion-toggle');
                const target = document.querySelector(targetSelector);

                if (!target) {
                    return;
                }

                const estaCerrado = target.classList.contains('d-none');

                target.classList.toggle('d-none', !estaCerrado);
                button.setAttribute('aria-expanded', estaCerrado ? 'true' : 'false');

                const icon = button.querySelector('[data-suscripcion-toggle-icon]');

                if (icon) {
                    icon.textContent = estaCerrado ? '⌃' : '⌄';
                }
            });
        });
    }

    function actualizarTotalVariable() {
        const { asignacionSelect, cantidadInput, totalInput, advertencia } = dom.cantidadVariable;

        if (!asignacionSelect || !cantidadInput || !totalInput) {
            return;
        }

        const option = selectedOption(asignacionSelect);
        const costo = parseInt(option?.dataset?.costo || 0, 10);
        const cantidad = parseInt(cantidadInput.value || 0, 10);
        const total = costo * cantidad;

        totalInput.value = formatearCLP(total);

        if (!advertencia) {
            return;
        }

        const esVariableOperativa = option?.dataset?.tipoAsignacion === 'VARIABLE';

        advertencia.classList.remove('text-danger', 'text-warning', 'text-muted');

        if (option?.value && !esVariableOperativa) {
            advertencia.classList.add('text-warning');
            advertencia.textContent = 'Revisa esta selección: sólo deben cargarse aquí asignaciones configuradas como cantidad variable.';
        } else {
            advertencia.classList.add('text-muted');
            advertencia.textContent = 'Selecciona una asignación variable y escribe la cantidad mensual informada.';
        }
    }

    function actualizarTotalComisionActual() {
        const { costoInput, totalInput } = dom.comision;

        if (!costoInput || !totalInput) {
            return;
        }

        const costo = parseInt(costoInput.value || 0, 10);

        totalInput.value = formatearCLP(costo);
    }

    function limpiarFormularioComision() {
        const c = dom.comision;

        if (c.proveedorSelect) c.proveedorSelect.value = '';
        if (c.transportistaSelect) c.transportistaSelect.value = '';
        if (c.punto1Input) c.punto1Input.value = '';
        if (c.origenGastoInput) c.origenGastoInput.value = 'Suscripciones';
        if (c.punto2Input) c.punto2Input.value = '';
        if (c.servicioInput) c.servicioInput.value = 'Reparto fin de semana';
        if (c.costoInput) c.costoInput.value = '';
        if (c.observacionInput) c.observacionInput.value = '';

        actualizarTotalComisionActual();
    }

    function renderizarComisiones() {
        const c = dom.comision;

        if (!c.hiddenContainer || !c.resumenBody) {
            return;
        }

        c.hiddenContainer.innerHTML = '';
        c.resumenBody.innerHTML = '';

        if (comisiones.length === 0) {
            c.resumenBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-muted text-center">
                        No hay comisiones agregadas para este periodo.
                    </td>
                </tr>
            `;

            if (c.cantidadTexto) c.cantidadTexto.textContent = '0';
            if (c.totalTexto) c.totalTexto.textContent = formatearCLP(0);

            return;
        }

        let total = 0;

        comisiones.forEach(function (comision, index) {
            total += parseInt(comision.costo || 0, 10);

            agregarHidden(`comisiones[${index}][suscripcion_proveedor_id]`, comision.suscripcion_proveedor_id, c.hiddenContainer);
            agregarHidden(`comisiones[${index}][suscripcion_transportista_id]`, comision.suscripcion_transportista_id, c.hiddenContainer);
            agregarHidden(`comisiones[${index}][punto_1]`, comision.punto_1, c.hiddenContainer);
            agregarHidden(`comisiones[${index}][origen_gasto]`, comision.origen_gasto, c.hiddenContainer);
            agregarHidden(`comisiones[${index}][punto_2]`, comision.punto_2, c.hiddenContainer);
            agregarHidden(`comisiones[${index}][servicio]`, comision.servicio, c.hiddenContainer);
            agregarHidden(`comisiones[${index}][costo]`, comision.costo, c.hiddenContainer);
            agregarHidden(`comisiones[${index}][observacion]`, comision.observacion, c.hiddenContainer);

            const row = document.createElement('tr');

            row.innerHTML = `
                <td>${escaparHtml(comision.proveedor_label)}</td>
                <td>${escaparHtml(comision.transportista_label)}</td>
                <td>${escaparHtml(comision.punto_1 || '—')}</td>
                <td>${escaparHtml(comision.servicio || '—')}</td>
                <td class="text-end">${formatearCLP(comision.costo)}</td>
                <td>${escaparHtml(comision.observacion || '—')}</td>
                <td class="text-center">
                    <button
                        type="button"
                        class="btn btn-outline-danger btn-sm"
                        data-index="${index}"
                        data-action="eliminar-comision"
                    >
                        Eliminar
                    </button>
                </td>
            `;

            c.resumenBody.appendChild(row);
        });

        if (c.cantidadTexto) c.cantidadTexto.textContent = String(comisiones.length);
        if (c.totalTexto) c.totalTexto.textContent = formatearCLP(total);
    }

    function agregarComisionDesdeFormulario() {
        const c = dom.comision;

        const proveedorId = c.proveedorSelect?.value || '';
        const transportistaId = c.transportistaSelect?.value || '';
        const costo = parseInt(c.costoInput?.value || '', 10);

        if (!proveedorId) {
            alert('Selecciona un proveedor para la comisión.');
            return;
        }

        if (!transportistaId) {
            alert('Selecciona un transportista para la comisión.');
            return;
        }

        if (Number.isNaN(costo) || costo < 0) {
            alert('Ingresa un costo válido para la comisión.');
            return;
        }

        const existeMismaComision = comisiones.some(function (comision) {
            return String(comision.suscripcion_proveedor_id) === String(proveedorId)
                && String(comision.suscripcion_transportista_id) === String(transportistaId);
        });

        if (existeMismaComision) {
            alert('Ya agregaste una comisión para este proveedor y transportista.');
            return;
        }

        comisiones.push({
            suscripcion_proveedor_id: proveedorId,
            suscripcion_transportista_id: transportistaId,
            proveedor_label: optionLabel(c.proveedorSelect),
            transportista_label: optionLabel(c.transportistaSelect),
            punto_1: limpiarTexto(c.punto1Input?.value),
            origen_gasto: limpiarTexto(c.origenGastoInput?.value) || 'Suscripciones',
            punto_2: limpiarTexto(c.punto2Input?.value),
            servicio: limpiarTexto(c.servicioInput?.value) || 'Comisión mensual',
            costo: costo,
            observacion: limpiarTexto(c.observacionInput?.value),
        });

        limpiarFormularioComision();
        renderizarComisiones();
    }

    function esTipoLineaAdicional(tipo) {
        return ['LINEA_ADICIONAL', 'PAGO_VARIABLE', 'PAGO_ADICIONAL', 'REEMPLAZO'].includes(normalizarTipo(tipo));
    }

    function esTipoAsignacionExistente(tipo) {
        return ['INASISTENCIA', 'FIJO_MENSUAL', 'FACTURACION'].includes(normalizarTipo(tipo));
    }


    function esPagoVariable(tipo) {
        return normalizarTipo(tipo) === 'PAGO_VARIABLE';
    }

    function tarifaPagoVariableActual() {
        const valor = parseInt(dom.ajuste.costoInput?.value || '', 10);

        return Number.isNaN(valor) ? 0 : valor;
    }

    function prepararPagoVariableTecnico() {
        const a = dom.ajuste;
        const tipo = normalizarTipo(a.tipoSelect?.value || '');

        if (tipo !== 'PAGO_VARIABLE') {
            return;
        }

        const concepto = conceptoPagoVariableSeleccionado();
        const nombreConcepto = concepto.nombre || concepto.manual || '';
        const codigoConcepto = concepto.codigo || (nombreConcepto ? slugCodigo(nombreConcepto) : 'PAGO_VARIABLE');
        const tarifa = tarifaPagoVariableActual();

        if (a.punto1Input) a.punto1Input.value = '';
        if (a.origenGastoInput) a.origenGastoInput.value = 'Suscripciones';
        if (a.punto2Input) a.punto2Input.value = '';
        if (a.grupoPrefacturaInput) a.grupoPrefacturaInput.value = '';

        if (a.codigoInput) {
            a.codigoInput.value = nombreConcepto ? 'PV-' + codigoConcepto : '';
        }

        if (a.servicioInput) {
            a.servicioInput.value = nombreConcepto
                ? 'Pago variable - ' + nombreConcepto
                : 'Pago variable';
        }

        if (a.qCalendarioInput) a.qCalendarioInput.value = '1';
        if (a.qInasistenciaInput) a.qInasistenciaInput.value = '0';
        if (a.cantidadInput) a.cantidadInput.value = '1';
        if (a.totalManualInput) a.totalManualInput.value = tarifa > 0 ? String(tarifa) : '';

        if (a.tipoDocumentoInput) a.tipoDocumentoInput.value = '';
        if (a.detalleDocumentoInput) a.detalleDocumentoInput.value = '';
        if (a.detalleImpuestoInput) a.detalleImpuestoInput.value = '';
        if (a.finalInput) a.finalInput.value = '';
    }




    function contenedorCampo(input) {
        return input?.closest('.col-md-2, .col-md-3, .col-md-4, .col-md-6, .col-md-8, .col-md-9, .col-md-12');
    }

    const camposAjuste = {
        punto1: contenedorCampo(dom.ajuste.punto1Input),
        origenGasto: contenedorCampo(dom.ajuste.origenGastoInput),
        punto2: contenedorCampo(dom.ajuste.punto2Input),
        codigo: contenedorCampo(dom.ajuste.codigoInput),
        servicio: contenedorCampo(dom.ajuste.servicioInput),
        grupoPrefactura: contenedorCampo(dom.ajuste.grupoPrefacturaInput),
        costo: contenedorCampo(dom.ajuste.costoInput),
        qCalendario: contenedorCampo(dom.ajuste.qCalendarioInput),
        qInasistencia: contenedorCampo(dom.ajuste.qInasistenciaInput),
        cantidad: contenedorCampo(dom.ajuste.cantidadInput),
        total: contenedorCampo(dom.ajuste.totalManualInput),
        tipoDocumento: contenedorCampo(dom.ajuste.tipoDocumentoInput),
        detalleDocumento: contenedorCampo(dom.ajuste.detalleDocumentoInput),
        detalleImpuesto: contenedorCampo(dom.ajuste.detalleImpuestoInput),
        final: contenedorCampo(dom.ajuste.finalInput),
        totalEstimado: contenedorCampo(dom.ajuste.totalEstimadoInput),
        observacion: contenedorCampo(dom.ajuste.observacionInput),
    };

    function ocultarCamposAjuste() {
        Object.values(camposAjuste).forEach(function (campo) {
            if (campo) {
                campo.classList.add('d-none');
            }
        });
    }

    function mostrarCamposAjuste(nombres) {
        nombres.forEach(function (nombre) {
            if (camposAjuste[nombre]) {
                camposAjuste[nombre].classList.remove('d-none');
            }
        });
    }

    function mostrarAvisoAsignacion(mensaje, tipo = 'warning') {
        const a = dom.ajuste;

        if (!a.advertenciaWrapper || !a.advertenciaAsignacion) {
            return;
        }

        a.advertenciaAsignacion.className = 'alert small mb-0 alert-' + tipo;
        a.advertenciaAsignacion.textContent = mensaje;
        a.advertenciaWrapper.classList.remove('d-none');
    }

    function ocultarAvisoAsignacion() {
        const a = dom.ajuste;

        if (!a.advertenciaWrapper || !a.advertenciaAsignacion) {
            return;
        }

        a.advertenciaAsignacion.textContent = '';
        a.advertenciaWrapper.classList.add('d-none');
    }

    function tipoAsignacionOption(option) {
        return limpiarTexto(option?.dataset?.tipoAsignacion || 'RUTA').toUpperCase();
    }

    function esOpcionRuta(option) {
        return tipoAsignacionOption(option) === 'RUTA';
    }

    function esOpcionFijoMensual(option) {
        return tipoAsignacionOption(option) === 'FIJO_MENSUAL';
    }

    function esOpcionComision(option) {
        return tipoAsignacionOption(option) === 'COMISION';
    }

    function esOpcionOpv(option) {
        return tipoAsignacionOption(option) === 'OPV';
    }

    function esOpcionCantidadVariable(option) {
        return tipoAsignacionOption(option) === 'VARIABLE';
    }

    function esOpcionContenedorAjuste(option) {
        return tipoAsignacionOption(option) === 'CONTENEDOR_AJUSTE';
    }

    function opcionAsignacionCompatible(tipo, option) {
        tipo = normalizarTipo(tipo);

        if (!option || !option.value) {
            return true;
        }

        const tipoAsignacion = tipoAsignacionOption(option);

        if (['COMISION', 'CONTENEDOR_AJUSTE'].includes(tipoAsignacion)) {
            return false;
        }

        if (tipo === 'INASISTENCIA') {
            return tipoAsignacion === 'RUTA';
        }

        if (tipo === 'FIJO_MENSUAL') {
            return ['RUTA', 'FIJO_MENSUAL'].includes(tipoAsignacion);
        }

        if (tipo === 'FACTURACION') {
            return ['RUTA', 'VARIABLE', 'FIJO_MENSUAL', 'OPV'].includes(tipoAsignacion);
        }

        return true;
    }

    function mensajeIncompatibilidadAsignacion(tipo, option) {
        tipo = normalizarTipo(tipo);

        if (!option || !option.value) {
            return '';
        }

        const codigo = normalizarCodigo(option.dataset.codigo || '');
        const tipoAsignacion = tipoAsignacionOption(option);

        if (esOpcionComision(option)) {
            return 'Esta asignación corresponde a comisión. No debe usarse como novedad mensual sobre una asignación existente.';
        }

        if (esOpcionContenedorAjuste(option)) {
            return 'Esta asignación corresponde a una línea contenedora de ajuste. No debe seleccionarse como asignación base.';
        }

        if (tipo === 'INASISTENCIA' && esOpcionCantidadVariable(option)) {
            return `${codigo} está configurada como cantidad variable. Cárgala arriba en “Cantidades variables del mes”; no corresponde registrar inasistencia aquí.`;
        }

        if (tipo === 'INASISTENCIA' && esOpcionOpv(option)) {
            return 'Esta asignación corresponde a OPV. No debe usarse como inasistencia normal de calendario.';
        }

        if (tipo === 'INASISTENCIA' && !esOpcionRuta(option)) {
            return 'La inasistencia sólo puede aplicarse a rutas normales.';
        }

        if (tipo === 'FIJO_MENSUAL' && esOpcionCantidadVariable(option)) {
            return `${codigo} está configurada como cantidad variable. No debe transformarse en fijo mensual, porque perdería la cantidad real del mes.`;
        }

        if (tipo === 'FIJO_MENSUAL' && esOpcionOpv(option)) {
            return 'Esta asignación corresponde a OPV. Revísala aparte antes de usarla como fijo mensual excepcional.';
        }

        return `La asignación seleccionada no es compatible con este tipo de novedad. Tipo asignación: ${tipoAsignacion || 'SIN TIPO'}.`;
    }

    function mensajeInformativoAsignacion(tipo, option) {
        tipo = normalizarTipo(tipo);

        if (!option || !option.value) {
            return '';
        }

        const codigo = normalizarCodigo(option.dataset.codigo || '');

        if (tipo === 'FACTURACION' && esOpcionCantidadVariable(option)) {
            return `${codigo} se debe cargar arriba como cantidad variable. Aquí sólo registrarás el proveedor facturador/documento efectivo del periodo.`;
        }

        if (tipo === 'FACTURACION' && esOpcionOpv(option)) {
            return 'Cambio de facturación sobre OPV: confirma que la ruta OPV tenga puntos/locales cargados para el periodo.';
        }

        return '';
    }

    function actualizarOpcionesAsignacionPorTipo() {
        const a = dom.ajuste;
        const tipo = normalizarTipo(a.tipoSelect?.value || '');

        Array.from(a.asignacionSelect?.options || []).forEach(function (option) {
            option.disabled = !opcionAsignacionCompatible(tipo, option);
        });

        const option = selectedOption(a.asignacionSelect);

        if (option && option.value && option.disabled) {
            mostrarAvisoAsignacion(mensajeIncompatibilidadAsignacion(tipo, option), 'warning');
            a.asignacionSelect.value = '';
            limpiarCamposAsignacion();
            return;
        }

        actualizarAvisoAsignacionActual();
    }

    function actualizarAvisoAsignacionActual() {
        const a = dom.ajuste;
        const tipo = normalizarTipo(a.tipoSelect?.value || '');
        const option = selectedOption(a.asignacionSelect);

        ocultarAvisoAsignacion();

        if (!option || !option.value || !tipo) {
            return;
        }

        if (!opcionAsignacionCompatible(tipo, option)) {
            mostrarAvisoAsignacion(mensajeIncompatibilidadAsignacion(tipo, option), 'warning');
            return;
        }

        const mensajeInfo = mensajeInformativoAsignacion(tipo, option);

        if (mensajeInfo !== '') {
            mostrarAvisoAsignacion(mensajeInfo, 'info');
        }
    }

    function validarCompatibilidadAsignacionActual() {
        const a = dom.ajuste;
        const tipo = normalizarTipo(a.tipoSelect?.value || '');
        const option = selectedOption(a.asignacionSelect);

        if (!option || !option.value) {
            return true;
        }

        if (!opcionAsignacionCompatible(tipo, option)) {
            alert(mensajeIncompatibilidadAsignacion(tipo, option));
            actualizarAvisoAsignacionActual();
            return false;
        }

        return true;
    }

    function limpiarCamposAsignacion() {
        const a = dom.ajuste;

        if (a.punto1Input) a.punto1Input.value = '';
        if (a.origenGastoInput) a.origenGastoInput.value = 'Suscripciones';
        if (a.punto2Input) a.punto2Input.value = '';
        if (a.codigoInput) a.codigoInput.value = '';
        if (a.servicioInput) a.servicioInput.value = 'Reparto fin de semana';
        if (a.grupoPrefacturaInput) a.grupoPrefacturaInput.value = '';
        if (a.costoInput) a.costoInput.value = '';
        if (a.qCalendarioInput) a.qCalendarioInput.value = '';
        if (a.qInasistenciaInput) a.qInasistenciaInput.value = '';
        if (a.cantidadInput) a.cantidadInput.value = '';
        if (a.totalManualInput) a.totalManualInput.value = '';

        actualizarTotalAjusteActual();
    }

    function limpiarCamposDependientesAjuste() {
        const a = dom.ajuste;

        if (a.asignacionSelect) a.asignacionSelect.value = '';
        if (a.proveedorSelect) a.proveedorSelect.value = '';
        if (a.transportistaSelect) a.transportistaSelect.value = '';
        if (a.conceptoPagoVariableSelect) a.conceptoPagoVariableSelect.value = '';
        if (a.conceptoPagoVariableManualInput) a.conceptoPagoVariableManualInput.value = '';
        if (a.proveedorFacturacionSelect) a.proveedorFacturacionSelect.value = '';
        if (a.transportistaOverrideSelect) a.transportistaOverrideSelect.value = '';

        limpiarCamposAsignacion();

        if (a.tipoDocumentoInput) a.tipoDocumentoInput.value = '';
        if (a.detalleDocumentoInput) a.detalleDocumentoInput.value = '';
        if (a.detalleImpuestoInput) a.detalleImpuestoInput.value = '';
        if (a.finalInput) a.finalInput.value = '';
        if (a.observacionInput) a.observacionInput.value = '';

        if (a.costoInput) a.costoInput.disabled = false;

        ocultarAvisoAsignacion();
    }

    function actualizarEstadoCostoSegunAsignacion() {
        const a = dom.ajuste;
        const tipo = normalizarTipo(a.tipoSelect?.value || '');
        const option = selectedOption(a.asignacionSelect);

        if (!a.costoInput) {
            return;
        }

        a.costoInput.disabled = false;

        if (tipo === 'FACTURACION' && option?.value && esOpcionCantidadVariable(option)) {
            a.costoInput.value = '';
            a.costoInput.disabled = true;
        }
    }

    function conceptoPagoVariableSeleccionado() {
        const a = dom.ajuste;
        const option = selectedOption(a.conceptoPagoVariableSelect);
        const valor = a.conceptoPagoVariableSelect?.value || '';

        if (!option || !valor) {
            return {
                id: '',
                manual: '',
                nombre: '',
                codigo: '',
                esOtro: false,
            };
        }

        if (valor === '__OTRO__') {
            const manual = limpiarTexto(a.conceptoPagoVariableManualInput?.value || '');

            return {
                id: '',
                manual: manual,
                nombre: manual,
                codigo: manual ? slugCodigo(manual) : 'OTRO',
                esOtro: true,
            };
        }

        return {
            id: valor,
            manual: '',
            nombre: limpiarTexto(option.dataset.nombre || option.text || ''),
            codigo: limpiarTexto(option.dataset.codigo || ''),
            esOtro: false,
        };
    }

    function actualizarConceptoPagoVariableManual() {
        const a = dom.ajuste;
        const concepto = conceptoPagoVariableSeleccionado();

        if (a.bloqueConceptoPagoVariableManual) {
            a.bloqueConceptoPagoVariableManual.classList.toggle('d-none', !concepto.esOtro);
        }
    }

    function aplicarConceptoPagoVariableSeleccionado() {
        const a = dom.ajuste;
        const tipo = normalizarTipo(a.tipoSelect?.value || '');

        if (tipo !== 'PAGO_VARIABLE') {
            return;
        }

        const concepto = conceptoPagoVariableSeleccionado();

        actualizarConceptoPagoVariableManual();

        if (!concepto.nombre) {
            if (a.codigoInput && (!a.codigoInput.value || normalizarCodigo(a.codigoInput.value).startsWith('PV-'))) {
                a.codigoInput.value = '';
            }

            if (a.servicioInput && (!a.servicioInput.value || normalizarCodigo(a.servicioInput.value).startsWith('PAGO VARIABLE'))) {
                a.servicioInput.value = 'Pago variable';
            }

            prepararPagoVariableTecnico();
            actualizarTotalAjusteActual();

            return;
        }

        const codigoConcepto = concepto.codigo || slugCodigo(concepto.nombre);

        if (a.codigoInput) {
            a.codigoInput.value = 'PV-' + codigoConcepto;
        }

        if (a.servicioInput) {
            a.servicioInput.value = 'Pago variable - ' + concepto.nombre;
        }

        if (a.observacionInput && !limpiarTexto(a.observacionInput.value)) {
            a.observacionInput.value = concepto.nombre;
        }

        prepararPagoVariableTecnico();
        actualizarTotalAjusteActual();
    }

    function actualizarCamposAjustePorTipo() {
        const a = dom.ajuste;
        const tipo = normalizarTipo(a.tipoSelect?.value || '');

        [
            a.bloqueAsignacion,
            a.bloqueProveedor,
            a.bloqueTransportista,
            a.bloqueConceptoPagoVariable,
            a.bloqueConceptoPagoVariableManual,
            a.bloqueProveedorFacturacion,
            a.bloqueTransportistaOverride,
        ].forEach(function (bloque) {
            if (bloque) {
                bloque.classList.add('d-none');
            }
        });

        ocultarCamposAjuste();
        ocultarAvisoAsignacion();

        if (a.tipoDescripcion) {
            a.tipoDescripcion.value = 'Selecciona un tipo de novedad para ver los campos necesarios.';
        }

        if (a.guiaOperativa) {
            a.guiaOperativa.textContent = 'Selecciona un tipo de novedad. El formulario mostrará sólo los campos necesarios para evitar errores.';
        }

        if (a.asignacionAyuda) {
            a.asignacionAyuda.textContent = 'Selecciona la ruta original. El listado se ajusta según el tipo de novedad.';
        }

        if (tipo === 'INASISTENCIA') {
            a.bloqueAsignacion?.classList.remove('d-none');
            mostrarCamposAjuste(['qInasistencia', 'observacion']);

            if (a.tipoDescripcion) {
                a.tipoDescripcion.value = 'Registra días no realizados en una ruta normal del calendario.';
            }

            if (a.guiaOperativa) {
                a.guiaOperativa.textContent = 'Elige la asignación original y escribe sólo la cantidad de inasistencias. No uses LOTA, OPV, comisiones ni líneas contenedoras.';
            }

            if (a.asignacionAyuda) {
                a.asignacionAyuda.textContent = 'Sólo se permiten rutas normales generadas por calendario.';
            }
        }

        if (tipo === 'FIJO_MENSUAL') {
            a.bloqueAsignacion?.classList.remove('d-none');
            mostrarCamposAjuste([
                'costo',
                'tipoDocumento',
                'detalleDocumento',
                'detalleImpuesto',
                'final',
                'totalEstimado',
                'observacion',
            ]);

            if (a.tipoDescripcion) {
                a.tipoDescripcion.value = 'Fuerza una asignación como pago único mensual sólo para este periodo.';
            }

            if (a.guiaOperativa) {
                a.guiaOperativa.textContent = 'Los fijos configurados en el maestro ya se generan automáticamente. Usa esta opción sólo si necesitas corregir una asignación puntual del periodo.';
            }

            if (a.asignacionAyuda) {
                a.asignacionAyuda.textContent = 'Selecciona una asignación sólo si realmente debes forzarla como fijo mensual excepcional.';
            }

            if (a.qCalendarioInput) a.qCalendarioInput.value = '1';
            if (a.qInasistenciaInput) a.qInasistenciaInput.value = '0';
            if (a.cantidadInput) a.cantidadInput.value = '1';
            if (a.totalManualInput) a.totalManualInput.value = a.costoInput?.value || '';
        }

        if (tipo === 'FACTURACION') {
            a.bloqueAsignacion?.classList.remove('d-none');
            a.bloqueProveedorFacturacion?.classList.remove('d-none');
            a.bloqueTransportistaOverride?.classList.remove('d-none');

            mostrarCamposAjuste([
                'costo',
                'tipoDocumento',
                'detalleDocumento',
                'detalleImpuesto',
                'final',
                'observacion',
            ]);

            if (a.tipoDescripcion) {
                a.tipoDescripcion.value = 'Cambia proveedor facturador, documento o transportista efectivo sólo para este periodo.';
            }

            if (a.guiaOperativa) {
                a.guiaOperativa.textContent = 'El detalle mensual se genera normalmente. Esta novedad sólo cambia la facturación efectiva. Para LOTA, primero carga la cantidad arriba.';
            }

            if (a.asignacionAyuda) {
                a.asignacionAyuda.textContent = 'Puedes seleccionar rutas normales o cantidades variables como LOTA. No selecciones comisiones.';
            }
        }



        if (esTipoLineaAdicional(tipo)) {
            a.bloqueProveedor?.classList.remove('d-none');
            a.bloqueTransportista?.classList.remove('d-none');

            if (tipo === 'PAGO_VARIABLE') {
                a.bloqueConceptoPagoVariable?.classList.remove('d-none');
                actualizarConceptoPagoVariableManual();

                mostrarCamposAjuste([
                    'costo',
                    'totalEstimado',
                    'observacion',
                ]);

                if (a.tipoDescripcion) {
                    a.tipoDescripcion.value = 'Registra un pago variable del mes asociado a un concepto operativo.';
                }

                if (a.guiaOperativa) {
                    a.guiaOperativa.textContent = 'Selecciona proveedor, transportista si aplica, concepto y tarifa. La tarifa se agregará como una línea propia en la pre-factura.';
                }

                if (a.servicioInput && !limpiarTexto(a.servicioInput.value)) {
                    a.servicioInput.value = 'Pago variable';
                }

                prepararPagoVariableTecnico();
                aplicarConceptoPagoVariableSeleccionado();
            } else {
                mostrarCamposAjuste([
                    'punto1',
                    'origenGasto',
                    'punto2',
                    'codigo',
                    'servicio',
                    'grupoPrefactura',
                    'costo',
                    'cantidad',
                    'total',
                    'tipoDocumento',
                    'detalleDocumento',
                    'detalleImpuesto',
                    'final',
                    'totalEstimado',
                    'observacion',
                ]);

                if (a.tipoDescripcion) {
                    a.tipoDescripcion.value = 'Crea una línea mensual adicional mediante una asignación contenedora.';
                }

                if (a.guiaOperativa) {
                    a.guiaOperativa.textContent = 'Usa este tipo para reemplazos o líneas que no existen como línea normal del mes. Escribe código, costo y cantidad con cuidado para evitar duplicados.';
                }
            }
        }




        actualizarOpcionesAsignacionPorTipo();
        actualizarEstadoCostoSegunAsignacion();
        actualizarTotalAjusteActual();
    }

    function aplicarDatosAsignacionSeleccionada() {
        const a = dom.ajuste;
        const option = selectedOption(a.asignacionSelect);

        if (!option || !option.value) {
            limpiarCamposAsignacion();
            ocultarAvisoAsignacion();
            return;
        }

        if (!validarCompatibilidadAsignacionActual()) {
            if (a.asignacionSelect) a.asignacionSelect.value = '';
            limpiarCamposAsignacion();
            return;
        }

        if (a.codigoInput) a.codigoInput.value = limpiarTexto(option.dataset.codigo || '');
        if (a.costoInput) a.costoInput.value = parseInt(option.dataset.costo || 0, 10) || '';
        if (a.punto1Input) a.punto1Input.value = limpiarTexto(option.dataset.punto1 || '');
        if (a.origenGastoInput) a.origenGastoInput.value = limpiarTexto(option.dataset.origenGasto || 'Suscripciones');
        if (a.punto2Input) a.punto2Input.value = limpiarTexto(option.dataset.punto2 || '');
        if (a.servicioInput) a.servicioInput.value = limpiarTexto(option.dataset.servicio || '');
        if (a.grupoPrefacturaInput) a.grupoPrefacturaInput.value = limpiarTexto(option.dataset.grupoPrefactura || '');

        if (normalizarTipo(a.tipoSelect?.value || '') === 'FIJO_MENSUAL') {
            if (a.qCalendarioInput) a.qCalendarioInput.value = '1';
            if (a.qInasistenciaInput) a.qInasistenciaInput.value = '0';
            if (a.cantidadInput) a.cantidadInput.value = '1';
            if (a.totalManualInput) a.totalManualInput.value = a.costoInput?.value || '';
        }

        actualizarEstadoCostoSegunAsignacion();
        actualizarAvisoAsignacionActual();
        actualizarTotalAjusteActual();
    }

    function aplicarDatosProveedorFacturacion() {
        const a = dom.ajuste;
        const option = selectedOption(a.proveedorFacturacionSelect);

        if (!option || !option.value) {
            return;
        }

        if (a.tipoDocumentoInput) {
            a.tipoDocumentoInput.value = limpiarTexto(option.dataset.tipo || a.tipoDocumentoInput.value);
        }

        if (a.detalleDocumentoInput) {
            a.detalleDocumentoInput.value = limpiarTexto(option.dataset.detalleDocumento || a.detalleDocumentoInput.value);
        }

        if (a.detalleImpuestoInput) {
            a.detalleImpuestoInput.value = limpiarTexto(option.dataset.detalleImpuesto || a.detalleImpuestoInput.value);
        }

        if (a.finalInput) {
            a.finalInput.value = limpiarTexto(option.dataset.final || a.finalInput.value);
        }
    }

    

    function limpiarDatosDocumentoAjuste() {
        const a = dom.ajuste;

        if (a.tipoDocumentoInput) {
            a.tipoDocumentoInput.value = '';
        }

        if (a.detalleDocumentoInput) {
            a.detalleDocumentoInput.value = '';
        }

        if (a.detalleImpuestoInput) {
            a.detalleImpuestoInput.value = '';
        }

        if (a.finalInput) {
            a.finalInput.value = '';
        }
    }

    function aplicarDatosProveedorLineaAdicional() {
        const a = dom.ajuste;
        const option = selectedOption(a.proveedorSelect);

        if (!option || !option.value) {
            limpiarDatosDocumentoAjuste();
            return;
        }

        if (a.tipoDocumentoInput) {
            a.tipoDocumentoInput.value = limpiarTexto(option.dataset.tipo || '');
        }

        if (a.detalleDocumentoInput) {
            a.detalleDocumentoInput.value = limpiarTexto(option.dataset.detalleDocumento || '');
        }

        if (a.detalleImpuestoInput) {
            a.detalleImpuestoInput.value = limpiarTexto(option.dataset.detalleImpuesto || '');
        }

        if (a.finalInput) {
            a.finalInput.value = limpiarTexto(option.dataset.final || '');
        }
    }



    function calcularTotalAjusteEstimado() {
        const a = dom.ajuste;
        const tipo = normalizarTipo(a.tipoSelect?.value || '');
        const costo = parseInt(a.costoInput?.value || 0, 10);

        if (tipo === 'PAGO_VARIABLE') {
            return Number.isNaN(costo) ? 0 : costo;
        }

        if (tipo === 'FIJO_MENSUAL') {
            return costo;
        }

        const qCalendario = parseInt(a.qCalendarioInput?.value || '', 10);
        const qInasistencia = parseInt(a.qInasistenciaInput?.value || 0, 10);
        const cantidadManual = parseInt(a.cantidadInput?.value || '', 10);
        const totalManual = parseInt(a.totalManualInput?.value || '', 10);

        if (!Number.isNaN(totalManual) && totalManual >= 0) {
            return totalManual;
        }

        let cantidad = 0;

        if (!Number.isNaN(cantidadManual)) {
            cantidad = cantidadManual;
        } else if (!Number.isNaN(qCalendario)) {
            cantidad = Math.max(0, qCalendario - qInasistencia);
        }

        return costo * cantidad;
    }



    

    function actualizarTotalAjusteActual() {
        const a = dom.ajuste;

        if (a.totalEstimadoInput) {
            a.totalEstimadoInput.value = formatearCLP(calcularTotalAjusteEstimado());
        }
    }

    function limpiarFormularioAjuste() {
        const a = dom.ajuste;

        if (a.tipoSelect) a.tipoSelect.value = '';

        limpiarCamposDependientesAjuste();
        actualizarCamposAjustePorTipo();
        actualizarTotalAjusteActual();
    }

    function construirClaveAjuste(tipo) {
        const a = dom.ajuste;
        tipo = normalizarTipo(tipo);

        if (esTipoAsignacionExistente(tipo)) {
            return [
                tipo,
                'ASIGNACION',
                a.asignacionSelect?.value || '',
            ].join('|');
        }

        if (tipo === 'PAGO_VARIABLE') {
            const concepto = conceptoPagoVariableSeleccionado();
            const conceptoClave = concepto.id || normalizarCodigo(concepto.manual || concepto.nombre || '');

            return [
                tipo,
                'PAGO_VARIABLE',
                a.proveedorSelect?.value || '',
                a.transportistaSelect?.value || '',
                conceptoClave,
            ].join('|');
        }

        return [
            tipo,
            'LINEA',
            a.proveedorSelect?.value || '',
            a.transportistaSelect?.value || '',
            normalizarCodigo(a.codigoInput?.value || ''),
            normalizarCodigo(a.punto1Input?.value || ''),
            normalizarCodigo(a.punto2Input?.value || ''),
            normalizarCodigo(a.origenGastoInput?.value || ''),
        ].join('|');
    }

    function existeAjusteDuplicado(clave) {
        return ajustesMensuales.some(function (ajuste) {
            return ajuste.clave_control === clave;
        });
    }

    function agregarAjusteDesdeFormulario() {
        const a = dom.ajuste;
        const tipo = normalizarTipo(a.tipoSelect?.value || '');

        if (!tipo) {
            alert('Selecciona el tipo de novedad mensual.');
            return;
        }

        if (esTipoAsignacionExistente(tipo) && !a.asignacionSelect?.value) {
            alert('Selecciona una asignación existente para esta novedad.');
            return;
        }

        if (!validarCompatibilidadAsignacionActual()) {
            return;
        }

        if (tipo === 'INASISTENCIA' && a.qInasistenciaInput?.value === '') {
            alert('Ingresa la cantidad de inasistencias.');
            return;
        }

        if (tipo === 'FIJO_MENSUAL') {
            if (a.costoInput?.value === '') {
                alert('Ingresa el valor mensual fijo.');
                return;
            }

            if (a.qCalendarioInput) a.qCalendarioInput.value = '1';
            if (a.qInasistenciaInput) a.qInasistenciaInput.value = '0';
            if (a.cantidadInput) a.cantidadInput.value = '1';
            if (a.totalManualInput) a.totalManualInput.value = a.costoInput?.value || '';
        }

        if (tipo === 'FACTURACION' && !a.proveedorFacturacionSelect?.value) {
            alert('Selecciona el proveedor facturador efectivo.');
            return;
        }

        if (tipo === 'PAGO_VARIABLE') {
            aplicarConceptoPagoVariableSeleccionado();
            prepararPagoVariableTecnico();

            const concepto = conceptoPagoVariableSeleccionado();
            const tarifa = tarifaPagoVariableActual();

            if (!concepto.id && !concepto.manual) {
                alert('Selecciona un concepto de pago variable o escribe uno manualmente.');
                return;
            }

            if (!a.proveedorSelect?.value) {
                alert('Selecciona el proveedor del pago variable.');
                return;
            }

            if (tarifa <= 0) {
                alert('Ingresa la tarifa del pago variable.');
                return;
            }
        }

        if (esTipoLineaAdicional(tipo)) {
            if (!a.proveedorSelect?.value) {
                alert(tipo === 'PAGO_VARIABLE' ? 'Selecciona el proveedor del pago variable.' : 'Selecciona el proveedor de la línea adicional.');
                return;
            }

            if (tipo !== 'PAGO_VARIABLE') {
                if (!limpiarTexto(a.codigoInput?.value)) {
                    alert('Ingresa el código de la línea adicional.');
                    return;
                }

                if (a.costoInput?.value === '') {
                    alert('Ingresa el costo de la línea adicional.');
                    return;
                }

                if (a.cantidadInput?.value === '') {
                    alert('Ingresa la cantidad de la línea adicional.');
                    return;
                }
            }
        }

        const claveControl = construirClaveAjuste(tipo);

        if (existeAjusteDuplicado(claveControl)) {
            alert('Ya agregaste una novedad igual para este periodo. Revisa la tabla inferior antes de duplicarla.');
            return;
        }

        const asignacionLabel = a.asignacionSelect?.value ? optionLabel(a.asignacionSelect) : '';
        const proveedorLabel = a.proveedorSelect?.value ? optionLabel(a.proveedorSelect) : '';
        const proveedorFacturacionLabel = a.proveedorFacturacionSelect?.value ? optionLabel(a.proveedorFacturacionSelect) : '';
        const transportistaLabel = a.transportistaSelect?.value ? optionLabel(a.transportistaSelect) : '';
        const transportistaOverrideLabel = a.transportistaOverrideSelect?.value ? optionLabel(a.transportistaOverrideSelect) : '';
        const conceptoPagoVariable = tipo === 'PAGO_VARIABLE' ? conceptoPagoVariableSeleccionado() : { id: '', manual: '', nombre: '', codigo: '' };

        let costo = a.costoInput?.value || '';
        let qCalendario = a.qCalendarioInput?.value || '';
        let qInasistencia = a.qInasistenciaInput?.value || '';
        let cantidad = a.cantidadInput?.value || '';
        let total = a.totalManualInput?.value || '';

        if (tipo === 'FACTURACION') {
            qCalendario = '';
            qInasistencia = '';
            cantidad = '';
            total = '';

            if (esOpcionCantidadVariable(selectedOption(a.asignacionSelect))) {
                costo = '';
            }
        }

        if (tipo === 'INASISTENCIA') {
            qCalendario = '';
            cantidad = '';
            total = '';
        }

        if (tipo === 'PAGO_VARIABLE') {
            const tarifa = tarifaPagoVariableActual();

            costo = tarifa > 0 ? String(tarifa) : '';
            qCalendario = '1';
            qInasistencia = '0';
            cantidad = '1';
            total = tarifa > 0 ? String(tarifa) : '';
        }

        ajustesMensuales.push({
            clave_control: claveControl,
            tipo_ajuste: tipo,

            concepto_pago_variable_id: conceptoPagoVariable.id || '',
            concepto_pago_variable_manual: conceptoPagoVariable.manual || '',
            concepto_pago_variable_label: conceptoPagoVariable.nombre || '',

            suscripcion_asignacion_id: a.asignacionSelect?.value || '',
            suscripcion_proveedor_id: a.proveedorSelect?.value || '',
            suscripcion_transportista_id: a.transportistaSelect?.value || '',

            suscripcion_proveedor_facturacion_id: a.proveedorFacturacionSelect?.value || '',
            suscripcion_transportista_override_id: a.transportistaOverrideSelect?.value || '',

            punto_1: limpiarTexto(a.punto1Input?.value),
            origen_gasto: limpiarTexto(a.origenGastoInput?.value) || 'Suscripciones',
            punto_2: limpiarTexto(a.punto2Input?.value),
            codigo: limpiarTexto(a.codigoInput?.value),
            servicio: limpiarTexto(a.servicioInput?.value),
            grupo_prefactura: limpiarTexto(a.grupoPrefacturaInput?.value),

            costo: costo,
            q_calendario: qCalendario,
            q_inasistencia: qInasistencia,
            cantidad: cantidad,
            total: total,

            tipo_documento: limpiarTexto(a.tipoDocumentoInput?.value),
            detalle_documento: limpiarTexto(a.detalleDocumentoInput?.value),
            detalle_impuesto: limpiarTexto(a.detalleImpuestoInput?.value),
            final: limpiarTexto(a.finalInput?.value),

            observacion: limpiarTexto(a.observacionInput?.value),

            asignacion_label: asignacionLabel,
            proveedor_label: proveedorLabel,
            proveedor_facturacion_label: proveedorFacturacionLabel,
            transportista_label: transportistaLabel,
            transportista_override_label: transportistaOverrideLabel,
            total_estimado: calcularTotalAjusteEstimado(),
        });

        limpiarFormularioAjuste();
        renderizarAjustes();
    }

    function detalleCambioAjuste(ajuste) {
        const tipo = normalizarTipo(ajuste.tipo_ajuste);

        if (tipo === 'FACTURACION') {
            const proveedor = ajuste.proveedor_facturacion_label || 'Sin proveedor facturador';

            const documento = [
                ajuste.tipo_documento,
                ajuste.detalle_documento,
                ajuste.detalle_impuesto,
                ajuste.final,
            ].filter(Boolean).join(' / ');

            const partes = [
                `Factura a: ${proveedor}`,
            ];

            if (documento) {
                partes.push(`Documento: ${documento}`);
            }

            if (ajuste.transportista_override_label) {
                partes.push(`Transportista efectivo: ${ajuste.transportista_override_label}`);
            }

            if (ajuste.costo) {
                partes.push(`Costo ajustado: ${formatearCLP(parseInt(ajuste.costo || 0, 10))}`);
            }

            return partes.join('\n');
        }

        if (tipo === 'INASISTENCIA') {
            return `Descuenta ${ajuste.q_inasistencia || 0} día(s) de calendario`;
        }

        if (tipo === 'FIJO_MENSUAL') {
            return `Fuerza pago mensual único por ${formatearCLP(parseInt(ajuste.costo || 0, 10))}`;
        }

        if (tipo === 'PAGO_VARIABLE') {
            const proveedor = ajuste.proveedor_label || 'Sin proveedor';
            const transportista = ajuste.transportista_label || '';
            const concepto = ajuste.concepto_pago_variable_label || ajuste.concepto_pago_variable_manual || 'Sin concepto';
            const tarifa = parseInt(ajuste.costo || ajuste.total || 0, 10);

            const partes = [
                `Concepto: ${concepto}`,
                `Proveedor: ${proveedor}`,
                `Tarifa: ${formatearCLP(tarifa)}`,
            ];

            if (transportista && transportista !== '—') {
                partes.push(`Transportista: ${transportista}`);
            }

            if (ajuste.observacion) {
                partes.push(`Observación: ${ajuste.observacion}`);
            }

            return partes.join('\n');
        }

        if (esTipoLineaAdicional(tipo)) {
            const proveedor = ajuste.proveedor_label || 'Sin proveedor';
            const transportista = ajuste.transportista_label || 'Sin transportista';
            const concepto = ajuste.concepto_pago_variable_label || ajuste.concepto_pago_variable_manual || '';

            const documento = [
                ajuste.tipo_documento,
                ajuste.detalle_documento,
                ajuste.detalle_impuesto,
                ajuste.final,
            ].filter(Boolean).join(' / ');

            const partes = [];

            if (tipo === 'PAGO_VARIABLE' && concepto) {
                partes.push(`Concepto: ${concepto}`);
            }

            partes.push(`Paga a: ${proveedor}`);
            partes.push(`Transportista: ${transportista}`);

            if (documento) {
                partes.push(`Documento: ${documento}`);
            }

            return partes.join('\n');
        }

        return '—';
    }

    function renderizarAjustes() {
        const a = dom.ajuste;

        if (!a.hiddenContainer || !a.resumenBody) {
            return;
        }

        a.hiddenContainer.innerHTML = '';
        a.resumenBody.innerHTML = '';

        if (ajustesMensuales.length === 0) {
            a.resumenBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-muted text-center">
                        No hay novedades mensuales agregadas para este periodo.
                    </td>
                </tr>
            `;

            if (a.cantidadTexto) a.cantidadTexto.textContent = '0';
            if (a.totalTexto) a.totalTexto.textContent = formatearCLP(0);

            return;
        }

        let total = 0;

        ajustesMensuales.forEach(function (ajuste, index) {
            total += parseInt(ajuste.total_estimado || 0, 10);

            agregarHidden(`ajustes_mensuales[${index}][tipo_ajuste]`, ajuste.tipo_ajuste, a.hiddenContainer);
            agregarHidden(`ajustes_mensuales[${index}][concepto_pago_variable_id]`, ajuste.concepto_pago_variable_id, a.hiddenContainer);
            agregarHidden(`ajustes_mensuales[${index}][concepto_pago_variable_manual]`, ajuste.concepto_pago_variable_manual, a.hiddenContainer);

            agregarHidden(`ajustes_mensuales[${index}][suscripcion_asignacion_id]`, ajuste.suscripcion_asignacion_id, a.hiddenContainer);
            agregarHidden(`ajustes_mensuales[${index}][suscripcion_proveedor_id]`, ajuste.suscripcion_proveedor_id, a.hiddenContainer);
            agregarHidden(`ajustes_mensuales[${index}][suscripcion_transportista_id]`, ajuste.suscripcion_transportista_id, a.hiddenContainer);

            agregarHidden(`ajustes_mensuales[${index}][suscripcion_proveedor_facturacion_id]`, ajuste.suscripcion_proveedor_facturacion_id, a.hiddenContainer);
            agregarHidden(`ajustes_mensuales[${index}][suscripcion_transportista_override_id]`, ajuste.suscripcion_transportista_override_id, a.hiddenContainer);

            agregarHidden(`ajustes_mensuales[${index}][punto_1]`, ajuste.punto_1, a.hiddenContainer);
            agregarHidden(`ajustes_mensuales[${index}][origen_gasto]`, ajuste.origen_gasto, a.hiddenContainer);
            agregarHidden(`ajustes_mensuales[${index}][punto_2]`, ajuste.punto_2, a.hiddenContainer);
            agregarHidden(`ajustes_mensuales[${index}][codigo]`, ajuste.codigo, a.hiddenContainer);
            agregarHidden(`ajustes_mensuales[${index}][servicio]`, ajuste.servicio, a.hiddenContainer);
            agregarHidden(`ajustes_mensuales[${index}][grupo_prefactura]`, ajuste.grupo_prefactura, a.hiddenContainer);

            agregarHidden(`ajustes_mensuales[${index}][costo]`, ajuste.costo, a.hiddenContainer);
            agregarHidden(`ajustes_mensuales[${index}][q_calendario]`, ajuste.q_calendario, a.hiddenContainer);
            agregarHidden(`ajustes_mensuales[${index}][q_inasistencia]`, ajuste.q_inasistencia, a.hiddenContainer);
            agregarHidden(`ajustes_mensuales[${index}][cantidad]`, ajuste.cantidad, a.hiddenContainer);
            agregarHidden(`ajustes_mensuales[${index}][total]`, ajuste.total, a.hiddenContainer);

            agregarHidden(`ajustes_mensuales[${index}][tipo_documento]`, ajuste.tipo_documento, a.hiddenContainer);
            agregarHidden(`ajustes_mensuales[${index}][detalle_documento]`, ajuste.detalle_documento, a.hiddenContainer);
            agregarHidden(`ajustes_mensuales[${index}][detalle_impuesto]`, ajuste.detalle_impuesto, a.hiddenContainer);
            agregarHidden(`ajustes_mensuales[${index}][final]`, ajuste.final, a.hiddenContainer);

            agregarHidden(`ajustes_mensuales[${index}][observacion]`, ajuste.observacion, a.hiddenContainer);

            const tipoRender = normalizarTipo(ajuste.tipo_ajuste);
            const cantidadVisible = tipoRender === 'PAGO_VARIABLE'
                ? '—'
                : (ajuste.cantidad || ajuste.q_inasistencia || '—');

            const row = document.createElement('tr');

            row.innerHTML = `
                <td>${escaparHtml(ajuste.tipo_ajuste || '—')}</td>
                <td>${escaparHtml(ajuste.asignacion_label || ajuste.proveedor_label || '—')}</td>
                <td style="white-space: pre-line;">${escaparHtml(detalleCambioAjuste(ajuste))}</td>
                <td>${escaparHtml(ajuste.codigo || '—')}</td>
                <td class="text-end">${escaparHtml(cantidadVisible)}</td>
                <td class="text-end">${formatearCLP(parseInt(ajuste.total_estimado || 0, 10))}</td>
                <td class="text-center">
                    <button
                        type="button"
                        class="btn btn-outline-danger btn-sm"
                        data-index="${index}"
                        data-action="eliminar-ajuste"
                    >
                        Eliminar
                    </button>
                </td>
            `;

            a.resumenBody.appendChild(row);
        });

        if (a.cantidadTexto) a.cantidadTexto.textContent = String(ajustesMensuales.length);
        if (a.totalTexto) a.totalTexto.textContent = formatearCLP(total);
    }

    function restaurarComisionesIniciales() {
        const c = dom.comision;

        comisionesIniciales.forEach(function (comision) {
            comisiones.push({
                suscripcion_proveedor_id: comision.suscripcion_proveedor_id,
                suscripcion_transportista_id: comision.suscripcion_transportista_id,
                proveedor_label: labelPorValor(c.proveedorSelect, comision.suscripcion_proveedor_id),
                transportista_label: labelPorValor(c.transportistaSelect, comision.suscripcion_transportista_id),
                punto_1: limpiarTexto(comision.punto_1 || ''),
                origen_gasto: limpiarTexto(comision.origen_gasto || 'Suscripciones'),
                punto_2: limpiarTexto(comision.punto_2 || ''),
                servicio: limpiarTexto(comision.servicio || 'Comisión mensual'),
                costo: parseInt(comision.costo || 0, 10),
                observacion: limpiarTexto(comision.observacion || ''),
            });
        });
    }

    function restaurarAjustesIniciales() {
        ajustesIniciales.forEach(function (ajuste) {

            const tipo = normalizarTipo(ajuste.tipo_ajuste || '');



            let totalEstimado = parseInt(ajuste.total || 0, 10)
                || (parseInt(ajuste.costo || 0, 10) * parseInt(ajuste.cantidad || 0, 10));

            if (tipo === 'PAGO_VARIABLE' && totalEstimado === 0) {
                totalEstimado = parseInt(ajuste.costo || 0, 10);
            }

            let claveControl = [
                tipo,
                ajuste.suscripcion_asignacion_id ? 'ASIGNACION' : 'LINEA',
                ajuste.suscripcion_asignacion_id || ajuste.suscripcion_proveedor_id || '',
                ajuste.suscripcion_transportista_id || '',
                normalizarCodigo(ajuste.codigo || ''),
                normalizarCodigo(ajuste.punto_1 || ''),
                normalizarCodigo(ajuste.punto_2 || ''),
                normalizarCodigo(ajuste.origen_gasto || ''),
            ].join('|');

            if (tipo === 'PAGO_VARIABLE') {
                const conceptoClave = ajuste.concepto_pago_variable_id
                    || normalizarCodigo(
                        ajuste.concepto_pago_variable_manual
                        || ajuste.concepto_pago_variable_snapshot
                        || ajuste.codigo
                        || ''
                    );

                claveControl = [
                    tipo,
                    'PAGO_VARIABLE',
                    ajuste.suscripcion_proveedor_id || '',
                    ajuste.suscripcion_transportista_id || '',
                    conceptoClave,
                ].join('|');
            }




            ajustesMensuales.push({
                clave_control: claveControl,
                tipo_ajuste: tipo,

                concepto_pago_variable_id: ajuste.concepto_pago_variable_id || '',
                concepto_pago_variable_manual: limpiarTexto(ajuste.concepto_pago_variable_manual || ''),
                concepto_pago_variable_label: labelPorValor(dom.ajuste.conceptoPagoVariableSelect, ajuste.concepto_pago_variable_id) !== '—'
                    ? labelPorValor(dom.ajuste.conceptoPagoVariableSelect, ajuste.concepto_pago_variable_id)
                    : limpiarTexto(ajuste.concepto_pago_variable_manual || ''),

                suscripcion_asignacion_id: ajuste.suscripcion_asignacion_id || '',
                suscripcion_proveedor_id: ajuste.suscripcion_proveedor_id || '',
                suscripcion_transportista_id: ajuste.suscripcion_transportista_id || '',

                suscripcion_proveedor_facturacion_id: ajuste.suscripcion_proveedor_facturacion_id || '',
                suscripcion_transportista_override_id: ajuste.suscripcion_transportista_override_id || '',

                punto_1: limpiarTexto(ajuste.punto_1 || ''),
                origen_gasto: limpiarTexto(ajuste.origen_gasto || 'Suscripciones'),
                punto_2: limpiarTexto(ajuste.punto_2 || ''),
                codigo: limpiarTexto(ajuste.codigo || ''),
                servicio: limpiarTexto(ajuste.servicio || ''),
                grupo_prefactura: limpiarTexto(ajuste.grupo_prefactura || ''),

                costo: ajuste.costo || '',
                q_calendario: ajuste.q_calendario || '',
                q_inasistencia: ajuste.q_inasistencia || '',
                cantidad: ajuste.cantidad || '',
                total: ajuste.total || '',

                tipo_documento: limpiarTexto(ajuste.tipo_documento || ''),
                detalle_documento: limpiarTexto(ajuste.detalle_documento || ''),
                detalle_impuesto: limpiarTexto(ajuste.detalle_impuesto || ''),
                final: limpiarTexto(ajuste.final || ''),

                observacion: limpiarTexto(ajuste.observacion || ''),

                asignacion_label: labelPorValor(dom.ajuste.asignacionSelect, ajuste.suscripcion_asignacion_id),
                proveedor_label: labelPorValor(dom.ajuste.proveedorSelect, ajuste.suscripcion_proveedor_id),
                proveedor_facturacion_label: labelPorValor(dom.ajuste.proveedorFacturacionSelect, ajuste.suscripcion_proveedor_facturacion_id),
                transportista_label: labelPorValor(dom.ajuste.transportistaSelect, ajuste.suscripcion_transportista_id),
                transportista_override_label: labelPorValor(dom.ajuste.transportistaOverrideSelect, ajuste.suscripcion_transportista_override_id),

                total_estimado: totalEstimado,
            });
        });
    }

    function registrarEventos() {
        const cantidad = dom.cantidadVariable;
        const c = dom.comision;
        const a = dom.ajuste;

        if (cantidad.asignacionSelect) {
            cantidad.asignacionSelect.addEventListener('change', actualizarTotalVariable);
        }

        if (cantidad.cantidadInput) {
            cantidad.cantidadInput.addEventListener('input', actualizarTotalVariable);
        }

        if (c.costoInput) {
            c.costoInput.addEventListener('input', actualizarTotalComisionActual);
        }

        if (c.agregarBtn) {
            c.agregarBtn.addEventListener('click', agregarComisionDesdeFormulario);
        }

        if (c.resumenBody) {
            c.resumenBody.addEventListener('click', function (event) {
                const button = event.target.closest('[data-action="eliminar-comision"]');

                if (!button) {
                    return;
                }

                const index = parseInt(button.dataset.index, 10);

                comisiones.splice(index, 1);
                renderizarComisiones();
            });
        }

        if (a.tipoSelect) {
            a.tipoSelect.addEventListener('change', actualizarCamposAjustePorTipo);
        }

        if (a.asignacionSelect) {
            a.asignacionSelect.addEventListener('change', aplicarDatosAsignacionSeleccionada);
        }

        if (a.proveedorFacturacionSelect) {
            a.proveedorFacturacionSelect.addEventListener('change', aplicarDatosProveedorFacturacion);
        }

        if (a.proveedorSelect) {
            a.proveedorSelect.addEventListener('change', aplicarDatosProveedorLineaAdicional);
        }

        if (a.conceptoPagoVariableSelect) {
            a.conceptoPagoVariableSelect.addEventListener('change', aplicarConceptoPagoVariableSeleccionado);
        }

        if (a.conceptoPagoVariableManualInput) {
            a.conceptoPagoVariableManualInput.addEventListener('input', aplicarConceptoPagoVariableSeleccionado);
        }

        [
            a.costoInput,
            a.qCalendarioInput,
            a.qInasistenciaInput,
            a.cantidadInput,
            a.totalManualInput,
        ].forEach(function (input) {
            if (input) {
                input.addEventListener('input', function () {
                    if (normalizarTipo(a.tipoSelect?.value || '') === 'FIJO_MENSUAL') {
                        if (a.qCalendarioInput) a.qCalendarioInput.value = '1';
                        if (a.qInasistenciaInput) a.qInasistenciaInput.value = '0';
                        if (a.cantidadInput) a.cantidadInput.value = '1';
                        if (a.totalManualInput) a.totalManualInput.value = a.costoInput?.value || '';
                    }

                    actualizarTotalAjusteActual();
                });
            }
        });

        if (a.agregarBtn) {
            a.agregarBtn.addEventListener('click', agregarAjusteDesdeFormulario);
        }

        if (a.resumenBody) {
            a.resumenBody.addEventListener('click', function (event) {
                const button = event.target.closest('[data-action="eliminar-ajuste"]');

                if (!button) {
                    return;
                }

                const index = parseInt(button.dataset.index, 10);

                ajustesMensuales.splice(index, 1);
                renderizarAjustes();
            });
        }
    }

    function inicializar() {
        inicializarAcordeonesSuscripciones();
        registrarEventos();

        restaurarComisionesIniciales();
        restaurarAjustesIniciales();

        actualizarTotalVariable();
        actualizarTotalComisionActual();
        actualizarCamposAjustePorTipo();
        actualizarTotalAjusteActual();

        renderizarComisiones();
        renderizarAjustes();
    }

    inicializar();
});