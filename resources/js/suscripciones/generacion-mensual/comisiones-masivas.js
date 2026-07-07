/**
 * resources/js/suscripciones/generacion-mensual/comisiones-masivas.js
 *
 * Maneja el modal de carga masiva de comisiones / pagos adicionales.
 * No guarda en BD directamente: sólo prepara objetos para comisiones[].
 *
 * Regla:
 * - Un mismo proveedor puede tener más de un pago adicional.
 * - Cada clic en "Agregar pago" crea una nueva fila editable.
 * - El código interno es COMISION y no se pide al usuario.
 */

import {
    escaparHtml,
    limpiarTexto,
} from './utils';

export function inicializarComisionesMasivas(dom, comisionesApi = {}) {
    const modal = document.getElementById('modal-comisiones-masivas');

    if (!modal) {
        return;
    }

    const transportistaTemplate = document.getElementById('comision-masiva-transportista-template');

    const buscadorInput = document.getElementById('comision-masiva-buscador');
    const buscarBtn = document.getElementById('btn-comision-masiva-buscar');
    const limpiarBusquedaBtn = document.getElementById('btn-comision-masiva-limpiar-busqueda');

    const proveedoresBody = document.getElementById('comision-masiva-proveedores-body');
    const seleccionadosBody = document.getElementById('comision-masiva-seleccionados-body');

    const limpiarSeleccionBtn = document.getElementById('btn-comision-masiva-limpiar');
    const confirmarBtn = document.getElementById('btn-confirmar-comisiones-masivas');

    const contadorSeleccionados = document.getElementById('comision-masiva-seleccionados-contador');
    const errorBox = document.getElementById('comision-masiva-error');

    let pagos = [];

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

    function optionLabel(select) {
        const option = selectedOption(select);

        if (!option || !option.value) {
            return '';
        }

        return limpiarTexto(option.dataset.label || option.text || '');
    }

    function entero(valor) {
        if (valor === null || valor === undefined || valor === '') {
            return null;
        }

        const numero = parseInt(valor, 10);

        return Number.isNaN(numero) ? null : numero;
    }

    function uid() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }

        return `${Date.now()}-${Math.random().toString(16).slice(2)}`;
    }

    function normalizarTexto(valor) {
        return limpiarTexto(valor || '')
            .toUpperCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^A-Z0-9]+/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function filasProveedor() {
        if (!proveedoresBody) {
            return [];
        }

        return Array.from(proveedoresBody.querySelectorAll('[data-comision-masiva-proveedor]'));
    }

    function actualizarContador() {
        if (contadorSeleccionados) {
            contadorSeleccionados.textContent = String(pagos.length);
        }
    }

    function aplicarBusqueda() {
        const termino = normalizarTexto(buscadorInput?.value || '');

        filasProveedor().forEach(function (row) {
            const textoBusqueda = normalizarTexto(row.dataset.busqueda || '');
            const visible = !termino || textoBusqueda.includes(termino);

            row.classList.toggle('d-none', !visible);
        });
    }

    function limpiarBusqueda() {
        if (buscadorInput) {
            buscadorInput.value = '';
        }

        aplicarBusqueda();
        ocultarError();

        if (buscadorInput) {
            buscadorInput.focus();
        }
    }

    function buscarTransportistaPorNombre(nombreProveedor) {
        if (!transportistaTemplate) {
            return '';
        }

        const nombreNormalizado = normalizarTexto(nombreProveedor);

        if (!nombreNormalizado) {
            return '';
        }

        const opciones = Array.from(transportistaTemplate.querySelectorAll('option'));

        const exacto = opciones.find(function (option) {
            return option.value && normalizarTexto(option.dataset.label || option.text || '') === nombreNormalizado;
        });

        if (exacto) {
            return exacto.value;
        }

        const parcial = opciones.find(function (option) {
            if (!option.value) {
                return false;
            }

            const texto = normalizarTexto(option.dataset.label || option.text || '');

            return texto.includes(nombreNormalizado) || nombreNormalizado.includes(texto);
        });

        return parcial?.value || '';
    }

    function datosPagoDesdeFila(row) {
        const razonSocial = limpiarTexto(row.dataset.razonSocial || '');
        const transportistaIdSugerido = buscarTransportistaPorNombre(razonSocial);

        return {
            uid: uid(),

            codigo: 'COMISION',

            suscripcion_proveedor_id: row.dataset.proveedorId || '',
            proveedor_label: limpiarTexto(row.dataset.label || ''),
            razon_social: razonSocial,
            rut: limpiarTexto(row.dataset.rut || ''),

            suscripcion_transportista_id: transportistaIdSugerido,
            transportista_label: '',

            punto_1: '',
            origen_gasto: 'Suscripciones',
            punto_2: '',
            servicio: 'Reparto fin de semana',
            costo: '',
            observacion: '',
        };
    }

    function guardarValoresEditados() {
        if (!seleccionadosBody) {
            return;
        }

        seleccionadosBody.querySelectorAll('[data-comision-masiva-pago]').forEach(function (row) {
            const pagoUid = row.dataset.uid;

            if (!pagoUid) {
                return;
            }

            const index = pagos.findIndex(function (pago) {
                return pago.uid === pagoUid;
            });

            if (index < 0) {
                return;
            }

            const item = pagos[index];

            const transportistaSelect = row.querySelector('[data-comision-masiva-transportista]');

            item.suscripcion_transportista_id = transportistaSelect?.value || '';
            item.transportista_label = optionLabel(transportistaSelect);

            item.punto_1 = limpiarTexto(row.querySelector('[data-comision-masiva-punto-1]')?.value || '');
            item.origen_gasto = 'Suscripciones';
            item.punto_2 = limpiarTexto(row.querySelector('[data-comision-masiva-punto-2]')?.value || '');
            item.servicio = limpiarTexto(row.querySelector('[data-comision-masiva-servicio]')?.value || 'Reparto fin de semana') || 'Reparto fin de semana';
            item.costo = row.querySelector('[data-comision-masiva-costo]')?.value || '';
            item.observacion = limpiarTexto(row.querySelector('[data-comision-masiva-observacion]')?.value || '');

            pagos[index] = item;
        });
    }

    function agregarPago(row) {
        guardarValoresEditados();

        pagos.push(datosPagoDesdeFila(row));

        ocultarError();
        renderizarPagos();
    }

    function quitarPago(pagoUid) {
        guardarValoresEditados();

        pagos = pagos.filter(function (pago) {
            return pago.uid !== pagoUid;
        });

        ocultarError();
        renderizarPagos();
    }

    function limpiarPagos() {
        pagos = [];

        ocultarError();
        renderizarPagos();
    }

    function renderizarPagos() {
        if (!seleccionadosBody) {
            return;
        }

        seleccionadosBody.innerHTML = '';

        if (pagos.length === 0) {
            seleccionadosBody.innerHTML = `
                <tr data-comision-masiva-empty>
                    <td colspan="8" class="text-muted text-center">
                        No hay pagos agregados.
                    </td>
                </tr>
            `;

            actualizarContador();
            return;
        }

        pagos.forEach(function (item) {
            const row = document.createElement('tr');

            row.dataset.comisionMasivaPago = '1';
            row.dataset.uid = item.uid;

            row.innerHTML = `
                <td>
                    <div class="fw-semibold">
                        ${escaparHtml(item.razon_social || item.proveedor_label || 'Proveedor')}
                    </div>

                    <div class="small text-muted">
                        ${escaparHtml(item.rut || '')}
                    </div>
                </td>

                <td>
                    <select
                        class="form-select form-select-sm"
                        data-comision-masiva-transportista
                    >
                        ${transportistaTemplate?.innerHTML || '<option value="">Seleccionar transportista...</option>'}
                    </select>
                </td>

                <td>
                    <input
                        type="text"
                        class="form-control form-control-sm"
                        value="${escaparHtml(item.punto_1 || '')}"
                        placeholder="Ej: LA DEHESA"
                        data-comision-masiva-punto-1
                    >
                </td>

                <td>
                    <input
                        type="text"
                        class="form-control form-control-sm"
                        value="${escaparHtml(item.punto_2 || '')}"
                        placeholder="Ej: LA DEHESA"
                        data-comision-masiva-punto-2
                    >
                </td>

                <td>
                    <input
                        type="text"
                        class="form-control form-control-sm"
                        value="${escaparHtml(item.servicio || 'Reparto fin de semana')}"
                        placeholder="Reparto fin de semana"
                        data-comision-masiva-servicio
                    >
                </td>

                <td>
                    <input
                        type="number"
                        class="form-control form-control-sm text-end"
                        min="0"
                        value="${escaparHtml(item.costo || '')}"
                        placeholder="0"
                        data-comision-masiva-costo
                    >
                </td>

                <td>
                    <input
                        type="text"
                        class="form-control form-control-sm"
                        value="${escaparHtml(item.observacion || '')}"
                        placeholder="Observación opcional"
                        data-comision-masiva-observacion
                    >
                </td>

                <td class="text-center">
                    <button
                        type="button"
                        class="btn btn-outline-danger btn-sm"
                        data-comision-masiva-quitar
                        data-uid="${escaparHtml(item.uid)}"
                    >
                        Quitar
                    </button>
                </td>
            `;

            seleccionadosBody.appendChild(row);

            const transportistaSelect = row.querySelector('[data-comision-masiva-transportista]');

            if (transportistaSelect) {
                transportistaSelect.value = item.suscripcion_transportista_id || '';

                item.transportista_label = optionLabel(transportistaSelect);
            }
        });

        actualizarContador();
    }

    function construirComisiones() {
        guardarValoresEditados();

        return pagos.map(function (item) {
            const costo = entero(item.costo);

            return {
                clave_control: [
                    'COMISION',
                    item.suscripcion_proveedor_id || '',
                    item.suscripcion_transportista_id || '',
                    item.punto_1 || '',
                    item.punto_2 || '',
                    item.servicio || '',
                    costo === null ? '' : costo,
                    item.observacion || '',
                    item.uid || '',
                ].join('|'),

                codigo: 'COMISION',

                suscripcion_proveedor_id: item.suscripcion_proveedor_id || '',
                suscripcion_transportista_id: item.suscripcion_transportista_id || '',

                proveedor_label: item.proveedor_label || '',
                transportista_label: item.transportista_label || '',

                punto_1: item.punto_1 || '',
                origen_gasto: item.origen_gasto || 'Suscripciones',
                punto_2: item.punto_2 || '',
                servicio: item.servicio || 'Reparto fin de semana',

                costo: costo === null ? '' : costo,
                observacion: item.observacion || '',
            };
        });
    }

    function validarComisiones(comisiones) {
        if (comisiones.length === 0) {
            return 'Agrega al menos un pago adicional.';
        }

        const sinTransportista = comisiones.find(function (comision) {
            return !comision.suscripcion_transportista_id;
        });

        if (sinTransportista) {
            return 'Todos los pagos adicionales deben tener transportista.';
        }

        const sinMonto = comisiones.find(function (comision) {
            return comision.costo === '' || comision.costo === null || comision.costo === undefined;
        });

        if (sinMonto) {
            return 'Todos los pagos adicionales deben tener monto.';
        }

        const montoInvalido = comisiones.find(function (comision) {
            const costo = entero(comision.costo);

            return costo === null || costo < 0;
        });

        if (montoInvalido) {
            return 'Todos los pagos adicionales deben tener un monto válido.';
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

    function confirmarComisiones() {
        ocultarError();

        const comisiones = construirComisiones();
        const error = validarComisiones(comisiones);

        if (error) {
            mostrarError(error);
            return;
        }

        if (typeof comisionesApi.agregarComisionesMasivas === 'function') {
            const resultado = comisionesApi.agregarComisionesMasivas(comisiones);

            if ((resultado?.agregados ?? comisiones.length) > 0) {
                limpiarPagos();
                cerrarModal();
                return;
            }

            if ((resultado?.duplicados ?? 0) > 0) {
                mostrarError(`Se omitieron ${resultado.duplicados} pago(s) adicional(es) porque ya estaban agregados en el resumen.`);
            }

            return;
        }

        document.dispatchEvent(new CustomEvent('suscripciones:comisiones-masivas', {
            detail: {
                comisiones,
            },
        }));

        limpiarPagos();
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
            proveedoresBody.addEventListener('click', function (event) {
                const button = event.target.closest('[data-comision-masiva-agregar-pago]');

                if (!button) {
                    return;
                }

                const row = button.closest('[data-comision-masiva-proveedor]');

                if (!row) {
                    return;
                }

                agregarPago(row);
            });
        }

        if (seleccionadosBody) {
            seleccionadosBody.addEventListener('click', function (event) {
                const button = event.target.closest('[data-comision-masiva-quitar]');

                if (!button) {
                    return;
                }

                quitarPago(button.dataset.uid);
            });

            seleccionadosBody.addEventListener('input', function () {
                guardarValoresEditados();
                ocultarError();
            });

            seleccionadosBody.addEventListener('change', function () {
                guardarValoresEditados();
                ocultarError();
            });
        }

        if (limpiarSeleccionBtn) {
            limpiarSeleccionBtn.addEventListener('click', limpiarPagos);
        }

        if (confirmarBtn) {
            confirmarBtn.addEventListener('click', confirmarComisiones);
        }
    }

    registrarEventos();
    aplicarBusqueda();
    renderizarPagos();
}