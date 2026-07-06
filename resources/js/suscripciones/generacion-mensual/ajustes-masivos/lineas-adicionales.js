/**
 * resources/js/suscripciones/generacion-mensual/ajustes-masivos/lineas-adicionales.js
 *
 * Maneja el modal de carga masiva de nuevas rutas / líneas adicionales.
 * No guarda en BD directamente: sólo prepara novedades y las envía
 * al flujo actual de ajustesMensuales[].
 */

import {
    escaparHtml,
    limpiarTexto,
    normalizarCodigo,
} from '../utils';

export function inicializarLineasAdicionalesMasivas(dom, ajustesMensualesApi = {}) {
    const modal = document.getElementById('modal-ajustes-masivos-linea-adicional');

    if (!modal) {
        return;
    }

    const proveedorTemplate = document.getElementById('linea-adicional-masiva-proveedor-template');
    const transportistaTemplate = document.getElementById('linea-adicional-masiva-transportista-template');

    const agregarBtn = document.getElementById('btn-linea-adicional-masiva-agregar');
    const limpiarBtn = document.getElementById('btn-linea-adicional-masiva-limpiar');
    const confirmarBtn = document.getElementById('btn-confirmar-lineas-adicionales-masivas');

    const lineasBody = document.getElementById('linea-adicional-masiva-lineas-body');
    const contadorLineas = document.getElementById('linea-adicional-masiva-contador');
    const errorBox = document.getElementById('linea-adicional-masiva-error');

    let secuencia = 0;
    const lineas = new Map();

    function nuevoUid() {
        secuencia += 1;

        return [
            'LINEA',
            Date.now(),
            secuencia,
        ].join('_');
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

    function optionLabel(select) {
        const option = select?.options[select.selectedIndex] || null;

        if (!option || !option.value) {
            return '';
        }

        return limpiarTexto(option.dataset.label || option.text || '');
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

    function calcularTotalLinea(item) {
        const costo = entero(item.costo);
        const cantidad = entero(item.cantidad);
        const totalManual = entero(item.total);

        if (totalManual !== null && totalManual >= 0) {
            return totalManual;
        }

        if (costo === null || cantidad === null) {
            return 0;
        }

        return costo * cantidad;
    }

    function actualizarContador() {
        if (contadorLineas) {
            contadorLineas.textContent = String(lineas.size);
        }
    }

    function aplicarDatosProveedor(row) {
        const proveedorSelect = row.querySelector('[data-linea-adicional-masiva-proveedor]');
        const option = selectedOption(proveedorSelect);

        if (!option || !option.value) {
            return;
        }

        const tipoDocumentoSelect = row.querySelector('[data-linea-adicional-masiva-tipo-documento]');
        const detalleDocumentoSelect = row.querySelector('[data-linea-adicional-masiva-detalle-documento]');
        const detalleImpuestoSelect = row.querySelector('[data-linea-adicional-masiva-detalle-impuesto]');
        const finalSelect = row.querySelector('[data-linea-adicional-masiva-final]');

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

    function actualizarTotalVisual(row) {
        const totalInput = row.querySelector('[data-linea-adicional-masiva-total]');
        const totalPreview = row.querySelector('[data-linea-adicional-masiva-total-preview]');

        if (!totalInput && !totalPreview) {
            return;
        }

        const costo = row.querySelector('[data-linea-adicional-masiva-costo]')?.value || '';
        const cantidad = row.querySelector('[data-linea-adicional-masiva-cantidad]')?.value || '';
        const totalManual = totalInput?.value || '';

        const itemTemporal = {
            costo,
            cantidad,
            total: totalManual,
        };

        const totalCalculado = calcularTotalLinea(itemTemporal);

        if (totalPreview) {
            totalPreview.textContent = '$' + new Intl.NumberFormat('es-CL').format(totalCalculado);
        }
    }

    function guardarValoresEditados() {
        if (!lineasBody) {
            return;
        }

        lineasBody.querySelectorAll('[data-linea-adicional-masiva-linea]').forEach(function (row) {
            const uid = row.dataset.uid;

            if (!uid || !lineas.has(uid)) {
                return;
            }

            const item = lineas.get(uid);

            const proveedorSelect = row.querySelector('[data-linea-adicional-masiva-proveedor]');
            const transportistaSelect = row.querySelector('[data-linea-adicional-masiva-transportista]');

            item.suscripcion_proveedor_id = proveedorSelect?.value || '';
            item.proveedor_label = optionLabel(proveedorSelect);

            item.suscripcion_transportista_id = transportistaSelect?.value || '';
            item.transportista_label = optionLabel(transportistaSelect);

            item.punto_1 = limpiarTexto(row.querySelector('[data-linea-adicional-masiva-punto-1]')?.value || '');
            item.origen_gasto = limpiarTexto(row.querySelector('[data-linea-adicional-masiva-origen-gasto]')?.value || 'Suscripciones');
            item.punto_2 = limpiarTexto(row.querySelector('[data-linea-adicional-masiva-punto-2]')?.value || '');

            item.codigo = limpiarTexto(row.querySelector('[data-linea-adicional-masiva-codigo]')?.value || '');
            item.servicio = limpiarTexto(row.querySelector('[data-linea-adicional-masiva-servicio]')?.value || '');
            item.grupo_prefactura = limpiarTexto(row.querySelector('[data-linea-adicional-masiva-grupo-prefactura]')?.value || '');

            item.costo = row.querySelector('[data-linea-adicional-masiva-costo]')?.value || '';
            item.cantidad = row.querySelector('[data-linea-adicional-masiva-cantidad]')?.value || '';
            item.total = row.querySelector('[data-linea-adicional-masiva-total]')?.value || '';

            item.tipo_documento = limpiarTexto(row.querySelector('[data-linea-adicional-masiva-tipo-documento]')?.value || '');
            item.detalle_documento = limpiarTexto(row.querySelector('[data-linea-adicional-masiva-detalle-documento]')?.value || '');
            item.detalle_impuesto = limpiarTexto(row.querySelector('[data-linea-adicional-masiva-detalle-impuesto]')?.value || '');
            item.final = limpiarTexto(row.querySelector('[data-linea-adicional-masiva-final]')?.value || '');

            item.observacion = limpiarTexto(row.querySelector('[data-linea-adicional-masiva-observacion]')?.value || '');

            lineas.set(uid, item);

            actualizarTotalVisual(row);
        });
    }

    function crearLineaVacia() {
        const uid = nuevoUid();

        lineas.set(uid, {
            uid,

            suscripcion_proveedor_id: '',
            proveedor_label: '',

            suscripcion_transportista_id: '',
            transportista_label: '',

            punto_1: '',
            origen_gasto: 'Suscripciones',
            punto_2: '',

            codigo: '',
            servicio: 'Nueva ruta',
            grupo_prefactura: '',

            costo: '',
            cantidad: '1',
            total: '',

            tipo_documento: '',
            detalle_documento: '',
            detalle_impuesto: '',
            final: '',

            observacion: '',
        });

        renderizarLineas();

        const row = lineasBody?.querySelector(`[data-uid="${CSS.escape(uid)}"]`);
        const proveedorSelect = row?.querySelector('[data-linea-adicional-masiva-proveedor]');

        if (proveedorSelect) {
            proveedorSelect.focus();
        }
    }

    function quitarLinea(uid) {
        lineas.delete(uid);
        ocultarError();
        renderizarLineas();
    }

    function limpiarLineas() {
        lineas.clear();
        ocultarError();
        renderizarLineas();
    }

    function renderizarLineas() {
        if (!lineasBody) {
            return;
        }

        lineasBody.innerHTML = '';

        if (lineas.size === 0) {
            lineasBody.innerHTML = `
                <div data-linea-adicional-masiva-empty class="text-muted text-center border rounded p-3">
                    No hay nuevas rutas preparadas.
                </div>
            `;

            actualizarContador();
            return;
        }

        lineas.forEach(function (item, uid) {
            const row = document.createElement('div');

            row.className = 'border rounded p-3 bg-light';
            row.dataset.lineaAdicionalMasivaLinea = '1';
            row.dataset.uid = uid;

            row.innerHTML = `
                <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <div class="fw-semibold">
                            Nueva ruta
                        </div>

                        <div class="small text-muted">
                            Se registrará como LINEA_ADICIONAL del periodo.
                        </div>
                    </div>

                    <button
                        type="button"
                        class="btn btn-outline-danger btn-sm"
                        data-linea-adicional-masiva-quitar
                        data-uid="${escaparHtml(uid)}"
                    >
                        Quitar
                    </button>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small mb-1">
                            Proveedor
                        </label>

                        <select
                            class="form-select form-select-sm"
                            data-linea-adicional-masiva-proveedor
                        >
                            ${proveedorTemplate?.innerHTML || '<option value="">Seleccionar proveedor...</option>'}
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small mb-1">
                            Transportista opcional
                        </label>

                        <select
                            class="form-select form-select-sm"
                            data-linea-adicional-masiva-transportista
                        >
                            ${transportistaTemplate?.innerHTML || '<option value="">Sin transportista / no aplica</option>'}
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small mb-1">
                            Punto 1
                        </label>

                        <input
                            type="text"
                            class="form-control form-control-sm"
                            value="${escaparHtml(item.punto_1 || '')}"
                            placeholder="Origen / sector / punto inicial"
                            data-linea-adicional-masiva-punto-1
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small mb-1">
                            Origen gasto
                        </label>

                        <input
                            type="text"
                            class="form-control form-control-sm"
                            value="${escaparHtml(item.origen_gasto || 'Suscripciones')}"
                            placeholder="Suscripciones"
                            data-linea-adicional-masiva-origen-gasto
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small mb-1">
                            Punto 2
                        </label>

                        <input
                            type="text"
                            class="form-control form-control-sm"
                            value="${escaparHtml(item.punto_2 || '')}"
                            placeholder="Destino / sector / punto final"
                            data-linea-adicional-masiva-punto-2
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small mb-1">
                            Código
                        </label>

                        <input
                            type="text"
                            class="form-control form-control-sm"
                            value="${escaparHtml(item.codigo || '')}"
                            placeholder="Ej: NR-001"
                            data-linea-adicional-masiva-codigo
                        >
                    </div>

                    <div class="col-md-5">
                        <label class="form-label small mb-1">
                            Servicio
                        </label>

                        <input
                            type="text"
                            class="form-control form-control-sm"
                            value="${escaparHtml(item.servicio || 'Nueva ruta')}"
                            placeholder="Ej: Nueva ruta suscripciones"
                            data-linea-adicional-masiva-servicio
                        >
                    </div>


                    <div class="col-md-3">
                        <label class="form-label small mb-1">
                            Costo
                        </label>

                        <input
                            type="number"
                            class="form-control form-control-sm text-end"
                            min="0"
                            value="${escaparHtml(item.costo || '')}"
                            placeholder="0"
                            data-linea-adicional-masiva-costo
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small mb-1">
                            Cantidad
                        </label>

                        <input
                            type="number"
                            class="form-control form-control-sm text-end"
                            min="1"
                            value="${escaparHtml(item.cantidad || '1')}"
                            placeholder="1"
                            data-linea-adicional-masiva-cantidad
                        >
                    </div>


                    <div class="col-md-3">
                        <label class="form-label small mb-1">
                            Total estimado
                        </label>

                        <div
                            class="form-control form-control-sm bg-white text-end"
                            data-linea-adicional-masiva-total-preview
                        >
                            $0
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small mb-1">
                            Tipo documento
                        </label>

                        <select
                            class="form-select form-select-sm"
                            data-linea-adicional-masiva-tipo-documento
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
                            data-linea-adicional-masiva-detalle-documento
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
                            data-linea-adicional-masiva-detalle-impuesto
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
                            data-linea-adicional-masiva-final
                        >
                            <option value="">Seleccionar...</option>
                            <option value="TOTAL">TOTAL</option>
                            <option value="LIQUIDO A PAGAR">LIQUIDO A PAGAR</option>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label small mb-1">
                            Observación
                        </label>

                        <input
                            type="text"
                            class="form-control form-control-sm"
                            value="${escaparHtml(item.observacion || '')}"
                            placeholder="Observación opcional"
                            data-linea-adicional-masiva-observacion
                        >
                    </div>
                </div>
            `;

            lineasBody.appendChild(row);

            const proveedorSelect = row.querySelector('[data-linea-adicional-masiva-proveedor]');
            const transportistaSelect = row.querySelector('[data-linea-adicional-masiva-transportista]');
            const tipoDocumentoSelect = row.querySelector('[data-linea-adicional-masiva-tipo-documento]');
            const detalleDocumentoSelect = row.querySelector('[data-linea-adicional-masiva-detalle-documento]');
            const detalleImpuestoSelect = row.querySelector('[data-linea-adicional-masiva-detalle-impuesto]');
            const finalSelect = row.querySelector('[data-linea-adicional-masiva-final]');

            if (proveedorSelect) {
                proveedorSelect.value = item.suscripcion_proveedor_id || '';
            }

            if (transportistaSelect) {
                transportistaSelect.value = item.suscripcion_transportista_id || '';
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

            actualizarTotalVisual(row);
        });

        actualizarContador();
    }

    function construirClaveControl(item) {
        return [
            'LINEA_ADICIONAL',
            'LINEA',
            item.suscripcion_proveedor_id || '',
            item.suscripcion_transportista_id || '',
            normalizarCodigo(item.codigo || ''),
            normalizarCodigo(item.punto_1 || ''),
            normalizarCodigo(item.punto_2 || ''),
            normalizarCodigo(item.origen_gasto || ''),
        ].join('|');
    }

    function construirAjustesLineasAdicionales() {
        guardarValoresEditados();

        const ajustes = [];

        lineas.forEach(function (item) {
            const totalEstimado = calcularTotalLinea(item);
            const totalParaGuardar = item.total !== ''
                ? item.total
                : String(totalEstimado);

            ajustes.push({
                clave_control: construirClaveControl(item),

                tipo_ajuste: 'LINEA_ADICIONAL',

                concepto_pago_variable_id: '',
                concepto_pago_variable_manual: '',
                concepto_pago_variable_label: '',

                suscripcion_asignacion_id: '',
                suscripcion_proveedor_id: item.suscripcion_proveedor_id || '',
                suscripcion_transportista_id: item.suscripcion_transportista_id || '',

                suscripcion_proveedor_facturacion_id: '',
                suscripcion_transportista_override_id: '',

                punto_1: item.punto_1 || '',
                origen_gasto: item.origen_gasto || 'Suscripciones',
                punto_2: item.punto_2 || '',
                codigo: item.codigo || '',
                servicio: item.servicio || 'Nueva ruta',
                grupo_prefactura: item.grupo_prefactura || '',

                costo: item.costo || '',
                q_calendario: '',
                q_inasistencia: '',
                cantidad: item.cantidad || '',
                total: totalParaGuardar,

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

                total_estimado: totalEstimado,
            });
        });

        return ajustes;
    }

    function validarAjustes(ajustes) {
        if (ajustes.length === 0) {
            return 'Agrega al menos una nueva ruta antes de confirmar.';
        }

        const sinProveedor = ajustes.find(function (ajuste) {
            return !ajuste.suscripcion_proveedor_id;
        });

        if (sinProveedor) {
            return 'Todas las nuevas rutas deben tener proveedor.';
        }

        const sinCodigo = ajustes.find(function (ajuste) {
            return !limpiarTexto(ajuste.codigo || '');
        });

        if (sinCodigo) {
            return 'Todas las nuevas rutas deben tener código.';
        }

        const sinServicio = ajustes.find(function (ajuste) {
            return !limpiarTexto(ajuste.servicio || '');
        });

        if (sinServicio) {
            return 'Todas las nuevas rutas deben tener servicio.';
        }

        const costoInvalido = ajustes.find(function (ajuste) {
            const costo = entero(ajuste.costo);

            return costo === null || costo < 0;
        });

        if (costoInvalido) {
            return 'Todas las nuevas rutas deben tener un costo válido.';
        }

        const cantidadInvalida = ajustes.find(function (ajuste) {
            const cantidad = entero(ajuste.cantidad);

            return cantidad === null || cantidad <= 0;
        });

        if (cantidadInvalida) {
            return 'Todas las nuevas rutas deben tener una cantidad mayor a 0.';
        }

        const totalInvalido = ajustes.find(function (ajuste) {
            const total = entero(ajuste.total);

            return total === null || total < 0;
        });

        if (totalInvalido) {
            return 'Todas las nuevas rutas deben tener un total válido.';
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

    function confirmarLineasAdicionales() {
        ocultarError();

        const ajustes = construirAjustesLineasAdicionales();
        const error = validarAjustes(ajustes);

        if (error) {
            mostrarError(error);
            return;
        }

        if (typeof ajustesMensualesApi.agregarAjustesMasivos === 'function') {
            const resultado = ajustesMensualesApi.agregarAjustesMasivos(ajustes);

            if ((resultado?.agregados ?? ajustes.length) > 0) {
                limpiarLineas();
                cerrarModal();
                return;
            }

            if ((resultado?.duplicados ?? 0) > 0) {
                mostrarError(`Se omitieron ${resultado.duplicados} nueva(s) ruta(s) porque ya estaban agregadas en el resumen.`);
            }

            return;
        }

        document.dispatchEvent(new CustomEvent('suscripciones:ajustes-masivos', {
            detail: {
                tipo: 'LINEA_ADICIONAL',
                ajustes: ajustes,
            },
        }));

        limpiarLineas();
        cerrarModal();
    }

    function registrarEventos() {
        if (agregarBtn) {
            agregarBtn.addEventListener('click', function () {
                guardarValoresEditados();
                ocultarError();
                crearLineaVacia();
            });
        }

        if (limpiarBtn) {
            limpiarBtn.addEventListener('click', limpiarLineas);
        }

        if (confirmarBtn) {
            confirmarBtn.addEventListener('click', confirmarLineasAdicionales);
        }

        if (lineasBody) {
            lineasBody.addEventListener('click', function (event) {
                const button = event.target.closest('[data-linea-adicional-masiva-quitar]');

                if (!button) {
                    return;
                }

                guardarValoresEditados();
                quitarLinea(button.dataset.uid);
            });

            lineasBody.addEventListener('change', function (event) {
                const proveedorSelect = event.target.closest('[data-linea-adicional-masiva-proveedor]');

                if (proveedorSelect) {
                    const row = proveedorSelect.closest('[data-linea-adicional-masiva-linea]');

                    if (row) {
                        aplicarDatosProveedor(row);
                    }
                }

                guardarValoresEditados();
                ocultarError();
            });

            lineasBody.addEventListener('input', function (event) {
                const row = event.target.closest('[data-linea-adicional-masiva-linea]');

                if (row) {
                    actualizarTotalVisual(row);
                }

                guardarValoresEditados();
                ocultarError();
            });
        }
    }

    registrarEventos();
    renderizarLineas();
}