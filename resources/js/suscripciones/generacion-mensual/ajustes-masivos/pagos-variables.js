/**
 * resources/js/suscripciones/generacion-mensual/ajustes-masivos/pagos-variables.js
 *
 * Maneja el modal de carga masiva de pagos variables.
 * No guarda en BD directamente: sólo prepara novedades y las envía
 * al flujo actual de ajustesMensuales[].
 */

import {
    escaparHtml,
    limpiarTexto,
    normalizarCodigo,
    slugCodigo,
} from '../utils';

export function inicializarPagosVariablesMasivos(dom, ajustesMensualesApi = {}) {
    const modal = document.getElementById('modal-ajustes-masivos-pago-variable');

    if (!modal) {
        return;
    }

    const transportistaTemplate = document.getElementById('pago-variable-masivo-transportista-template');
    const conceptoTemplate = document.getElementById('pago-variable-masivo-concepto-template');

    const buscadorInput = document.getElementById('pago-variable-masivo-buscador');
    const buscarBtn = document.getElementById('btn-pago-variable-masivo-buscar');
    const limpiarBusquedaBtn = document.getElementById('btn-pago-variable-masivo-limpiar-busqueda');

    const proveedoresBody = document.getElementById('pago-variable-masivo-proveedores-body');
    const seleccionadosBody = document.getElementById('pago-variable-masivo-seleccionados-body');

    const limpiarSeleccionBtn = document.getElementById('btn-pago-variable-masivo-limpiar');
    const confirmarBtn = document.getElementById('btn-confirmar-pagos-variables-masivos');

    const contadorSeleccionados = document.getElementById('pago-variable-masivo-seleccionados-contador');
    const errorBox = document.getElementById('pago-variable-masivo-error');

    const pagos = new Map();

    function ocultarError() {
        if (!errorBox) {
            return;
        }

        errorBox.textContent = '';
        errorBox.classList.add('d-none');
    }

    function mostrarError(mensaje) {
        if (!errorBox) {
            alert(mensaje);
            return;
        }

        errorBox.textContent = mensaje;
        errorBox.classList.remove('d-none');
    }

    function selectedOption(select) {
        return select?.options[select.selectedIndex] || null;
    }

    function entero(valor) {
        if (valor === null || valor === undefined || valor === '') {
            return null;
        }

        const numero = parseInt(valor, 10);

        return Number.isNaN(numero) ? null : numero;
    }

    function normalizarBusqueda(valor) {
        return limpiarTexto(valor || '')
            .toUpperCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function filasProveedor() {
        if (!proveedoresBody) {
            return [];
        }

        return Array.from(proveedoresBody.querySelectorAll('[data-pago-variable-masivo-proveedor]'));
    }

    function checkboxesProveedor() {
        if (!proveedoresBody) {
            return [];
        }

        return Array.from(proveedoresBody.querySelectorAll('[data-pago-variable-masivo-checkbox]'));
    }

    function actualizarContador() {
        if (contadorSeleccionados) {
            contadorSeleccionados.textContent = String(pagos.size);
        }
    }

    function datosProveedorDesdeFila(row) {
        return {
            suscripcion_proveedor_id: row.dataset.proveedorId || '',
            proveedor_label: limpiarTexto(row.dataset.label || ''),
            razon_social: limpiarTexto(row.dataset.razonSocial || ''),
            rut: limpiarTexto(row.dataset.rut || ''),

            tipo_documento: limpiarTexto(row.dataset.tipo || ''),
            detalle_documento: limpiarTexto(row.dataset.detalleDocumento || ''),
            detalle_impuesto: limpiarTexto(row.dataset.detalleImpuesto || ''),
            final: limpiarTexto(row.dataset.final || ''),

            suscripcion_transportista_id: '',
            transportista_label: '',

            concepto_pago_variable_id: '',
            concepto_pago_variable_manual: '',
            concepto_pago_variable_label: '',
            codigo_concepto: '',
            es_otro: false,

            costo: '',
            observacion: '',
        };
    }

    function aplicarBusqueda() {
        const termino = normalizarBusqueda(buscadorInput?.value || '');

        filasProveedor().forEach(function (row) {
            const textoBusqueda = normalizarBusqueda(row.dataset.busqueda || '');
            const visible = !termino || textoBusqueda.includes(termino);

            row.classList.toggle('d-none', !visible);
        });
    }

    function limpiarBusqueda() {
        if (buscadorInput) {
            buscadorInput.value = '';
        }

        aplicarBusqueda();

        if (buscadorInput) {
            buscadorInput.focus();
        }
    }

    function marcarCheckboxProveedor(proveedorId, marcado) {
        checkboxesProveedor().forEach(function (checkbox) {
            if (String(checkbox.value) === String(proveedorId)) {
                checkbox.checked = marcado;
            }
        });
    }

    function obtenerConceptoDesdeFila(row) {
        const conceptoSelect = row.querySelector('[data-pago-variable-masivo-concepto]');
        const option = selectedOption(conceptoSelect);
        const valor = conceptoSelect?.value || '';

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
            const manual = limpiarTexto(row.querySelector('[data-pago-variable-masivo-concepto-manual]')?.value || '');

            return {
                id: '',
                manual,
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

    function actualizarConceptoManual(row) {
        const concepto = obtenerConceptoDesdeFila(row);

        const wrapper = row.querySelector('[data-pago-variable-masivo-concepto-manual-wrapper]');
        const input = row.querySelector('[data-pago-variable-masivo-concepto-manual]');
        const ayuda = row.querySelector('[data-pago-variable-masivo-concepto-manual-ayuda]');

        if (wrapper) {
            wrapper.classList.toggle('text-muted', !concepto.esOtro);
        }

        if (input) {
            input.classList.toggle('d-none', !concepto.esOtro);

            if (!concepto.esOtro) {
                input.value = '';
            }
        }

        if (ayuda) {
            ayuda.classList.toggle('d-none', concepto.esOtro);
        }
    }

    function guardarValoresEditados() {
        if (!seleccionadosBody) {
            return;
        }

        seleccionadosBody.querySelectorAll('[data-pago-variable-masivo-seleccionado]').forEach(function (row) {
            const proveedorId = row.dataset.proveedorId;

            if (!proveedorId || !pagos.has(proveedorId)) {
                return;
            }

            const item = pagos.get(proveedorId);

            const transportistaSelect = row.querySelector('[data-pago-variable-masivo-transportista]');
            const transportistaOption = selectedOption(transportistaSelect);
            const concepto = obtenerConceptoDesdeFila(row);

            item.suscripcion_transportista_id = transportistaSelect?.value || '';
            item.transportista_label = transportistaOption && transportistaOption.value
                ? limpiarTexto(transportistaOption.dataset.label || transportistaOption.text || '')
                : '';

            item.concepto_pago_variable_id = concepto.id || '';
            item.concepto_pago_variable_manual = concepto.manual || '';
            item.concepto_pago_variable_label = concepto.nombre || '';
            item.codigo_concepto = concepto.codigo || '';
            item.es_otro = concepto.esOtro;

            item.costo = row.querySelector('[data-pago-variable-masivo-tarifa]')?.value || '';
            item.observacion = limpiarTexto(row.querySelector('[data-pago-variable-masivo-observacion]')?.value || '');

            pagos.set(proveedorId, item);
        });
    }

    function agregarProveedor(row) {
        const proveedorId = row.dataset.proveedorId || '';

        if (!proveedorId) {
            return;
        }

        if (!pagos.has(proveedorId)) {
            pagos.set(proveedorId, datosProveedorDesdeFila(row));
        }

        renderizarSeleccionados();
    }

    function quitarProveedor(proveedorId) {
        pagos.delete(String(proveedorId));
        marcarCheckboxProveedor(proveedorId, false);
        ocultarError();
        renderizarSeleccionados();
    }

    function limpiarSeleccion() {
        pagos.clear();

        checkboxesProveedor().forEach(function (checkbox) {
            checkbox.checked = false;
        });

        ocultarError();
        renderizarSeleccionados();
    }

    function renderizarSeleccionados() {
        if (!seleccionadosBody) {
            return;
        }

        seleccionadosBody.innerHTML = '';

        if (pagos.size === 0) {
            seleccionadosBody.innerHTML = `
                <tr data-pago-variable-masivo-empty>
                    <td colspan="7" class="text-muted text-center">
                        No hay proveedores seleccionados.
                    </td>
                </tr>
            `;

            actualizarContador();
            return;
        }

        pagos.forEach(function (item, proveedorId) {
            const row = document.createElement('tr');

            row.dataset.pagoVariableMasivoSeleccionado = '1';
            row.dataset.proveedorId = proveedorId;

            row.innerHTML = `
                <td>
                    <div class="fw-semibold">
                        ${escaparHtml(item.razon_social || item.proveedor_label || 'Proveedor')}
                    </div>

                    <div class="small text-muted">
                        ${escaparHtml(item.rut || '')}
                        ${item.tipo_documento ? '<span class="mx-1">|</span>' : ''}
                        ${escaparHtml(item.tipo_documento || '')}
                    </div>
                </td>

                <td>
                    <select
                        class="form-select form-select-sm"
                        data-pago-variable-masivo-transportista
                    >
                        ${transportistaTemplate?.innerHTML || '<option value="">Sin transportista / no aplica</option>'}
                    </select>
                </td>

                <td>
                    <select
                        class="form-select form-select-sm"
                        data-pago-variable-masivo-concepto
                    >
                        ${conceptoTemplate?.innerHTML || '<option value="">Seleccionar concepto...</option>'}
                    </select>
                </td>

                <td data-pago-variable-masivo-concepto-manual-wrapper>
                    <input
                        type="text"
                        class="form-control form-control-sm"
                        value="${escaparHtml(item.concepto_pago_variable_manual || '')}"
                        placeholder="Ej: Apoyo de ruta"
                        data-pago-variable-masivo-concepto-manual
                    >

                    <span
                        class="small text-muted"
                        data-pago-variable-masivo-concepto-manual-ayuda
                    >
                        Sólo si el concepto es “Otro”.
                    </span>
                </td>

                <td>
                    <input
                        type="number"
                        class="form-control form-control-sm text-end"
                        min="0"
                        value="${escaparHtml(item.costo || '')}"
                        placeholder="0"
                        data-pago-variable-masivo-tarifa
                    >
                </td>

                <td>
                    <input
                        type="text"
                        class="form-control form-control-sm"
                        value="${escaparHtml(item.observacion || '')}"
                        placeholder="Observación opcional"
                        data-pago-variable-masivo-observacion
                    >
                </td>

                <td class="text-center">
                    <button
                        type="button"
                        class="btn btn-outline-danger btn-sm"
                        data-pago-variable-masivo-quitar
                        data-proveedor-id="${escaparHtml(proveedorId)}"
                    >
                        Quitar
                    </button>
                </td>
            `;

            seleccionadosBody.appendChild(row);

            const transportistaSelect = row.querySelector('[data-pago-variable-masivo-transportista]');
            const conceptoSelect = row.querySelector('[data-pago-variable-masivo-concepto]');

            if (transportistaSelect) {
                transportistaSelect.value = item.suscripcion_transportista_id || '';
            }

            if (conceptoSelect) {
                conceptoSelect.value = item.concepto_pago_variable_id || (item.es_otro ? '__OTRO__' : '');
            }

            actualizarConceptoManual(row);
        });

        actualizarContador();
    }

    function codigoConcepto(item) {
        const codigo = limpiarTexto(item.codigo_concepto || '');

        if (codigo) {
            return slugCodigo(codigo);
        }

        const concepto = limpiarTexto(
            item.concepto_pago_variable_label
            || item.concepto_pago_variable_manual
            || ''
        );

        return concepto ? slugCodigo(concepto) : 'PAGO_VARIABLE';
    }

    function nombreConcepto(item) {
        return limpiarTexto(
            item.concepto_pago_variable_label
            || item.concepto_pago_variable_manual
            || ''
        );
    }

    function construirClaveControl(item) {
        const conceptoClave = item.concepto_pago_variable_id
            || normalizarCodigo(item.concepto_pago_variable_manual || item.concepto_pago_variable_label || '');

        return [
            'PAGO_VARIABLE',
            'PAGO_VARIABLE',
            item.suscripcion_proveedor_id || '',
            item.suscripcion_transportista_id || '',
            conceptoClave,
        ].join('|');
    }

    function construirAjustesPagosVariables() {
        guardarValoresEditados();

        const ajustes = [];

        pagos.forEach(function (item) {
            const tarifa = entero(item.costo) || 0;
            const conceptoNombre = nombreConcepto(item);
            const conceptoCodigo = codigoConcepto(item);

            ajustes.push({
                clave_control: construirClaveControl(item),

                tipo_ajuste: 'PAGO_VARIABLE',

                concepto_pago_variable_id: item.concepto_pago_variable_id || '',
                concepto_pago_variable_manual: item.concepto_pago_variable_manual || '',
                concepto_pago_variable_label: conceptoNombre,

                suscripcion_asignacion_id: '',
                suscripcion_proveedor_id: item.suscripcion_proveedor_id || '',
                suscripcion_transportista_id: item.suscripcion_transportista_id || '',

                suscripcion_proveedor_facturacion_id: '',
                suscripcion_transportista_override_id: '',

                punto_1: '',
                origen_gasto: 'Suscripciones',
                punto_2: '',
                codigo: conceptoNombre ? 'PV-' + conceptoCodigo : 'PV-PAGO_VARIABLE',
                servicio: conceptoNombre ? 'Pago variable - ' + conceptoNombre : 'Pago variable',
                grupo_prefactura: '',

                costo: String(tarifa),
                q_calendario: '1',
                q_inasistencia: '0',
                cantidad: '1',
                total: String(tarifa),

                tipo_documento: item.tipo_documento || '',
                detalle_documento: item.detalle_documento || '',
                detalle_impuesto: item.detalle_impuesto || '',
                final: item.final || '',

                observacion: item.observacion || '',

                asignacion_label: '',
                proveedor_label: item.proveedor_label || '',
                proveedor_facturacion_label: '',
                transportista_label: item.transportista_label || '',
                transportista_override_label: '',

                total_estimado: tarifa,
            });
        });

        return ajustes;
    }

    function validarAjustes(ajustes) {
        if (ajustes.length === 0) {
            return 'Selecciona al menos un proveedor antes de confirmar.';
        }

        const sinProveedor = ajustes.find(function (ajuste) {
            return !ajuste.suscripcion_proveedor_id;
        });

        if (sinProveedor) {
            return 'Todos los pagos variables deben tener proveedor.';
        }

        const sinConcepto = ajustes.find(function (ajuste) {
            return !ajuste.concepto_pago_variable_id
                && !limpiarTexto(ajuste.concepto_pago_variable_manual || '');
        });

        if (sinConcepto) {
            return 'Todos los pagos variables deben tener concepto o concepto manual.';
        }

        const tarifaInvalida = ajustes.find(function (ajuste) {
            const tarifa = entero(ajuste.costo);

            return tarifa === null || tarifa <= 0;
        });

        if (tarifaInvalida) {
            return 'Todos los pagos variables deben tener una tarifa mayor a 0.';
        }

        return '';
    }

    function cerrarModal() {
        if (window.jQuery && typeof window.jQuery(modal).modal === 'function') {
            window.jQuery(modal).modal('hide');
            return;
        }

        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        modal.style.display = 'none';

        document.body.classList.remove('modal-open');

        document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
            backdrop.remove();
        });
    }

    function confirmarPagosVariables() {
        ocultarError();

        const ajustes = construirAjustesPagosVariables();
        const error = validarAjustes(ajustes);

        if (error) {
            mostrarError(error);
            return;
        }

        if (typeof ajustesMensualesApi.agregarAjustesMasivos === 'function') {
            const resultado = ajustesMensualesApi.agregarAjustesMasivos(ajustes);

            if ((resultado?.agregados ?? ajustes.length) > 0) {
                limpiarSeleccion();
                cerrarModal();
                return;
            }

            if ((resultado?.duplicados ?? 0) > 0) {
                mostrarError(`Se omitieron ${resultado.duplicados} pago(s) variable(s) porque ya estaban agregados en el resumen.`);
            }

            return;
        }

        document.dispatchEvent(new CustomEvent('suscripciones:ajustes-masivos', {
            detail: {
                tipo: 'PAGO_VARIABLE',
                ajustes: ajustes,
            },
        }));

        limpiarSeleccion();
        cerrarModal();
    }

    function registrarEventos() {
        if (buscarBtn) {
            buscarBtn.addEventListener('click', aplicarBusqueda);
        }

        if (limpiarBusquedaBtn) {
            limpiarBusquedaBtn.addEventListener('click', limpiarBusqueda);
        }

        if (buscadorInput) {
            buscadorInput.addEventListener('input', aplicarBusqueda);

            buscadorInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    aplicarBusqueda();
                }
            });
        }

        if (proveedoresBody) {
            proveedoresBody.addEventListener('change', function (event) {
                const checkbox = event.target.closest('[data-pago-variable-masivo-checkbox]');

                if (!checkbox) {
                    return;
                }

                guardarValoresEditados();
                ocultarError();

                const row = checkbox.closest('[data-pago-variable-masivo-proveedor]');

                if (!row) {
                    return;
                }

                if (checkbox.checked) {
                    agregarProveedor(row);
                    return;
                }

                quitarProveedor(checkbox.value);
            });
        }

        if (limpiarSeleccionBtn) {
            limpiarSeleccionBtn.addEventListener('click', limpiarSeleccion);
        }

        if (confirmarBtn) {
            confirmarBtn.addEventListener('click', confirmarPagosVariables);
        }

        if (seleccionadosBody) {
            seleccionadosBody.addEventListener('click', function (event) {
                const button = event.target.closest('[data-pago-variable-masivo-quitar]');

                if (!button) {
                    return;
                }

                guardarValoresEditados();
                quitarProveedor(button.dataset.proveedorId);
            });

            seleccionadosBody.addEventListener('change', function (event) {
                const row = event.target.closest('[data-pago-variable-masivo-seleccionado]');

                if (!row) {
                    return;
                }

                if (event.target.closest('[data-pago-variable-masivo-concepto]')) {
                    actualizarConceptoManual(row);
                }

                guardarValoresEditados();
                ocultarError();
            });

            seleccionadosBody.addEventListener('input', function (event) {
                const row = event.target.closest('[data-pago-variable-masivo-seleccionado]');

                if (!row) {
                    return;
                }

                guardarValoresEditados();
                ocultarError();
            });
        }
    }

    registrarEventos();
    aplicarBusqueda();
    renderizarSeleccionados();
}