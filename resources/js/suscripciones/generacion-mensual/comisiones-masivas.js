import {
    escaparHtml,
    formatearCLP,
    limpiarTexto,
} from './utils';

export function inicializarComisionesMasivas(dom, comisionesApi = {}) {
    const modal = document.getElementById('modal-comisiones-masivas');

    if (!modal) {
        return;
    }

    const transportistaTemplate = document.getElementById(
        'comision-masiva-transportista-template'
    );

    const montoInput = document.getElementById('comision-masiva-monto');

    const observacionGeneralInput = document.getElementById(
        'comision-masiva-observacion-general'
    );

    const buscadorInput = document.getElementById(
        'comision-masiva-buscador'
    );

    const buscarBtn = document.getElementById(
        'btn-comision-masiva-buscar'
    );

    const limpiarBusquedaBtn = document.getElementById(
        'btn-comision-masiva-limpiar-busqueda'
    );

    const proveedoresBody = document.getElementById(
        'comision-masiva-proveedores-body'
    );

    const seleccionadosBody = document.getElementById(
        'comision-masiva-seleccionados-body'
    );

    const limpiarSeleccionBtn = document.getElementById(
        'btn-comision-masiva-limpiar'
    );

    const confirmarBtn = document.getElementById(
        'btn-confirmar-comisiones-masivas'
    );

    const contadorSeleccionados = document.getElementById(
        'comision-masiva-seleccionados-contador'
    );

    const resumenCantidad = document.getElementById(
        'comision-masiva-resumen-cantidad'
    );

    const montoPreview = document.getElementById(
        'comision-masiva-monto-preview'
    );

    const totalPreview = document.getElementById(
        'comision-masiva-total-preview'
    );

    const errorBox = document.getElementById(
        'comision-masiva-error'
    );

    /*
     * Estado temporal del modal.
     *
     * Se usa un arreglo porque un mismo proveedor puede tener
     * más de un pago adicional.
     */
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

        return limpiarTexto(
            option.dataset.label || option.text || ''
        );
    }

    function entero(valor) {
        if (
            valor === null
            || valor === undefined
            || valor === ''
        ) {
            return null;
        }

        const numero = parseInt(valor, 10);

        return Number.isNaN(numero) ? null : numero;
    }

    function generarUid() {
        if (
            window.crypto
            && typeof window.crypto.randomUUID === 'function'
        ) {
            return window.crypto.randomUUID();
        }

        return [
            Date.now(),
            Math.random().toString(16).slice(2),
        ].join('-');
    }

    function montoComun() {
        const monto = entero(montoInput?.value || '');

        return monto === null ? 0 : monto;
    }

    function observacionGeneral() {
        return limpiarTexto(
            observacionGeneralInput?.value || ''
        );
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

        return Array.from(
            proveedoresBody.querySelectorAll(
                '[data-comision-masiva-proveedor]'
            )
        );
    }

    function buscarTransportistaPorNombre(nombreProveedor) {
        if (!transportistaTemplate) {
            return '';
        }

        const nombreNormalizado = normalizarTexto(
            nombreProveedor
        );

        if (!nombreNormalizado) {
            return '';
        }

        const opciones = Array.from(
            transportistaTemplate.querySelectorAll('option')
        );

        const coincidenciaExacta = opciones.find(
            function (option) {
                if (!option.value) {
                    return false;
                }

                const nombreTransportista = normalizarTexto(
                    option.dataset.label
                    || option.text
                    || ''
                );

                return nombreTransportista === nombreNormalizado;
            }
        );

        if (coincidenciaExacta) {
            return coincidenciaExacta.value;
        }

        const coincidenciaParcial = opciones.find(
            function (option) {
                if (!option.value) {
                    return false;
                }

                const nombreTransportista = normalizarTexto(
                    option.dataset.label
                    || option.text
                    || ''
                );

                return nombreTransportista.includes(
                    nombreNormalizado
                ) || nombreNormalizado.includes(
                    nombreTransportista
                );
            }
        );

        return coincidenciaParcial?.value || '';
    }

    function datosPagoDesdeFila(row) {
        const razonSocial = limpiarTexto(
            row.dataset.razonSocial || ''
        );

        return {
            uid: generarUid(),

            suscripcion_proveedor_id:
                row.dataset.proveedorId || '',

            proveedor_label:
                limpiarTexto(row.dataset.label || ''),

            razon_social: razonSocial,

            rut: limpiarTexto(
                row.dataset.rut || ''
            ),


            suscripcion_transportista_id:
                buscarTransportistaPorNombre(razonSocial),

            transportista_label: '',

            /*
            * La cantidad se completa después,
            * en la sección Revisar pagos preparados.
            */
            cantidad: '',

        };
    }




    function actualizarResumen() {
        const pagosPreparados = pagos.length;
        const tarifa = montoComun();

        const total = pagos.reduce(function (acumulado, pago) {
            const cantidad = entero(pago.cantidad);

            if (cantidad === null || cantidad <= 0) {
                return acumulado;
            }

            return acumulado + (tarifa * cantidad);
        }, 0);

        if (contadorSeleccionados) {
            contadorSeleccionados.textContent =
                String(pagosPreparados);
        }

        if (resumenCantidad) {
            resumenCantidad.textContent =
                String(pagosPreparados);
        }

        if (montoPreview) {
            montoPreview.textContent =
                formatearCLP(tarifa);
        }

        if (totalPreview) {
            totalPreview.textContent =
                formatearCLP(total);
        }
    }



    function actualizarTotalesFilas() {
        if (!seleccionadosBody) {
            actualizarResumen();
            return;
        }

        const tarifa = montoComun();

        seleccionadosBody
            .querySelectorAll('[data-comision-masiva-pago]')
            .forEach(function (row) {
                const pagoUid = row.dataset.uid;

                const pago = pagos.find(function (item) {
                    return item.uid === pagoUid;
                });

                if (!pago) {
                    return;
                }

                const cantidad = entero(pago.cantidad);

                const total = cantidad !== null && cantidad > 0
                    ? tarifa * cantidad
                    : 0;

                const totalFila = row.querySelector(
                    '[data-comision-masiva-total-fila]'
                );

                if (totalFila) {
                    totalFila.textContent =
                        formatearCLP(total);
                }
            });

        actualizarResumen();
    }





    function actualizarDatosComunesEnFilas() {
        if (!seleccionadosBody) {
            return;
        }

        const tarifa = montoComun();
        const observacion = observacionGeneral();

        seleccionadosBody
            .querySelectorAll(
                '[data-comision-masiva-monto-fila]'
            )
            .forEach(function (elemento) {
                elemento.textContent =
                    formatearCLP(tarifa);
            });

        seleccionadosBody
            .querySelectorAll(
                '[data-comision-masiva-observacion-fila]'
            )
            .forEach(function (elemento) {
                elemento.textContent =
                    observacion || '—';
            });

        actualizarTotalesFilas();
    }









    function guardarDatosEditados() {
        if (!seleccionadosBody) {
            return;
        }

        seleccionadosBody
            .querySelectorAll('[data-comision-masiva-pago]')
            .forEach(function (row) {
                const pagoUid = row.dataset.uid;

                if (!pagoUid) {
                    return;
                }

                const index = pagos.findIndex(
                    function (pago) {
                        return pago.uid === pagoUid;
                    }
                );

                if (index < 0) {
                    return;
                }

                const transportistaSelect = row.querySelector(
                    '[data-comision-masiva-transportista]'
                );

                const cantidadInput = row.querySelector(
                    '[data-comision-masiva-cantidad]'
                );

                pagos[index].suscripcion_transportista_id =
                    transportistaSelect?.value || '';

                pagos[index].transportista_label =
                    optionLabel(transportistaSelect);

                const cantidad = entero(
                    cantidadInput?.value || ''
                );

                pagos[index].cantidad =
                    cantidad === null ? '' : cantidad;
            });
    }








    function aplicarBusqueda() {
        const termino = normalizarTexto(
            buscadorInput?.value || ''
        );

        filasProveedor().forEach(function (row) {
            const textoBusqueda = normalizarTexto(
                row.dataset.busqueda || ''
            );

            const visible = !termino
                || textoBusqueda.includes(termino);

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

    function agregarPago(row) {
        guardarDatosEditados();

        const pago = datosPagoDesdeFila(row);

        if (!pago.suscripcion_proveedor_id) {
            return;
        }

        pagos.push(pago);

        ocultarError();
        renderizarPagos();
    }

    function quitarPago(pagoUid) {
        guardarDatosEditados();

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

    function reiniciarModal() {
        pagos = [];

        if (montoInput) {
            montoInput.value = '';
        }

        if (observacionGeneralInput) {
            observacionGeneralInput.value = '';
        }

        if (buscadorInput) {
            buscadorInput.value = '';
        }

        ocultarError();
        aplicarBusqueda();
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
                    <td colspan="7" class="text-muted text-center">
                        No hay pagos adicionales preparados.
                    </td>
                </tr>
            `;

            actualizarResumen();
            return;
        }

        pagos.forEach(function (item) {
            const row = document.createElement('tr');

            const cantidad = entero(item.cantidad);

            const totalFila =
                cantidad !== null && cantidad > 0
                    ? montoComun() * cantidad
                    : 0;

            row.dataset.comisionMasivaPago = '1';
            row.dataset.uid = item.uid;

            row.innerHTML = `
                <td>
                    <div class="fw-semibold">
                        ${escaparHtml(
                            item.razon_social
                            || item.proveedor_label
                            || 'Proveedor'
                        )}
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
                        ${
                            transportistaTemplate?.innerHTML
                            || '<option value="">Seleccionar transportista...</option>'
                        }
                    </select>
                </td>

                <td
                    class="text-end fw-semibold"
                    data-comision-masiva-monto-fila
                >
                    ${formatearCLP(montoComun())}
                </td>

                <td>
                    <input
                        type="number"
                        class="form-control form-control-sm text-center"
                        min="1"
                        step="1"
                        value="${escaparHtml(item.cantidad ?? '')}"
                        placeholder="Ej: 10"
                        autocomplete="off"
                        data-comision-masiva-cantidad
                    >
                </td>

                <td
                    class="text-end fw-semibold"
                    data-comision-masiva-total-fila
                >
                    ${formatearCLP(totalFila)}
                </td>

                <td data-comision-masiva-observacion-fila>
                    ${escaparHtml(
                        observacionGeneral() || '—'
                    )}
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

            const transportistaSelect = row.querySelector(
                '[data-comision-masiva-transportista]'
            );

            if (transportistaSelect) {
                transportistaSelect.value =
                    item.suscripcion_transportista_id || '';

                item.transportista_label =
                    optionLabel(transportistaSelect);
            }
        });

        actualizarTotalesFilas();
    }




    function construirComisiones() {
        guardarDatosEditados();

        const tarifa = montoComun();
        const observacion = observacionGeneral();

        return pagos.map(function (item) {
            const cantidad = entero(item.cantidad);

            return {
                clave_control: [
                    'COMISION',
                    item.suscripcion_proveedor_id || '',
                    item.suscripcion_transportista_id || '',
                    tarifa,
                    cantidad ?? '',
                    observacion,
                    item.uid,
                ].join('|'),

                codigo: 'COMISION',

                suscripcion_proveedor_id:
                    item.suscripcion_proveedor_id || '',

                suscripcion_transportista_id:
                    item.suscripcion_transportista_id || '',

                proveedor_label:
                    item.proveedor_label || '',

                transportista_label:
                    item.transportista_label || '',

                punto_1: '',
                origen_gasto: 'Suscripciones',
                punto_2: '',
                servicio: 'Reparto fin de semana',

                /*
                * costo representa la tarifa unitaria.
                */
                costo: tarifa,

                /*
                * Cantidad individual del pago.
                */
                cantidad:
                    cantidad === null ? 0 : cantidad,

                observacion,
            };
        });
    }




    function validarComisiones(comisiones) {
        const tarifa = montoComun();

        if (tarifa <= 0) {
            return 'Ingresa una tarifa unitaria mayor a 0.';
        }

        if (comisiones.length === 0) {
            return 'Agrega al menos un pago adicional.';
        }

        const sinProveedor = comisiones.find(
            function (comision) {
                return !comision.suscripcion_proveedor_id;
            }
        );

        if (sinProveedor) {
            return 'Todos los pagos adicionales deben tener proveedor.';
        }

        const sinTransportista = comisiones.find(
            function (comision) {
                return !comision.suscripcion_transportista_id;
            }
        );

        if (sinTransportista) {
            return 'Todos los pagos adicionales deben tener transportista.';
        }

        const tarifaInvalida = comisiones.find(
            function (comision) {
                const costo = entero(comision.costo);

                return costo === null || costo <= 0;
            }
        );

        if (tarifaInvalida) {
            return 'Todos los pagos adicionales deben tener una tarifa válida mayor a 0.';
        }

        const cantidadInvalida = comisiones.find(
            function (comision) {
                const cantidad = entero(comision.cantidad);

                return cantidad === null || cantidad <= 0;
            }
        );

        if (cantidadInvalida) {
            return 'Ingresa una cantidad mayor a 0 para todos los pagos preparados.';
        }

        return '';
    }




    function cerrarModal() {
        if (
            window.jQuery
            && typeof window.jQuery(modal).modal === 'function'
        ) {
            window.jQuery(modal).modal('hide');
            return;
        }

        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        modal.style.display = 'none';

        document.body.classList.remove('modal-open');

        document
            .querySelectorAll('.modal-backdrop')
            .forEach(function (backdrop) {
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

        if (
            typeof comisionesApi.agregarComisionesMasivas
            === 'function'
        ) {
            const resultado =
                comisionesApi.agregarComisionesMasivas(
                    comisiones
                );

            const agregados =
                resultado?.agregados ?? comisiones.length;

            const duplicados =
                resultado?.duplicados ?? 0;

            if (agregados > 0) {
                reiniciarModal();
                cerrarModal();
                return;
            }

            if (duplicados > 0) {
                mostrarError(
                    `Se omitieron ${duplicados} pago(s) adicional(es) porque fueron considerados duplicados.`
                );

                return;
            }

            mostrarError(
                'No fue posible agregar los pagos adicionales al resumen.'
            );

            return;
        }

        document.dispatchEvent(
            new CustomEvent(
                'suscripciones:comisiones-masivas',
                {
                    detail: {
                        comisiones,
                    },
                }
            )
        );

        reiniciarModal();
        cerrarModal();
    }

    function registrarEventos() {
        if (montoInput) {
            montoInput.addEventListener(
                'input',
                function () {
                    ocultarError();
                    actualizarDatosComunesEnFilas();
                }
            );
        }

        if (observacionGeneralInput) {
            observacionGeneralInput.addEventListener(
                'input',
                function () {
                    ocultarError();
                    actualizarDatosComunesEnFilas();
                }
            );
        }

        if (buscarBtn) {
            buscarBtn.addEventListener(
                'click',
                aplicarBusqueda
            );
        }

        if (limpiarBusquedaBtn) {
            limpiarBusquedaBtn.addEventListener(
                'click',
                limpiarBusqueda
            );
        }

        if (buscadorInput) {
            buscadorInput.addEventListener(
                'input',
                aplicarBusqueda
            );

            buscadorInput.addEventListener(
                'keydown',
                function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        aplicarBusqueda();
                    }
                }
            );
        }

        if (proveedoresBody) {
            proveedoresBody.addEventListener(
                'click',
                function (event) {
                    const button = event.target.closest(
                        '[data-comision-masiva-agregar-pago]'
                    );

                    if (!button) {
                        return;
                    }

                    const row = button.closest(
                        '[data-comision-masiva-proveedor]'
                    );

                    if (!row) {
                        return;
                    }

                    agregarPago(row);
                }
            );
        }

        if (seleccionadosBody) {
            seleccionadosBody.addEventListener(
                'click',
                function (event) {
                    const button = event.target.closest(
                        '[data-comision-masiva-quitar]'
                    );

                    if (!button) {
                        return;
                    }

                    quitarPago(button.dataset.uid);
                }
            );

            /*
            * Recalcular inmediatamente cuando cambia
            * la cantidad de una fila.
            */
            seleccionadosBody.addEventListener(
                'input',
                function (event) {
                    if (
                        !event.target.matches(
                            '[data-comision-masiva-cantidad]'
                        )
                    ) {
                        return;
                    }

                    guardarDatosEditados();
                    ocultarError();
                    actualizarTotalesFilas();
                }
            );

            /*
            * Guardar cambios del transportista
            * y también de la cantidad al abandonar el input.
            */
            seleccionadosBody.addEventListener(
                'change',
                function (event) {
                    if (
                        !event.target.matches(
                            '[data-comision-masiva-transportista], '
                            + '[data-comision-masiva-cantidad]'
                        )
                    ) {
                        return;
                    }

                    guardarDatosEditados();
                    ocultarError();
                    actualizarTotalesFilas();
                }
            );
        }

        if (limpiarSeleccionBtn) {
            limpiarSeleccionBtn.addEventListener(
                'click',
                limpiarPagos
            );
        }

        if (confirmarBtn) {
            confirmarBtn.addEventListener(
                'click',
                confirmarComisiones
            );
        }
    }

    registrarEventos();
    aplicarBusqueda();
    renderizarPagos();
}