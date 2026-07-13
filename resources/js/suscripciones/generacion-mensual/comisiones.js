/**
 * resources/js/suscripciones/generacion-mensual/comisiones.js
 *
 * Maneja la sección de comisiones / pagos adicionales del mes.
 * Mantiene el nombre técnico "comisiones" porque así lo espera el backend.
 *
 * Regla:
 * - Cada pago adicional representa un registro independiente.
 * - Un mismo proveedor puede tener uno o varios pagos adicionales.
 * - Dos pagos pueden contener exactamente los mismos datos.
 * - No se deben eliminar ni bloquear pagos por considerarlos duplicados.
 */

import {
    agregarHidden,
    escaparHtml,
    formatearCLP,
    labelPorValor,
    limpiarTexto,
    optionLabel,
} from './utils';

export function inicializarComisiones(dom, comisionesIniciales = []) {
    let comisiones = [];

    function actualizarTotalComisionActual() {
        const { costoInput, totalInput } = dom.comision;

        if (!costoInput || !totalInput) {
            return;
        }

        const costo = parseInt(costoInput.value || 0, 10);

        totalInput.value = formatearCLP(
            Number.isNaN(costo) ? 0 : costo
        );
    }

    function limpiarFormularioComision() {
        const c = dom.comision;

        if (c.proveedorSelect) {
            c.proveedorSelect.value = '';
        }

        if (c.transportistaSelect) {
            c.transportistaSelect.value = '';
        }

        if (c.punto1Input) {
            c.punto1Input.value = '';
        }

        if (c.origenGastoInput) {
            c.origenGastoInput.value = 'Suscripciones';
        }

        if (c.punto2Input) {
            c.punto2Input.value = '';
        }

        if (c.servicioInput) {
            c.servicioInput.value = 'Reparto fin de semana';
        }

        if (c.costoInput) {
            c.costoInput.value = '';
        }

        if (c.observacionInput) {
            c.observacionInput.value = '';
        }

        actualizarTotalComisionActual();
    }

    function normalizarComision(comision) {
        const costo = parseInt(comision.costo || 0, 10);

        return {
            codigo:
                limpiarTexto(comision.codigo || 'COMISION')
                || 'COMISION',

            suscripcion_proveedor_id:
                comision.suscripcion_proveedor_id || '',

            suscripcion_transportista_id:
                comision.suscripcion_transportista_id || '',

            proveedor_label:
                limpiarTexto(comision.proveedor_label || '—'),

            transportista_label:
                limpiarTexto(comision.transportista_label || '—'),

            punto_1:
                limpiarTexto(comision.punto_1 || ''),

            origen_gasto:
                limpiarTexto(
                    comision.origen_gasto || 'Suscripciones'
                ) || 'Suscripciones',

            punto_2:
                limpiarTexto(comision.punto_2 || ''),

            servicio:
                limpiarTexto(
                    comision.servicio || 'Reparto fin de semana'
                ) || 'Reparto fin de semana',

            costo:
                Number.isNaN(costo) ? 0 : costo,

            observacion:
                limpiarTexto(comision.observacion || ''),
        };
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

            if (c.cantidadTexto) {
                c.cantidadTexto.textContent = '0';
            }

            if (c.totalTexto) {
                c.totalTexto.textContent = formatearCLP(0);
            }

            return;
        }

        let total = 0;

        comisiones.forEach(function (comision, index) {
            total += parseInt(comision.costo || 0, 10);

            agregarHidden(
                `comisiones[${index}][codigo]`,
                comision.codigo || 'COMISION',
                c.hiddenContainer
            );

            agregarHidden(
                `comisiones[${index}][suscripcion_proveedor_id]`,
                comision.suscripcion_proveedor_id,
                c.hiddenContainer
            );

            agregarHidden(
                `comisiones[${index}][suscripcion_transportista_id]`,
                comision.suscripcion_transportista_id,
                c.hiddenContainer
            );

            agregarHidden(
                `comisiones[${index}][punto_1]`,
                comision.punto_1,
                c.hiddenContainer
            );

            agregarHidden(
                `comisiones[${index}][origen_gasto]`,
                comision.origen_gasto,
                c.hiddenContainer
            );

            agregarHidden(
                `comisiones[${index}][punto_2]`,
                comision.punto_2,
                c.hiddenContainer
            );

            agregarHidden(
                `comisiones[${index}][servicio]`,
                comision.servicio,
                c.hiddenContainer
            );

            agregarHidden(
                `comisiones[${index}][costo]`,
                comision.costo,
                c.hiddenContainer
            );

            agregarHidden(
                `comisiones[${index}][observacion]`,
                comision.observacion,
                c.hiddenContainer
            );

            const row = document.createElement('tr');

            row.innerHTML = `
                <td>
                    ${escaparHtml(comision.proveedor_label)}
                </td>

                <td>
                    ${escaparHtml(comision.transportista_label)}
                </td>

                <td>
                    ${escaparHtml(comision.punto_1 || '—')}
                </td>

                <td>
                    ${escaparHtml(comision.servicio || '—')}
                </td>

                <td class="text-end">
                    ${formatearCLP(comision.costo)}
                </td>

                <td>
                    ${escaparHtml(comision.observacion || '—')}
                </td>

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

        if (c.cantidadTexto) {
            c.cantidadTexto.textContent = String(
                comisiones.length
            );
        }

        if (c.totalTexto) {
            c.totalTexto.textContent = formatearCLP(total);
        }
    }

    function agregarComisionDesdeFormulario() {
        const c = dom.comision;

        const proveedorId =
            c.proveedorSelect?.value || '';

        const transportistaId =
            c.transportistaSelect?.value || '';

        const costo = parseInt(
            c.costoInput?.value || '',
            10
        );

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

        const comisionNueva = normalizarComision({
            codigo: 'COMISION',

            suscripcion_proveedor_id:
                proveedorId,

            suscripcion_transportista_id:
                transportistaId,

            proveedor_label:
                optionLabel(c.proveedorSelect),

            transportista_label:
                optionLabel(c.transportistaSelect),

            punto_1:
                limpiarTexto(c.punto1Input?.value),

            origen_gasto:
                limpiarTexto(c.origenGastoInput?.value)
                || 'Suscripciones',

            punto_2:
                limpiarTexto(c.punto2Input?.value),

            servicio:
                limpiarTexto(c.servicioInput?.value)
                || 'Reparto fin de semana',

            costo,

            observacion:
                limpiarTexto(c.observacionInput?.value),
        });

        /*
         * No se valida duplicidad.
         *
         * Cada clic representa un pago adicional independiente,
         * incluso si sus datos son iguales a otro pago ya agregado.
         */
        comisiones.push(comisionNueva);

        limpiarFormularioComision();
        renderizarComisiones();
    }

    function agregarComisionesMasivas(comisionesNuevas) {
        if (!Array.isArray(comisionesNuevas)) {
            return {
                agregados: 0,
                duplicados: 0,
            };
        }

        let agregados = 0;

        comisionesNuevas.forEach(function (comision) {
            const comisionNormalizada =
                normalizarComision(comision);

            if (
                !comisionNormalizada.suscripcion_proveedor_id
                || !comisionNormalizada.suscripcion_transportista_id
            ) {
                return;
            }

            /*
             * Cada elemento del arreglo representa un pago independiente.
             *
             * No se compara con pagos existentes, porque un proveedor
             * puede recibir más de un pago con los mismos datos.
             */
            comisiones.push(comisionNormalizada);
            agregados++;
        });

        if (agregados > 0) {
            renderizarComisiones();
        }

        /*
         * Se conserva "duplicados" en la respuesta para mantener
         * compatibilidad con comisiones-masivas.js.
         */
        return {
            agregados,
            duplicados: 0,
        };
    }

    function restaurarComisionesIniciales() {
        const c = dom.comision;

        if (!Array.isArray(comisionesIniciales)) {
            return;
        }

        comisionesIniciales.forEach(function (comision) {
            comisiones.push(
                normalizarComision({
                    codigo:
                        comision.codigo || 'COMISION',

                    suscripcion_proveedor_id:
                        comision.suscripcion_proveedor_id,

                    suscripcion_transportista_id:
                        comision.suscripcion_transportista_id,

                    proveedor_label:
                        labelPorValor(
                            c.proveedorSelect,
                            comision.suscripcion_proveedor_id
                        ),

                    transportista_label:
                        labelPorValor(
                            c.transportistaSelect,
                            comision.suscripcion_transportista_id
                        ),

                    punto_1:
                        limpiarTexto(comision.punto_1 || ''),

                    origen_gasto:
                        limpiarTexto(
                            comision.origen_gasto
                            || 'Suscripciones'
                        ),

                    punto_2:
                        limpiarTexto(comision.punto_2 || ''),

                    servicio:
                        limpiarTexto(
                            comision.servicio
                            || 'Reparto fin de semana'
                        ),

                    costo:
                        parseInt(comision.costo || 0, 10),

                    observacion:
                        limpiarTexto(
                            comision.observacion || ''
                        ),
                })
            );
        });
    }

    function registrarEventosComisiones() {
        const c = dom.comision;

        if (c.costoInput) {
            c.costoInput.addEventListener(
                'input',
                actualizarTotalComisionActual
            );
        }

        if (c.agregarBtn) {
            c.agregarBtn.addEventListener(
                'click',
                agregarComisionDesdeFormulario
            );
        }

        if (c.resumenBody) {
            c.resumenBody.addEventListener(
                'click',
                function (event) {
                    const button = event.target.closest(
                        '[data-action="eliminar-comision"]'
                    );

                    if (!button) {
                        return;
                    }

                    const index = parseInt(
                        button.dataset.index,
                        10
                    );

                    if (
                        Number.isNaN(index)
                        || index < 0
                        || index >= comisiones.length
                    ) {
                        return;
                    }

                    comisiones.splice(index, 1);
                    renderizarComisiones();
                }
            );
        }
    }

    registrarEventosComisiones();
    restaurarComisionesIniciales();
    actualizarTotalComisionActual();
    renderizarComisiones();

    return {
        agregarComisionesMasivas,
        renderizarComisiones,
    };
}