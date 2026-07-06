/**
 * resources/js/suscripciones/generacion-mensual/ajustes-masivos/facturacion.js
 *
 * Maneja el modal de carga masiva de cambios de facturación.
 * No guarda en BD directamente: sólo prepara novedades y las envía
 * al flujo actual de ajustesMensuales[].
 */

import {
    escaparHtml,
    limpiarTexto,
    normalizarCodigo,
} from '../utils';

export function inicializarFacturacionesMasivas(dom, ajustesMensualesApi = {}) {
    const modal = document.getElementById('modal-ajustes-masivos-facturacion');

    if (!modal) {
        return;
    }

    const proveedorTemplate = document.getElementById('facturacion-masiva-proveedor-template');
    const transportistaTemplate = document.getElementById('facturacion-masiva-transportista-template');


    const buscador = document.getElementById('facturacion-masiva-buscador');
    const buscarBtn = document.getElementById('btn-facturacion-masiva-buscar');
    const limpiarBusquedaBtn = document.getElementById('btn-facturacion-masiva-limpiar-busqueda');

    const asignacionesBody = document.getElementById('facturacion-masiva-asignaciones-body');
    const seleccionadasBody = document.getElementById('facturacion-masiva-seleccionadas-body');
    const contadorSeleccionadas = document.getElementById('facturacion-masiva-seleccionadas-contador');
    const errorBox = document.getElementById('facturacion-masiva-error');
    const limpiarBtn = document.getElementById('btn-facturacion-masiva-limpiar');
    const confirmarBtn = document.getElementById('btn-confirmar-facturaciones-masivas');

    const seleccionadas = new Map();

    function filasAsignaciones() {
        return Array.from(modal.querySelectorAll('[data-facturacion-masiva-asignacion]'));
    }

    function checkboxesAsignaciones() {
        return Array.from(modal.querySelectorAll('[data-facturacion-masiva-checkbox]'));
    }

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

    function obtenerDatosFila(row) {
        return {
            suscripcion_asignacion_id: row.dataset.asignacionId || '',
            label: limpiarTexto(row.dataset.label || ''),
            codigo: limpiarTexto(row.dataset.codigo || ''),
            costo_base: row.dataset.costo || '',
            punto_1: limpiarTexto(row.dataset.punto1 || ''),
            origen_gasto: limpiarTexto(row.dataset.origenGasto || 'Suscripciones'),
            punto_2: limpiarTexto(row.dataset.punto2 || ''),
            servicio: limpiarTexto(row.dataset.servicio || ''),
            grupo_prefactura: limpiarTexto(row.dataset.grupoPrefactura || ''),
            tipo_asignacion: limpiarTexto(row.dataset.tipoAsignacion || ''),

            suscripcion_proveedor_facturacion_id: '',
            proveedor_facturacion_label: '',

            suscripcion_transportista_override_id: '',
            transportista_override_label: '',

            costo: '',
            tipo_documento: '',
            detalle_documento: '',
            detalle_impuesto: '',
            final: '',
            observacion: '',
        };
    }

    function actualizarContador() {
        if (contadorSeleccionadas) {
            contadorSeleccionadas.textContent = String(seleccionadas.size);
        }
    }

    function sincronizarCheckbox(id, checked) {
        const checkbox = checkboxesAsignaciones().find(function (item) {
            return String(item.value) === String(id);
        });

        if (checkbox) {
            checkbox.checked = checked;
        }
    }

    function optionLabel(select) {
        const option = select?.options[select.selectedIndex] || null;

        if (!option || !option.value) {
            return '';
        }

        return limpiarTexto(option.dataset.label || option.text || '');
    }




    function valorDocumentoDesdeDatos(tipo, detalleDocumento, detalleImpuesto, final) {
        return [
            limpiarTexto(tipo || ''),
            limpiarTexto(detalleDocumento || ''),
            limpiarTexto(detalleImpuesto || ''),
            limpiarTexto(final || ''),
        ].join('|');
    }



    function aplicarDatosProveedor(row) {
        const proveedorSelect = row.querySelector('[data-facturacion-masiva-proveedor]');
        const option = proveedorSelect?.options[proveedorSelect.selectedIndex] || null;

        if (!option || !option.value) {
            return;
        }

        const tipoDocumentoSelect = row.querySelector('[data-facturacion-masiva-tipo-documento]');
        const detalleDocumentoSelect = row.querySelector('[data-facturacion-masiva-detalle-documento]');
        const detalleImpuestoSelect = row.querySelector('[data-facturacion-masiva-detalle-impuesto]');
        const finalSelect = row.querySelector('[data-facturacion-masiva-final]');

        if (tipoDocumentoSelect && option.dataset.tipo) {
            tipoDocumentoSelect.value = limpiarTexto(option.dataset.tipo || '');
        }

        if (detalleDocumentoSelect && option.dataset.detalleDocumento) {
            detalleDocumentoSelect.value = limpiarTexto(option.dataset.detalleDocumento || '');
        }

        if (detalleImpuestoSelect && option.dataset.detalleImpuesto) {
            detalleImpuestoSelect.value = limpiarTexto(option.dataset.detalleImpuesto || '');
        }

        if (finalSelect && option.dataset.final) {
            finalSelect.value = limpiarTexto(option.dataset.final || '');
        }
    }




    function guardarValoresEditados() {
        if (!seleccionadasBody) {
            return;
        }

        seleccionadasBody.querySelectorAll('[data-facturacion-masiva-seleccionada]').forEach(function (row) {
            const id = row.dataset.asignacionId;

            if (!id || !seleccionadas.has(String(id))) {
                return;
            }

            const item = seleccionadas.get(String(id));

            const proveedorSelect = row.querySelector('[data-facturacion-masiva-proveedor]');
            const transportistaSelect = row.querySelector('[data-facturacion-masiva-transportista]');

            item.suscripcion_proveedor_facturacion_id = proveedorSelect?.value || '';
            item.proveedor_facturacion_label = optionLabel(proveedorSelect);

            item.suscripcion_transportista_override_id = transportistaSelect?.value || '';
            item.transportista_override_label = optionLabel(transportistaSelect);



            item.costo = row.querySelector('[data-facturacion-masiva-costo]')?.value || '';

            item.tipo_documento = limpiarTexto(row.querySelector('[data-facturacion-masiva-tipo-documento]')?.value || '');
            item.detalle_documento = limpiarTexto(row.querySelector('[data-facturacion-masiva-detalle-documento]')?.value || '');
            item.detalle_impuesto = limpiarTexto(row.querySelector('[data-facturacion-masiva-detalle-impuesto]')?.value || '');
            item.final = limpiarTexto(row.querySelector('[data-facturacion-masiva-final]')?.value || '');

            item.observacion = limpiarTexto(row.querySelector('[data-facturacion-masiva-observacion]')?.value || '');

            seleccionadas.set(String(id), item);
        });
    }







    function renderizarSeleccionadas() {
        if (!seleccionadasBody) {
            return;
        }

        seleccionadasBody.innerHTML = '';

        if (seleccionadas.size === 0) {
            seleccionadasBody.innerHTML = `
                <div data-facturacion-masiva-empty class="text-muted text-center border rounded p-3">
                    No hay asignaciones seleccionadas.
                </div>
            `;

            actualizarContador();
            return;
        }

        seleccionadas.forEach(function (item) {
            const row = document.createElement('div');

            row.className = 'border rounded p-3 bg-light';
            row.dataset.facturacionMasivaSeleccionada = '1';
            row.dataset.asignacionId = item.suscripcion_asignacion_id;

            row.innerHTML = `
                <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <div class="fw-semibold">${escaparHtml(item.codigo || 'Sin código')}</div>
                        <div class="small text-muted">${escaparHtml(item.label || '—')}</div>
                        <div class="small text-muted">
                            Tipo asignación: ${escaparHtml(item.tipo_asignacion || '—')}
                        </div>
                    </div>

                    <button
                        type="button"
                        class="btn btn-outline-danger btn-sm"
                        data-facturacion-masiva-quitar
                        data-asignacion-id="${escaparHtml(item.suscripcion_asignacion_id)}"
                    >
                        Quitar
                    </button>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small mb-1">
                            Proveedor facturador efectivo
                        </label>

                        <select
                            class="form-select form-select-sm"
                            data-facturacion-masiva-proveedor
                        >
                            ${proveedorTemplate?.innerHTML || '<option value="">Seleccionar proveedor facturador...</option>'}
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small mb-1">
                            Transportista efectivo opcional
                        </label>

                        <select
                            class="form-select form-select-sm"
                            data-facturacion-masiva-transportista
                        >
                            ${transportistaTemplate?.innerHTML || '<option value="">Mantener transportista original...</option>'}
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small mb-1">
                            Costo opcional
                        </label>

                        <input
                            type="number"
                            class="form-control form-control-sm text-end"
                            min="0"
                            value="${escaparHtml(item.costo || '')}"
                            placeholder="Opcional"
                            data-facturacion-masiva-costo
                        >

                        <div class="small text-muted mt-1">
                            Base: ${escaparHtml(item.costo_base || '—')}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small mb-1">
                            Tipo documento
                        </label>

                        <select
                            class="form-select form-select-sm"
                            data-facturacion-masiva-tipo-documento
                        >
                            <option value="">Seleccionar...</option>
                            <option value="FACTURA">FACTURA</option>
                            <option value="BOLETA">BOLETA</option>
                            <option value="DOCUMENTO">DOCUMENTO</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small mb-1">
                            Detalle documento
                        </label>

                        <select
                            class="form-select form-select-sm"
                            data-facturacion-masiva-detalle-documento
                        >
                            <option value="">Seleccionar...</option>
                            <option value="NETO">NETO</option>
                            <option value="BRUTO">BRUTO</option>
                            <option value="SIN REGISTRO">SIN REGISTRO</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small mb-1">
                            Detalle impuesto
                        </label>

                        <select
                            class="form-select form-select-sm"
                            data-facturacion-masiva-detalle-impuesto
                        >
                            <option value="">Seleccionar...</option>
                            <option value="IMPUESTO">IMPUESTO</option>
                            <option value="RETENCION">RETENCION</option>
                            <option value="SIN REGISTRO">SIN REGISTRO</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small mb-1">
                            Final
                        </label>

                        <select
                            class="form-select form-select-sm"
                            data-facturacion-masiva-final
                        >
                            <option value="">Seleccionar...</option>
                            <option value="TOTAL">TOTAL</option>
                            <option value="LIQUIDO A PAGAR">LIQUIDO A PAGAR</option>
                        </select>
                    </div>

                    <div class="col-md-9">
                        <label class="form-label small mb-1">
                            Observación
                        </label>

                        <input
                            type="text"
                            class="form-control form-control-sm"
                            value="${escaparHtml(item.observacion || '')}"
                            placeholder="Observación opcional"
                            data-facturacion-masiva-observacion
                        >
                    </div>
                </div>
            `;

            seleccionadasBody.appendChild(row);

            const proveedorSelect = row.querySelector('[data-facturacion-masiva-proveedor]');
            const transportistaSelect = row.querySelector('[data-facturacion-masiva-transportista]');
            const tipoDocumentoSelect = row.querySelector('[data-facturacion-masiva-tipo-documento]');
            const detalleDocumentoSelect = row.querySelector('[data-facturacion-masiva-detalle-documento]');
            const detalleImpuestoSelect = row.querySelector('[data-facturacion-masiva-detalle-impuesto]');
            const finalSelect = row.querySelector('[data-facturacion-masiva-final]');

            if (proveedorSelect) {
                proveedorSelect.value = item.suscripcion_proveedor_facturacion_id || '';
            }

            if (transportistaSelect) {
                transportistaSelect.value = item.suscripcion_transportista_override_id || '';
            }

            if (tipoDocumentoSelect) {
                tipoDocumentoSelect.value = item.tipo_documento || '';
            }

            if (detalleDocumentoSelect) {
                detalleDocumentoSelect.value = item.detalle_documento || '';
            }

            if (detalleImpuestoSelect) {
                detalleImpuestoSelect.value = item.detalle_impuesto || '';
            }

            if (finalSelect) {
                finalSelect.value = item.final || '';
            }
        });

        actualizarContador();
    }










    function agregarSeleccion(row) {
        const item = obtenerDatosFila(row);

        if (!item.suscripcion_asignacion_id) {
            return;
        }

        const tipoAsignacion = normalizarCodigo(item.tipo_asignacion);

        if (!['RUTA', 'VARIABLE', 'FIJO_MENSUAL', 'OPV'].includes(tipoAsignacion)) {
            return;
        }

        const id = String(item.suscripcion_asignacion_id);

        if (!seleccionadas.has(id)) {
            seleccionadas.set(id, item);
        }
    }

    function quitarSeleccion(id) {
        seleccionadas.delete(String(id));
        sincronizarCheckbox(id, false);
        ocultarError();
        renderizarSeleccionadas();
    }

    function filtrarAsignaciones() {
        const texto = normalizarCodigo(buscador?.value || '');

        filasAsignaciones().forEach(function (row) {
            const busqueda = normalizarCodigo(row.dataset.busqueda || '');
            const visible = texto === '' || busqueda.includes(texto);

            row.classList.toggle('d-none', !visible);
        });
    }

    function limpiarBusqueda() {
        if (buscador) {
            buscador.value = '';
        }

        filtrarAsignaciones();
        ocultarError();
    }

    function limpiarSeleccion() {
        seleccionadas.clear();

        checkboxesAsignaciones().forEach(function (checkbox) {
            checkbox.checked = false;
        });

        ocultarError();
        renderizarSeleccionadas();
    }

    function construirAjustesFacturacion() {
        guardarValoresEditados();

        const ajustes = [];

        seleccionadas.forEach(function (item) {
            ajustes.push({
                clave_control: [
                    'FACTURACION',
                    'ASIGNACION',
                    item.suscripcion_asignacion_id || '',
                ].join('|'),

                tipo_ajuste: 'FACTURACION',

                concepto_pago_variable_id: '',
                concepto_pago_variable_manual: '',
                concepto_pago_variable_label: '',

                suscripcion_asignacion_id: item.suscripcion_asignacion_id || '',
                suscripcion_proveedor_id: '',
                suscripcion_transportista_id: '',

                suscripcion_proveedor_facturacion_id: item.suscripcion_proveedor_facturacion_id || '',
                suscripcion_transportista_override_id: item.suscripcion_transportista_override_id || '',

                punto_1: item.punto_1 || '',
                origen_gasto: item.origen_gasto || 'Suscripciones',
                punto_2: item.punto_2 || '',
                codigo: item.codigo || '',
                servicio: item.servicio || '',
                grupo_prefactura: item.grupo_prefactura || '',

                costo: item.costo || '',
                q_calendario: '',
                q_inasistencia: '',
                cantidad: '',
                total: '',

                tipo_documento: item.tipo_documento || '',
                detalle_documento: item.detalle_documento || '',
                detalle_impuesto: item.detalle_impuesto || '',
                final: item.final || '',

                observacion: item.observacion || '',

                asignacion_label: item.label || '',
                proveedor_label: '',
                proveedor_facturacion_label: item.proveedor_facturacion_label || '',
                transportista_label: '',
                transportista_override_label: item.transportista_override_label || '',

                total_estimado: 0,
            });
        });

        return ajustes;
    }

    function validarAjustes(ajustes) {
        if (ajustes.length === 0) {
            return 'Selecciona al menos una asignación para registrar cambios de facturación.';
        }

        const sinProveedor = ajustes.find(function (ajuste) {
            return !ajuste.suscripcion_proveedor_facturacion_id;
        });

        if (sinProveedor) {
            return 'Todas las asignaciones seleccionadas deben tener un proveedor facturador efectivo.';
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

    function confirmarFacturaciones() {
        ocultarError();

        const ajustes = construirAjustesFacturacion();
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
                mostrarError(`Se omitieron ${resultado.duplicados} cambio(s) porque ya estaban agregados en el resumen.`);
            }

            return;
        }

        document.dispatchEvent(new CustomEvent('suscripciones:ajustes-masivos', {
            detail: {
                tipo: 'FACTURACION',
                ajustes: ajustes,
            },
        }));

        limpiarSeleccion();
        cerrarModal();
    }

    function registrarEventos() {
        if (buscador) {
            buscador.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    filtrarAsignaciones();
                }
            });

            buscador.addEventListener('input', filtrarAsignaciones);
        }

        if (buscarBtn) {
            buscarBtn.addEventListener('click', filtrarAsignaciones);
        }

        if (limpiarBusquedaBtn) {
            limpiarBusquedaBtn.addEventListener('click', limpiarBusqueda);
        }

        if (asignacionesBody) {
            asignacionesBody.addEventListener('change', function (event) {
                const checkbox = event.target.closest('[data-facturacion-masiva-checkbox]');

                if (!checkbox) {
                    return;
                }

                guardarValoresEditados();

                const row = checkbox.closest('[data-facturacion-masiva-asignacion]');

                if (!row) {
                    return;
                }

                if (checkbox.checked) {
                    agregarSeleccion(row);
                } else {
                    seleccionadas.delete(String(checkbox.value));
                }

                ocultarError();
                renderizarSeleccionadas();
            });
        }

        if (seleccionadasBody) {
            seleccionadasBody.addEventListener('click', function (event) {
                const button = event.target.closest('[data-facturacion-masiva-quitar]');

                if (!button) {
                    return;
                }

                guardarValoresEditados();
                quitarSeleccion(button.dataset.asignacionId);
            });

            seleccionadasBody.addEventListener('change', function (event) {
                const proveedorSelect = event.target.closest('[data-facturacion-masiva-proveedor]');

                if (proveedorSelect) {
                    const row = proveedorSelect.closest('[data-facturacion-masiva-seleccionada]');

                    if (row) {
                        aplicarDatosProveedor(row);
                    }
                }

                guardarValoresEditados();
            });

            seleccionadasBody.addEventListener('input', guardarValoresEditados);
        }

        if (limpiarBtn) {
            limpiarBtn.addEventListener('click', limpiarSeleccion);
        }

        if (confirmarBtn) {
            confirmarBtn.addEventListener('click', confirmarFacturaciones);
        }
    }

    registrarEventos();
    filtrarAsignaciones();
    renderizarSeleccionadas();
}